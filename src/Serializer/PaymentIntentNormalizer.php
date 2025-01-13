<?php

namespace PaymentSystem\Laravel\Nuvei\Serializer;

use PaymentSystem\Gateway\Resources\PaymentIntentInterface;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentIntent;
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
            'account_id' => $data->accountId->toString(),
            'money' => $this->normalizer->normalize($data->money, $format, $context),
            'merchant_descriptor' => $data->merchantDescriptor,
            'description' => $data->description,
            'decline_reason' => $data->declineReason,
            'payment_method_id' => $this->normalizer->normalize($data->paymentMethodId, $format, $context),
            'three_ds' => $this->normalizer->normalize($data->threeDS, $format, $context),
            'data' => $data->getRawData(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PaymentIntent;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PaymentIntent
    {
        return new PaymentIntent(
            Uuid::fromString($data['account_id']),
            $this->denormalizer->denormalize($data['money'], $type, $format, $context),
            $data['merchant_descriptor'],
            $data['description'],
            $data['decline_reason'],
            $this->denormalizer->denormalize($data['payment_method_id'], $type, $format, $context),
            $this->denormalizer->denormalize($data['three_ds'], $type, $format, $context),
            $data['data'],
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