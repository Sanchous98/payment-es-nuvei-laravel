<?php

namespace PaymentSystem\Laravel\Nuvei\Serializer;

use PaymentSystem\Gateway\Resources\PaymentIntentInterface;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentIntent;
use PaymentSystem\Laravel\Nuvei\Gateway\Transaction;
use PaymentSystem\Laravel\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaymentIntentNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof PaymentIntent);

        return [
            'account_id' => $this->normalizer->normalize($data->getGatewayId()),
            'payment_method_id' => $this->normalizer->normalize($data->getPaymentMethodId()),
            'transaction' => $data->getRawData()
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PaymentIntent;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PaymentIntent
    {
        return new PaymentIntent(
            $this->denormalizer->denormalize($data['account_id'], $type),
            $this->denormalizer->denormalize($data['payment_method_id'], $type),
            new Transaction($data['transaction']['data'], isset($data['transaction']['prev']) ? new Transaction($data['transaction']['prev']) : null),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, PaymentIntentInterface::class, true)
            && isset($data['data']['sessionToken'])
            && \Ramsey\Uuid\Uuid::isValid($data['data']['sessionToken']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PaymentIntentInterface::class => false,
            PaymentIntent::class => true,
        ];
    }
}