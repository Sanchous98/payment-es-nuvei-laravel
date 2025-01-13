<?php

namespace PaymentSystem\Laravel\Nuvei\Jobs;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Nuvei\Api\Environment;
use Nuvei\Api\RestClient;
use Nuvei\Api\Service\Payments\CreditCard as CreditCardService;
use PaymentSystem\Contracts\DecryptInterface;
use PaymentSystem\Events\TokenCreated;
use PaymentSystem\Gateway\Resources\TokenInterface;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\DTO\CardTokenizationRequest;
use PaymentSystem\Laravel\Nuvei\Gateway\Token;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\Repositories\TokenRepositoryInterface;
use PaymentSystem\TokenAggregateRoot;

class TokenCreateJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;
    use Batchable;

    private RestClient $client;

    public function __construct(
        private readonly TokenCreated $event,
        private readonly Message $message,
        private readonly Account $account,
    ) {
        $this->client = new RestClient([
            'environment' => App::isProduction() ? Environment::LIVE : Environment::TEST,
            'merchantId' => $this->account->credentials->merchant_id,
            'merchantSiteId' => $this->account->credentials->site_id,
            'merchantSecretKey' => $this->account->credentials->secret_key,
        ]);
    }

    public function uniqueId(): string
    {
        return $this->message->aggregateRootId() . $this->account->id;
    }

    public function __invoke(TokenRepositoryInterface $repository, DecryptInterface $decrypt): void
    {
        $token = $repository->retrieve($this->message->aggregateRootId());
        $token->getGatewayTokens()->add(fn() => $this->tokenize($decrypt, $token));
    }

    private function tokenize(DecryptInterface $decrypt, TokenAggregateRoot $token): TokenInterface
    {
        $service = new CreditCardService($this->client);
        $req = new CardTokenizationRequest($service->getSessionToken(), $this->event->source, $this->event->billingAddress);
        $rsp = $service->cardTokenization($req->toArray($decrypt));

        return new Token(Uuid::fromString($this->account->id), $token->getSource(), $rsp);
    }
}