<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Entities\Invoice;

final readonly class InvoiceDto
{
    /** @param InvoiceProductLineDto[] $invoice_product_lines */
    public function __construct(
        public string $invoice_id,
        public string $invoice_status,
        public string $customer_name,
        public string $customer_email,
        public array $invoice_product_lines,
        public int $total_price,
    ) {}

    public static function fromEntity(Invoice $invoice): self
    {
        $productLines = array_map(
            fn($line) => InvoiceProductLineDto::fromEntity($line),
            $invoice->getProducts(),
        );

        $totalPrice = array_sum(array_map(
            fn(InvoiceProductLineDto $line) => $line->total_unit_price,
            $productLines,
        ));

        return new self(
            invoice_id: $invoice->getId()->toString(),
            invoice_status: $invoice->getStatus()->value,
            customer_name: $invoice->getCustomerName(),
            customer_email: $invoice->getCustomerEmail(),
            invoice_product_lines: $productLines,
            total_price: $totalPrice,
        );
    }
}
