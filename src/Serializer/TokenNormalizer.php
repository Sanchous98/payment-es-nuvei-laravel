<?php

namespace PaymentSystem\Laravel\Nuvei\Serializer;

use PaymentSystem\Contracts\SourceInterface;
use PaymentSystem\Gateway\Resources\TokenInterface;
use PaymentSystem\Laravel\Nuvei\Gateway\Token;
use PaymentSystem\Laravel\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TokenNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof Token);

        return [
            'account_id' => $data->accountId->toString(),
            'source' => $this->normalizer->normalize($data->source, $format, $context),
            'data' => $data->getRawData(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Token;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Token
    {
        return new Token(
            Uuid::fromString($data['account_id']),
            $this->denormalizer->denormalize($data['source'], SourceInterface::class),
            $data['data'],
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, TokenInterface::class, true)
            && isset($data['data']['ccTempToken'])
            && \Ramsey\Uuid\Uuid::isValid($data['data']['ccTempToken']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TokenInterface::class => false,
            Token::class => true,
        ];
    }
}