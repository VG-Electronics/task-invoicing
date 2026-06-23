<?php

namespace Modules\Invoices\Infrastructure\Persistence\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\Mappers\InvoiceMapper;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceModel;
use Ramsey\Uuid\Uuid;

readonly class EloquentInvoiceRepository implements InvoiceRepository
{
    public function __construct(private InvoiceMapper $mapper)
    {

    }

    public function getById(Uuid $id): ?Invoice
    {
        $invoiceModel = InvoiceModel::query()->with('productLines')->find($id->toString());

        if (!$invoiceModel) {
            return null;
        }

        return $this->mapper->mapToEntity($invoiceModel);
    }
}
