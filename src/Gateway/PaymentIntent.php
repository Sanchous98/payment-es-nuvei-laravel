<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

use EventSauce\EventSourcing\AggregateRootId;
use Money\Currency;
use Money\Money;
use PaymentSystem\Gateway\Resources\PaymentIntentInterface;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\ValueObjects\ThreeDSResult;

readonly class PaymentIntent implements PaymentIntentInterface
{
    public function __construct(
        public AggregateRootId $accountId,
        public AggregateRootId $paymentMethodId,
        public Transaction $last,
    ) {
    }

    public function getId(): AggregateRootId
    {
        return Uuid::fromString($this->last->data['transactionDetails']['transactionId']);
    }

    public function getGatewayId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function getRawData(): array
    {
        return $this->last->jsonSerialize();
    }

    public function isValid(): bool
    {
        return strtoupper($this->last->data['status']) === 'SUCCESS' && strtoupper($this->last->data['transactionDetails']['transactionStatus']) === 'APPROVED';
    }

    public function getFee(): ?Money
    {
        return null;
    }

    public function getMoney(): Money
    {
        $requested = $this->last->data['partialApproval'];

        return new Money($requested['requestedAmount'], new Currency($requested['requestedCurrency']));
    }

    public function getMerchantDescriptor(): string
    {
        return '';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getPaymentMethodId(): AggregateRootId
    {
        return $this->paymentMethodId;
    }

    public function getThreeDS(): ?ThreeDSResult
    {
        return null; // TODO
    }

    public function getDeclineReason(): string
    {
        return ''; // TODO
    }
}