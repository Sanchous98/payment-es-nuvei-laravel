<?php

namespace PaymentSystem\Laravel\Nuvei\Serializer;

use PaymentSystem\Gateway\Resources\PaymentMethodInterface;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentMethod;
use PaymentSystem\Laravel\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaymentMethodNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof PaymentMethod);

        return [
            'account_id' => $data->accountId->toString(),
            'billing_address' => $this->normalizer->normalize($data->address, $format, $context),
            'source' => $this->normalizer->normalize($data->source, $format, $context),
            'data' => $data->getRawData(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PaymentMethod;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PaymentMethod
    {
        return new PaymentMethod(
            Uuid::fromString($data['account_id']),
            $this->denormalizer->denormalize($data['billing_address'], $type, $format, $context),
            $this->denormalizer->denormalize($data['source'], $type, $format, $context),
            $data['data'],
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, PaymentMethodInterface::class, true)
            && isset($data['data']['bin'])
            && isset($data['data']['last4digits']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PaymentMethodInterface::class => false,
            PaymentMethod::class => true,
        ];
    }
}