<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Entities\Invoice;

final readonly class InvoiceDto
{
    /** @param InvoiceProductLineDto[] $productLines */
    public function __construct(
        public string $id,
        public string $customerName,
        public string $customerEmail,
        public string $status,
        public array $productLines,
        public int $totalPrice,
    ) {}

    public static function fromEntity(Invoice $invoice): self
    {
        $productLines = array_map(
            fn($line) => InvoiceProductLineDto::fromEntity($line),
            $invoice->getProducts(),
        );

        $totalPrice = array_sum(array_map(
            fn(InvoiceProductLineDto $line) => $line->totalUnitPrice,
            $productLines,
        ));

        return new self(
            id: $invoice->getId()->toString(),
            customerName: $invoice->getCustomerName(),
            customerEmail: $invoice->getCustomerEmail(),
            status: $invoice->getStatus()->value,
            productLines: $productLines,
            totalPrice: $totalPrice,
        );
    }
}
