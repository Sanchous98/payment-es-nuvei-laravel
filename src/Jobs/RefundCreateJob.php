<?php

namespace PaymentSystem\Laravel\Nuvei\Jobs;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Nuvei\Api\Environment;
use Nuvei\Api\RestClient;
use Nuvei\Api\Service\PaymentService;
use PaymentSystem\Events\RefundCreated;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentIntent;
use PaymentSystem\Laravel\Nuvei\Gateway\Refund;
use PaymentSystem\Laravel\Nuvei\Nuvei\TransactionDetailsService;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\RefundAggregateRoot;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;
use PaymentSystem\Repositories\RefundRepositoryInterface;

class RefundCreateJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    private RestClient $client;

    public function __construct(
        private readonly RefundCreated $event,
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

    public function __invoke(PaymentIntentRepositoryInterface $paymentIntentRepository, RefundRepositoryInterface $repository): void
    {
        $service = new PaymentService($this->client);
        $transactionDetailsService = new TransactionDetailsService($this->client);

        $refund = $repository->retrieve($this->message->aggregateRootId());
        $paymentIntent = $paymentIntentRepository->retrieve($refund->getPaymentIntentId());
        $nuveiPayment = $paymentIntent->getGatewayPaymentIntent()->getPaymentIntent();

        $refund
            ->getGatewayRefund()
            ->create(function () use($service, $refund, $nuveiPayment, $transactionDetailsService) {
                $result = $service->refundTransaction([
                    'clientUniqueId' => $this->message->aggregateRootId()->toString(),
                    'amount' => $refund->getMoney()->getAmount(),
                    'currency' => (string)$refund->getMoney()->getCurrency(),
                    'relatedTransactionId' => $nuveiPayment->getId(),
                ]);

                return new Refund(
                    Uuid::fromString($this->account->id),
                    $refund->getPaymentIntentId(),
                    $transactionDetailsService->getTransactionDetails($result['transactionId']),
                );
            });
    }
}