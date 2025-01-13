<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

use EventSauce\EventSourcing\AggregateRootId;
use Money\Currency;
use Money\Money;
use PaymentSystem\Gateway\Resources\RefundInterface;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\RefundAggregateRoot;
use PaymentSystem\ValueObjects\GenericId;

readonly class Refund implements RefundInterface
{
    public function __construct(
        public Uuid $accountId,
        public AggregateRootId $paymentIntentId,
        public Transaction $tx,
    ) {
    }

    public function getId(): AggregateRootId
    {
        return Uuid::fromString($this->tx->data['transactionDetails']['transactionId']);
    }

    public function getGatewayId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function getRawData(): array
    {
        return $this->tx->data;
    }

    public function isValid(): bool
    {
        return strtoupper($this->tx->data['status']) === 'SUCCESS' && strtoupper($this->tx->data['transactionDetails']['transactionStatus']) === 'APPROVED';
    }

    public function getFee(): ?Money
    {
        return null;
    }

    public function getMoney(): Money
    {
        $requested = $this->tx->data['partialApproval'];

        return new Money($requested['requestedAmount'], new Currency($requested['requestedCurrency']));
    }

    public function getPaymentIntentId(): AggregateRootId
    {
        return $this->paymentIntentId;
    }
}