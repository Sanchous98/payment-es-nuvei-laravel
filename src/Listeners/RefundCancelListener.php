<?php

namespace PaymentSystem\Laravel\Nuvei\Listeners;

use EventSauce\EventSourcing\Message;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use PaymentSystem\Events\RefundCanceled;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Jobs\RefundCancelJob;
use PaymentSystem\Laravel\Nuvei\Models\Credentials;
use PaymentSystem\Repositories\RefundRepositoryInterface;

readonly class RefundCancelListener implements ShouldQueue
{
    public function __construct(private RefundRepositoryInterface $repository, private Dispatcher $dispatcher)
    {
    }

    public function __invoke(RefundCanceled $event, Message $message): void
    {
        $account = Account::with('credentials')
            ->find($message->header(AccountDecorator::ACCOUNT_IDS_HEADER)[0]);

        if (!isset($account)) {
            $this->repository->retrieve($message->aggregateRootId())->decline('No accounts set for this request.');
            return;
        }

        if ($account->credentials instanceof Credentials) {
            $this->dispatcher->dispatch(new RefundCancelJob($event, $message, $account));
        }
    }
}