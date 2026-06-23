<?php

namespace Modules\Invoices\Infrastructure\Persistence\Mappers;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceProductLineModel;
use Ramsey\Uuid\Uuid;

class InvoiceMapper
{
    public function mapToModel(Invoice $invoice): InvoiceModel
    {
        $model = new InvoiceModel([
            'customer_name' => $invoice->getCustomerName(),
            'customer_email' => $invoice->getCustomerEmail(),
            'status' => $invoice->getStatus(),
        ]);
        $model->id = $invoice->getId()->toString();

        return $model;
    }

    public function mapProductLineToModel(InvoiceProductLine $productLine, string $invoiceId): InvoiceProductLineModel
    {
        $model = new InvoiceProductLineModel([
            'invoice_id' => $invoiceId,
            'name'       => $productLine->getName(),
            'price'      => $productLine->getPrice(),
            'quantity'   => $productLine->getQuantity(),
        ]);
        $model->id = $productLine->getId()->toString();

        return $model;
    }

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
