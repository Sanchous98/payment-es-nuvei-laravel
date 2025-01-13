<?php

namespace PaymentSystem\Laravel\Nuvei\Listeners;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use PaymentSystem\Events\PaymentMethodCreated;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Nuvei\Jobs\PaymentMethodCreateJob;
use PaymentSystem\Laravel\Nuvei\Models\Credentials;
use PaymentSystem\Repositories\PaymentMethodRepositoryInterface;
use PaymentSystem\Repositories\TokenRepositoryInterface;

readonly class PaymentMethodCreateListener implements ShouldQueue
{
    public function __construct(
        private PaymentMethodRepositoryInterface $repository,
        private TokenRepositoryInterface $tokens,
        private QueueingDispatcher $dispatcher,
    ) {
    }

    public function __invoke(PaymentMethodCreated $event, Message $message): void
    {
        $accounts = Account::with('credentials')
            ->whereIn('id', $message->header(AccountDecorator::ACCOUNT_IDS_HEADER))
            ->get();

        if ($accounts->isEmpty()) {
            $this->repository->retrieve($message->aggregateRootId())->fail();
            return;
        }

        $accounts = $accounts->filter(fn(Account $account) => $account->credentials instanceof Credentials);

        if ($accounts->isEmpty()) {
            return;
        }

        $batch = $this->dispatcher
            ->batch($accounts->map(fn(Account $account) => new PaymentMethodCreateJob($event, $message, $account)))
            ->name("Create payment method ({$message->aggregateRootId()})")
            ->withOption('id', $message->aggregateRootId())
            ->finally(function (Batch $batch) {
                if ($batch->pendingJobs === $batch->totalJobs) {
                    $repository = \app(PaymentMethodRepositoryInterface::class);
                    $repository->retrieve($batch->options['id'])->fail();
                }
            });

        if ($event->tokenId !== null) {
            $this->tokens->retrieve($event->tokenId)->use($batch->dispatch(...));
        } else {
            $batch->dispatch();
        }
    }
}