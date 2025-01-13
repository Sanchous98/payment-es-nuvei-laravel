<?php

namespace PaymentSystem\Laravel\Nuvei\DTO;

readonly class CreateUPOResponse
{
    public function __construct(
        public array $response,
    ) {
    }

    public function getUPOId(): string
    {
        return $this->response['userPaymentOptionId'];
    }
}