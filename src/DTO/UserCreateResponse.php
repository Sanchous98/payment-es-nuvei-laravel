<?php

namespace PaymentSystem\Laravel\Nuvei\DTO;

readonly class UserCreateResponse
{
    public function __construct(
        public array $response
    ) {
    }

    public function getUserTokenId(): string
    {
        return $this->response['userTokenId'];
    }
}