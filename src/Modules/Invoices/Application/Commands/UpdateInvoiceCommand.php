<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Commands;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class UpdateInvoiceCommand
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    public function execute(string $invoiceId, string $customerName, string $customerEmail, array $products): void
    {
        $existing = $this->invoiceRepository->getById(Uuid::fromString($invoiceId));

        if ($existing === null) {
            throw new RuntimeException("Invoice {$invoiceId} not found.");
        }

        $invoice = new Invoice(
            id: $existing->getId(),
            customerName: $customerName,
            customerEmail: $customerEmail,
            status: $existing->getStatus(),
        );

        $invoice->setProducts(...array_map(
            fn(array $product) => new InvoiceProductLine(
                id: isset($product['id']) ? Uuid::fromString($product['id']) : Uuid::uuid4(),
                name: $product['name'],
                price: $product['price'],
                quantity: $product['quantity'],
            ),
            $products,
        ));

        $this->invoiceRepository->updateInvoice($invoice);
    }
}
