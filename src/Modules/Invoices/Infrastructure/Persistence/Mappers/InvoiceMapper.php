<?php

namespace Modules\Invoices\Infrastructure\Persistence\Mappers;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceProductLineModel;
use Ramsey\Uuid\Uuid;

class InvoiceMapper
{
    public function mapToEntity(InvoiceModel $model): Invoice
    {
        $invoice = new Invoice(
            id: Uuid::fromString($model->id),
            customerName: $model->customer_name,
            customerEmail: $model->customer_email,
            status: $model->status
        );

        $invoice->setProducts(...array_map(
            fn(InvoiceProductLineModel $productModel) => new InvoiceProductLine(
                id: Uuid::fromString($productModel->id),
                name: $productModel->name,
                price: $productModel->price,
                quantity: $productModel->quantity,
            ),
            $model->productLines->all()
        ));

        return $invoice;
    }
}
