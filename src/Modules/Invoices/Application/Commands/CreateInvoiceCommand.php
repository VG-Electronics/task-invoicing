<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Commands;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Ramsey\Uuid\Uuid;

final class CreateInvoiceCommand
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    public function execute(string $customerName, string $customerEmail, array $products): string
    {
        $invoice = new Invoice(
            id: Uuid::uuid4(),
            customerName: $customerName,
            customerEmail: $customerEmail,
            status: StatusEnum::Draft,
        );

        $invoice->setProducts(...array_map(
            fn(array $product) => new InvoiceProductLine(
                id: Uuid::uuid4(),
                name: $product['name'],
                price: $product['price'],
                quantity: $product['quantity'],
            ),
            $products,
        ));

        $this->invoiceRepository->createInvoice($invoice);

        return $invoice->getId()->toString();
    }
}
