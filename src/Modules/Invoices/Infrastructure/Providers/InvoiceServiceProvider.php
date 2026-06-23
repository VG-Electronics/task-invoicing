<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\Repositories\EloquentInvoiceRepository;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepository::class, EloquentInvoiceRepository::class);
    }
}
