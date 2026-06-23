<?php

namespace Modules\Invoices\Domain\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Ramsey\Uuid\UuidInterface;

interface InvoiceRepository
{
    public function getById(UuidInterface $id): ?Invoice;

    public function createInvoice(Invoice $invoice): void;

    public function updateInvoice(Invoice $invoice): void;
}
