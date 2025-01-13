<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

use EventSauce\EventSourcing\AggregateRootId;
use PaymentSystem\Contracts\TokenizedSourceInterface;
use PaymentSystem\Gateway\Resources\TokenInterface;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\ValueObjects\GenericId;
use Ramsey\Uuid\UuidInterface;

readonly class Token implements TokenInterface
{
    /**
     * @param array{
     * internalRequestId: integer,
     * status: "SUCCESS"|"ERROR",
     * errCode: integer,
     * reason: string,
     * version: "1.0",
     * sessionToken: string|UuidInterface,
     * ccTempToken: string|UuidInterface,
     * isVerified: bool,
     * uniqueCC: string,
     * cardType: "Credit"|"Debit",
     * issuerCountry: string
     * } $data
     */
    public function __construct(
        public Uuid $accountId,
        public TokenizedSourceInterface $source,
        public array $data,
    ) {
    }

    public function getId(): AggregateRootId
    {
        return new GenericId($this->data['ccTempToken']);
    }

    public function getGatewayId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function getRawData(): array
    {
        return $this->data;
    }

    public function isValid(): bool
    {
        return strtoupper($this->data['status']) === 'SUCCESS' && $this->data['isVerified'];
    }

    public function getSource(): TokenizedSourceInterface
    {
        return $this->source;
    }
}