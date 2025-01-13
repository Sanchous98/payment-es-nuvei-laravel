<?php

namespace PaymentSystem\Laravel\Nuvei\DTO;

use PaymentSystem\ValueObjects\BillingAddress;

readonly class UserCreateRequest
{
    public function __construct(
        public BillingAddress $billingAddress,
        public string $sessionToken,
        public string $clientRequestId,
        public string $userTokenId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'sessionToken' => $this->sessionToken,
            'clientRequestId' => $this->clientRequestId,
            'userTokenId' => $this->userTokenId,
            'countryCode' => (string)$this->billingAddress->country,
            'email' => (string)$this->billingAddress->email,
            'firstName' => $this->billingAddress->firstName,
            'lastName' => $this->billingAddress->lastName,
        ];
    }
}