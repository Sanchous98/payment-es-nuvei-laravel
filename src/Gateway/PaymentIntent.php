<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

use EventSauce\EventSourcing\AggregateRootId;
use Money\Money;
use PaymentSystem\Gateway\Resources\PaymentIntentInterface;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\PaymentIntentAggregateRoot;
use PaymentSystem\ValueObjects\GenericId;
use PaymentSystem\ValueObjects\ThreeDSResult;
use Ramsey\Uuid\UuidInterface;

readonly class PaymentIntent implements PaymentIntentInterface
{
    /**
     * @param array{
     * orderId: string,
     * userTokenId: string,
     * isAFT: "True"|"False",
     * isAFTOverridden: "True"|"False",
     * transactionStatus: "APPROVED"|"DECLINED"|"ERROR",
     * gwErrorCode: integer,
     * gwExtendedErrorCode: integer,
     * transactionType: "Sale"|"Auth"|"PreAuth",
     * transactionId: string,
     * externalTransactionId: string,
     * authCode: string,
     * customData: string,
     * fraudDetails: array{
     *     finalDecision: "Accept"|"Reject"|"Review"|"Error"|"None",
     *     score: float,
     *     recommendations: string,
     *     system: array{systemId: string, systemName: string, decision: string},
     *     rules: array{ruleId: string, ruleDescription: string},
     * },
     * sessionToken: string|UuidInterface,
     * clientUniqueId: mixed,
     * internalRequestId: integer,
     * status: "SUCCESS"|"ERROR",
     * errCode: integer,
     * reason: string,
     * merchantId: string,
     * merchantSiteId: string,
     * version: "1.0",
     * clientRequestId: mixed,
     * merchantAdviceCode: "01"|"02"|"03"|"04"|"21"|"24"|"25"|"26"|"27"|"28"|"29"|"30"|"40"|"41"|"42"|"43",
     * paymentOption: array{
     *     userPaymentOptionId: string,
     *     card: array{
     *         ccCardNumber: string,
     *         bin: string,
     *         last4Digits: string,
     *         ccExpMonth: string,
     *         ccExpYear: string,
     *         acquirerId: string,
     *         cvv2Reply: 'M'|'N'|'P'|'U'|'S',
     *         avsCode: 'A'|'W'|'Y'|'X'|'Z'|'U'|'S'|'R'|'B'|'N',
     *         cardType: "Debit"|"Credit",
     *         cardBrand: "VISA"|"MASTERCARD"|"AMEX"|"DINERS"|"DISCOVER",
     *         threeD: array,
     *     },
     * }} $data
     */
    public function __construct(
        public Uuid $accountId,
        public Money $money,
        public string $merchantDescriptor,
        public string $description,
        public AggregateRootId $paymentMethodId,
        public ?ThreeDSResult $threeDS,
        public string $declineReason,
        public array $data,
    ) {
    }

    public static function fromOriginal(Uuid $accountId, PaymentIntentAggregateRoot $original, array $data): self
    {
        return new self(
            $accountId,
            $original->getMoney(),
            $original->getMerchantDescriptor(),
            $original->getDescription(),
            $original->getTenderId(),
            $original->getThreeDSResult(),
            $original->getDeclineReason(),
            $data,
        );
    }

    public function getId(): AggregateRootId
    {
        return new GenericId($this->data['transactionId']);
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
        return strtoupper($this->data['status']) === 'SUCCESS' && strtoupper(
                $this->data['transactionStatus']
            ) === 'APPROVED';
    }

    public function getFee(): ?Money
    {
        return null;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getMerchantDescriptor(): string
    {
        return $this->merchantDescriptor;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentMethodId(): AggregateRootId
    {
        return $this->paymentMethodId;
    }

    public function getThreeDS(): ?ThreeDSResult
    {
        return $this->threeDS;
    }

    public function getDeclineReason(): string
    {
        return $this->declineReason;
    }
}