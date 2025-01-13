<?php

namespace PaymentSystem\Laravel\Nuvei\Jobs;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nuvei\Api\Environment;
use Nuvei\Api\Exception\ValidationException;
use Nuvei\Api\RestClient;
use Nuvei\Api\Service\PaymentService;
use PaymentSystem\Events\PaymentIntentAuthorized;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentIntent;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentMethod;
use PaymentSystem\Laravel\Nuvei\Gateway\Token;
use PaymentSystem\Laravel\Nuvei\Gateway\Transaction;
use PaymentSystem\Laravel\Nuvei\Nuvei\TransactionDetailsService;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;
use PaymentSystem\Repositories\TenderRepositoryInterface;

class PaymentIntentAuthorizeJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    private RestClient $client;

    public function __construct(
        private readonly PaymentIntentAuthorized $event,
        private readonly Message $message,
        private readonly Account $account,
    ) {
        $this->client = new RestClient([
            'environment' => Environment::TEST,
            'merchantId' => $this->account->credentials->merchant_id,
            'merchantSiteId' => $this->account->credentials->site_id,
            'merchantSecretKey' => $this->account->credentials->secret_key,
        ]);
    }

    public function uniqueId(): string
    {
        return $this->message->aggregateRootId() . $this->account->id;
    }

    public function __invoke(
        PaymentIntentRepositoryInterface $repository,
        TenderRepositoryInterface $tenderRepository
    ): void {
        if (!isset($this->event->tenderId)) {
            throw new \InvalidArgumentException("Nuvei doesn't support nullable tender on authorize stage");
        }

        $tender = $tenderRepository->retrieve($this->event->tenderId);
        $gatewayTender = collect($tender->getGatewayTenders())
            ->first(fn(Token|PaymentIntent $tender) => $tender->accountId->equals(Uuid::fromString($this->account->id)));

        $paymentOption = match ($gatewayTender::class) {
            Token::class => ['card' => [
                'ccTempToken' => $gatewayTender->getId()->toString(),
            ]],
            PaymentMethod::class => [
                'userPaymentOptionId' => $gatewayTender->getId()->toString(),
            ],
            default => throw new \RuntimeException('unknown tender'),
        };

        $params = [
            'transactionType' => 'Auth',
            'clientUniqueId' => $this->message->aggregateRootId()->toString(),
            'amount' => $this->event->money->getAmount(),
            'currency' => $this->event->money->getCurrency()->getCode(),
            'paymentOption' => $paymentOption,
        ];

        $service = new PaymentService($this->client);
        $paymentIntent = $repository->retrieve($this->message->aggregateRootId());
        $transactionDetailsService = new TransactionDetailsService($this->client);

        try {
            $tender
                ->use(fn() => $paymentIntent
                    ->getGatewayPaymentIntent()
                    ->authorize(function () use ($service, $params, $tender, $transactionDetailsService) {
                        $result = $service->createPayment($params);

                        return new PaymentIntent(
                            Uuid::fromString($this->account->id),
                            $tender->aggregateRootId(),
                            new Transaction($transactionDetailsService->getTransactionDetails($result['transactionId'])),
                        );
                    }));
        } catch (ValidationException $e) {
            $paymentIntent->decline($e->getMessage());
        }
    }
}