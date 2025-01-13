<?php

namespace PaymentSystem\Laravel\Nuvei\Jobs;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nuvei\Api\Environment;
use Nuvei\Api\RestClient;
use Nuvei\Api\Service\UserPaymentOptions;
use PaymentSystem\Events\PaymentMethodUpdated;
use PaymentSystem\Gateway\Resources\PaymentMethodInterface;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentMethod;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\Repositories\PaymentMethodRepositoryInterface;
use PaymentSystem\ValueObjects\BillingAddress;
use PaymentSystem\ValueObjects\CreditCard;

class PaymentMethodUpdateJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    private RestClient $client;

    public function __construct(
        private readonly PaymentMethodUpdated $event,
        private readonly Message $message,
        private readonly Account $account,
    ) {
        $this->client = new RestClient([
            'environment' => Environment::TEST,
            'merchantId' => $this->account->credentials->merchant_id,
            'merchantSiteId' => $this->account->credentials->site_id,
            'merchantSecretKey' => $this->account->credentials->secret_key,
        ]);
    }

    public function uniqueId(): string
    {
        return $this->message->aggregateRootId() . $this->account->id;
    }

    public function __invoke(PaymentMethodRepositoryInterface $repository): void
    {
        $paymentMethod = $repository->retrieve($this->message->aggregateRootId());
        $nuveiUpo = $paymentMethod->getGatewayPaymentMethods()
            ->find(fn(PaymentMethodInterface $method) => $method instanceof PaymentMethod && $method->accountId->equals(Uuid::fromString($this->account->id)));
        $paymentMethod
            ->getGatewayPaymentMethods()
            ->update($nuveiUpo->getGatewayId(), $nuveiUpo->getId(), function (PaymentMethodInterface $upo) use($paymentMethod): PaymentMethod {
                $service = new UserPaymentOptions($this->client);
                $source = $paymentMethod->getSource();
                $rsp = match ($source::class) {
                    CreditCard::class => $service->editUPOCC([
                        'userPaymentOptionId' => $upo->getId(),
                        'ccExpMonth' => $source->expiration->format('m'),
                        'ccExpYear' => $source->expiration->format('Y'),
                        'ccNameOnCard' => (string)$source->holder,
                        'billingAddress' => self::address($this->event->billingAddress),
                    ]),
                };
                return PaymentMethod::fromOriginal(Uuid::fromString($this->account->id), $paymentMethod, array_merge($upo->getRawData(), $rsp));
            });
    }

    private static function address(BillingAddress $billingAddress): array
    {
        return [
            'country' => (string)$billingAddress->country,
            'email' => (string)$billingAddress->email,
            'firstName' => $billingAddress->firstName,
            'lastName' => $billingAddress->lastName,
            'phone' => (string)$billingAddress->phone,
            'zip' => $billingAddress->postalCode,
            'city' => $billingAddress->city,
            'state' => $billingAddress->state ? (string)$billingAddress->state : null,
            'address' => $billingAddress->addressLine,
            'addressLine2' => $billingAddress->addressLineExtra,
        ];
    }
}