<?php

namespace PaymentSystem\Laravel\Nuvei\Serializer;

use PaymentSystem\Gateway\Resources\RefundInterface;
use PaymentSystem\Laravel\Nuvei\Gateway\Refund;
use PaymentSystem\Laravel\Nuvei\Gateway\Transaction;
use PaymentSystem\Laravel\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RefundNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof Refund);

        return [
            'account_id' => $data->accountId->toString(),
            'payment_intent_id' => $this->normalizer->normalize($data->paymentIntentId, $format, $context),
            'transaction' => $data->getRawData(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Refund;
    }


    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Refund
    {
        return new Refund(
            $this->denormalizer->denormalize($data['account_id'], $type),
            $this->denormalizer->denormalize($data['payment_intent_id'], $type),
            new Transaction($data['transaction']['data'], isset($data['transaction']['prev']) ? new Transaction($data['transaction']['prev']) : null),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, RefundInterface::class, true)
            && isset($data['data']['merchantId'])
            && isset($data['data']['merchantSiteId']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            RefundInterface::class => false,
            Refund::class => true,
        ];
    }
}