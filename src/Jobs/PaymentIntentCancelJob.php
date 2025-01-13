<?php

namespace PaymentSystem\Laravel\Nuvei\Jobs;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nuvei\Api\Environment;
use Nuvei\Api\RestClient;
use Nuvei\Api\Service\PaymentService;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentIntent;
use PaymentSystem\Laravel\Nuvei\Gateway\Transaction;
use PaymentSystem\Laravel\Nuvei\Nuvei\TransactionDetailsService;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;

class PaymentIntentCancelJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    private RestClient $client;

    public function __construct(
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

    public function __invoke(PaymentIntentRepositoryInterface $repository): void
    {
        $paymentIntent = $repository->retrieve($this->message->aggregateRootId());

        $service = new PaymentService($this->client);
        $transactionDetailsService = new TransactionDetailsService($this->client);

        assert($paymentIntent->getGatewayPaymentIntent()->getPaymentIntent()->getGatewayId()->toString() === (string)$this->account->id);

        $paymentIntent
            ->getGatewayPaymentIntent()
            ->capture(function (PaymentIntent $nuvei) use($service, $paymentIntent, $transactionDetailsService) {
                $result = $service->voidTransaction([
                    'clientUniqueId' => $this->message->aggregateRootId()->toString(),
                    'amount' => $paymentIntent->getMoney()->getAmount(),
                    'currency' => $paymentIntent->getMoney()->getCurrency()->getCode(),
                    'relatedTransactionId' => $nuvei->getId(),
                ]);

                return new PaymentIntent(
                    Uuid::fromString($this->account->id),
                    $paymentIntent->getTenderId(),
                    new Transaction($transactionDetailsService->getTransactionDetails($result['transactionId'])),
                );
            });
    }
}