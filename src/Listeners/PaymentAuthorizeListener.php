<?php

namespace PaymentSystem\Laravel\Nuvei\Listeners;

use EventSauce\EventSourcing\Message;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use PaymentSystem\Events\PaymentIntentAuthorized;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Jobs\PaymentIntentAuthorizeJob;
use PaymentSystem\Laravel\Nuvei\Models\Credentials;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;

readonly class PaymentAuthorizeListener implements ShouldQueue
{
    public function __construct(private PaymentIntentRepositoryInterface $repository, private Dispatcher $dispatcher)
    {
    }

    public function __invoke(PaymentIntentAuthorized $event, Message $message): void
    {
        $account = Account::with('credentials')
            ->find($message->header(AccountDecorator::ACCOUNT_IDS_HEADER)[0]);

        if (!isset($account)) {
            $this->repository->retrieve($message->aggregateRootId())->decline('No accounts set for this request.');
            return;
        }

        if ($account->credentials instanceof Credentials) {
            $this->dispatcher->dispatch(new PaymentIntentAuthorizeJob($event, $message, $account));
        }
    }
}