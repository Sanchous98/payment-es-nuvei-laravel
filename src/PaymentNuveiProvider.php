<?php

namespace PaymentSystem\Laravel\Nuvei;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use PaymentSystem\Events\PaymentIntentAuthorized;
use PaymentSystem\Events\PaymentIntentCanceled;
use PaymentSystem\Events\PaymentIntentCaptured;
use PaymentSystem\Events\PaymentMethodCreated;
use PaymentSystem\Events\PaymentMethodUpdated;
use PaymentSystem\Events\RefundCanceled;
use PaymentSystem\Events\RefundCreated;
use PaymentSystem\Events\TokenCreated;
use PaymentSystem\Laravel\Nuvei\Listeners\PaymentAuthorizeListener;
use PaymentSystem\Laravel\Nuvei\Listeners\PaymentCancelListener;
use PaymentSystem\Laravel\Nuvei\Listeners\PaymentCaptureListener;
use PaymentSystem\Laravel\Nuvei\Listeners\PaymentMethodCreateListener;
use PaymentSystem\Laravel\Nuvei\Listeners\PaymentMethodUpdateListener;
use PaymentSystem\Laravel\Nuvei\Listeners\RefundCancelListener;
use PaymentSystem\Laravel\Nuvei\Listeners\RefundCreateListener;
use PaymentSystem\Laravel\Nuvei\Listeners\TokenCreateListener;
use PaymentSystem\Laravel\Nuvei\Migrations\CredentialsMigration;
use PaymentSystem\Laravel\Nuvei\Serializer\PaymentIntentNormalizer;
use PaymentSystem\Laravel\Nuvei\Serializer\PaymentMethodNormalizer;
use PaymentSystem\Laravel\Nuvei\Serializer\RefundNormalizer;
use PaymentSystem\Laravel\Nuvei\Serializer\TokenNormalizer;

class PaymentNuveiProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([CredentialsMigration::class], 'payment-migrations');

        Event::listen(PaymentIntentAuthorized::class, PaymentAuthorizeListener::class);
        Event::listen(PaymentIntentCanceled::class, PaymentCancelListener::class);
        Event::listen(PaymentIntentCaptured::class, PaymentCaptureListener::class);
        Event::listen(PaymentMethodCreated::class, PaymentMethodCreateListener::class);
        Event::listen(PaymentMethodUpdated::class, PaymentMethodUpdateListener::class);
        Event::listen(RefundCanceled::class, RefundCancelListener::class);
        Event::listen(RefundCreated::class, RefundCreateListener::class);
        Event::listen(TokenCreated::class, TokenCreateListener::class);

        $this->app->tag([
            PaymentIntentNormalizer::class,
            PaymentMethodNormalizer::class,
            RefundNormalizer::class,
            TokenNormalizer::class,
        ], 'normalizers');
    }
}
