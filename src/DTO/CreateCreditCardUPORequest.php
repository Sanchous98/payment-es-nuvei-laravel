<?php

namespace PaymentSystem\Laravel\Nuvei\DTO;

use PaymentSystem\Contracts\DecryptInterface;
use PaymentSystem\ValueObjects\BillingAddress;
use PaymentSystem\ValueObjects\CreditCard;

readonly class CreateCreditCardUPORequest
{
    public function __construct(
        public CreditCard $creditCard,
        public BillingAddress $billingAddress,
        public string $userTokenId,
        public string $clientRequestId,
    ) {
    }

    public function toArray(DecryptInterface $decrypt): array
    {
        return [
            'userTokenId' => $this->userTokenId,
            'clientRequestId' => $this->clientRequestId,
            "ccCardNumber" => $this->creditCard->number->getNumber($decrypt),
            "ccExpMonth" => $this->creditCard->expiration->format('m'),
            "ccExpYear" => $this->creditCard->expiration->format('Y'),
            "ccNameOnCard" => (string)$this->creditCard->holder,
            'billingAddress' => [
                'country' => (string)$this->billingAddress->country,
                'email' => (string)$this->billingAddress->email,
                'firstName' => $this->billingAddress->firstName,
                'lastName' => $this->billingAddress->lastName,
                'phone' => (string)$this->billingAddress->phone,
                'zip' => $this->billingAddress->postalCode,
                'city' => $this->billingAddress->city,
                'state' => $this->billingAddress->state ? (string)$this->billingAddress->state : null,
                'address' => $this->billingAddress->addressLine,
                'addressLine2' => $this->billingAddress->addressLineExtra,
            ],
        ];
    }
}