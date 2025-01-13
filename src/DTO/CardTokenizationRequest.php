<?php

namespace PaymentSystem\Laravel\Nuvei\DTO;

use PaymentSystem\Contracts\DecryptInterface;
use PaymentSystem\Contracts\SourceInterface;
use PaymentSystem\ValueObjects\BillingAddress;
use PaymentSystem\ValueObjects\CreditCard;

readonly class CardTokenizationRequest
{
    public function __construct(
        public string $sessionToken,
        public SourceInterface $source,
        public BillingAddress $billingAddress,
    ) {
    }

    public function toArray(DecryptInterface $decrypt): array
    {
        $source = $this->source;

        return [
            'sessionToken' => $this->sessionToken,
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
            ...match ($source::class) {
                CreditCard::class => [
                    'cardData' => [
                        'cardNumber' => $source->number->getNumber($decrypt),
                        'cardHolderName' => (string)$source->holder,
                        'expirationMonth' => $source->expiration->format('m'),
                        'expirationYear' => $source->expiration->format('Y'),
                        'CVV' => $source->cvc->getCvc($decrypt)
                    ]
                ]
            },
        ];
    }
}