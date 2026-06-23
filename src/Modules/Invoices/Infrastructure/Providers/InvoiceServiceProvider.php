<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Listeners\InvoiceDeliveredListener;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\Repositories\EloquentInvoiceRepository;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepository::class, EloquentInvoiceRepository::class);
    }

    public function boot(): void
    {
        $this->app->make(Dispatcher::class)->listen(
            WebhookDeliveredEvent::class,
            InvoiceDeliveredListener::class,
        );
    }
}
