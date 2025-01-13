<?php

namespace PaymentSystem\Laravel\Nuvei\Jobs;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Nuvei\Api\Environment;
use Nuvei\Api\RestClient;
use Nuvei\Api\Service\UserPaymentOptions;
use Nuvei\Api\Service\UserService;
use PaymentSystem\Contracts\DecryptInterface;
use PaymentSystem\Contracts\SourceInterface;
use PaymentSystem\Events\PaymentMethodCreated;
use PaymentSystem\Gateway\Resources\TokenInterface;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\DTO\CreateCreditCardUPORequest;
use PaymentSystem\Laravel\Nuvei\DTO\CreateUPOResponse;
use PaymentSystem\Laravel\Nuvei\DTO\UserCreateRequest;
use PaymentSystem\Laravel\Nuvei\DTO\UserCreateResponse;
use PaymentSystem\Laravel\Nuvei\Exceptions\UnsupportedSourceTypeException;
use PaymentSystem\Laravel\Nuvei\Gateway\PaymentMethod;
use PaymentSystem\Laravel\Nuvei\Gateway\Token;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\Repositories\PaymentMethodRepositoryInterface;
use PaymentSystem\Repositories\TokenRepositoryInterface;
use PaymentSystem\TokenAggregateRoot;
use PaymentSystem\ValueObjects\BillingAddress;
use PaymentSystem\ValueObjects\CreditCard;

class PaymentMethodCreateJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;
    use Batchable;

    private RestClient $client;

    public function __construct(
        private readonly PaymentMethodCreated $event,
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

    public function __invoke(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        TokenRepositoryInterface $tokenRepository,
        DecryptInterface $decrypt
    ): void {
        $userTokenId = $this->createCustomer($this->event->billingAddress)->getUserTokenId();

        if ($this->event->tokenId !== null) {
            $token = $tokenRepository->retrieve($this->event->tokenId);
            $upoResponse = self::createFromToken($this->event->billingAddress, $userTokenId, $token);
        } else {
            $upoResponse = self::create($this->event->billingAddress, $userTokenId, $this->event->source, $decrypt);
        }

        $paymentMethod = $paymentMethodRepository->retrieve($this->message->aggregateRootId());
        $paymentMethod
            ->getGatewayPaymentMethods()
            ->add(fn() => PaymentMethod::fromOriginal(
                Uuid::fromString($this->account->id),
                $paymentMethod,
                $upoResponse,
            ));
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

    private function createCustomer(BillingAddress $billingAddress): UserCreateResponse
    {
        $service = new UserService($this->client);
        $req = new UserCreateRequest(
            $billingAddress,
            $service->getSessionToken(),
            Str::orderedUuid(),
            Str::orderedUuid()
        );

        return new UserCreateResponse($service->createUser($req->toArray()));
    }

    private function createFromToken(BillingAddress $billingAddress, string $userTokenId, TokenAggregateRoot $token): CreateUPOResponse
    {
        /** @var Token|null $nuveiToken */
        $nuveiToken = $token->getGatewayTokens()
            ->find(fn(TokenInterface $token) => $token instanceof Token && $token->accountId->equals(Uuid::fromString($this->account->id)));

        assert($nuveiToken !== null);

        $service = new UserPaymentOptions($this->client);

        return new CreateUPOResponse($service->addUPOCreditCardByTempToken([
            'sessionToken' => $service->getSessionToken(),
            'clientRequestId' => Str::orderedUuid(),
            'userTokenId' => $userTokenId,
            'ccTempToken' => $nuveiToken->ccTempToken,
            'billingAddress' => self::address($billingAddress),
        ]));
    }

    private function create(BillingAddress $billingAddress, string $userTokenId, SourceInterface $source, DecryptInterface $decrypt): CreateUPOResponse
    {
        $service = new UserPaymentOptions($this->client);

        return new CreateUPOResponse(match ($source::class) {
            CreditCard::class => $service->addUPOCreditCard((new CreateCreditCardUPORequest(
                $source,
                $billingAddress,
                $userTokenId,
                Str::orderedUuid(),
            ))->toArray($decrypt)),
            default => throw new UnsupportedSourceTypeException(),
        });
    }
}