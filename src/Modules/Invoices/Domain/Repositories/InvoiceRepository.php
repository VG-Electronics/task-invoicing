<?php

namespace Modules\Invoices\Domain\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Ramsey\Uuid\Uuid;

interface InvoiceRepository
{
    public function getById(Uuid $id): ?Invoice;
}
