<?php

namespace Modules\Invoices\Infrastructure\Persistence\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
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

    public function createInvoice(Invoice $invoice): void
    {
        $model = $this->mapper->mapToModel($invoice);
        $model->save();

        foreach ($invoice->getProducts() as $product) {
            $this->mapper->mapProductLineToModel($product, $model->id)->save();
        }
    }

    public function updateInvoice(Invoice $invoice): void
    {
        $model = $this->mapper->mapToModel($invoice);
        $model->exists = true;
        $model->save();

        $incomingIds = array_map(
            fn(InvoiceProductLine $p) => $p->getId()->toString(),
            $invoice->getProducts(),
        );

        if (empty($incomingIds)) {
            $model->productLines()->delete();
        } else {
            $model->productLines()->whereNotIn('id', $incomingIds)->delete();
        }

        $existingIds = $model->productLines()->pluck('id')->all();

        foreach ($invoice->getProducts() as $product) {
            $productModel = $this->mapper->mapProductLineToModel($product, $model->id);
            $productModel->exists = in_array($product->getId()->toString(), $existingIds);
            $productModel->save();
        }
    }
}
