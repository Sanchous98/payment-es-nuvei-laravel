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
use PaymentSystem\Events\RefundCanceled;
use PaymentSystem\Gateway\RefundAggregate;
use PaymentSystem\Gateway\Resources\RefundInterface;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Gateway\Refund;
use PaymentSystem\Laravel\Nuvei\Nuvei\TransactionDetailsService;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\RefundAggregateRoot;
use PaymentSystem\Repositories\RefundRepositoryInterface;

class RefundCancelJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    private RestClient $client;

    public function __construct(
        private readonly RefundCanceled $event,
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

    public function __invoke(RefundRepositoryInterface $repository): void
    {
        $service = new PaymentService($this->client);
        $transactionDetailsService = new TransactionDetailsService($this->client);

        $refund = $repository->retrieve($this->message->aggregateRootId());
        $refund->getGatewayRefund()
            ->cancel(function(Refund $nuvei) use($service, $refund, $transactionDetailsService) {
                $result = $service->voidTransaction([
                    'clientUniqueId' => $this->message->aggregateRootId()->toString(),
                    'amount' => $refund->getMoney()->getAmount(),
                    'currency' => (string)$refund->getMoney()->getCurrency(),
                    'relatedTransactionId' => $nuvei->getId(),
                ]);

                return new Refund(
                    Uuid::fromString($this->account->id),
                    $refund->getPaymentIntentId(),
                    $transactionDetailsService->getTransactionDetails($result['transactionId']),
                );
            });
    }
}