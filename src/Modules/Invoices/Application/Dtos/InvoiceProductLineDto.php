<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;

final readonly class InvoiceProductLineDto
{
    public function __construct(
        public string $id,
        public string $name,
        public int $unitPrice,
        public int $quantity,
        public int $totalUnitPrice,
    ) {}

    public static function fromEntity(InvoiceProductLine $line): self
    {
        return new self(
            id: $line->getId()->toString(),
            name: $line->getName(),
            unitPrice: $line->getPrice(),
            quantity: $line->getQuantity(),
            totalUnitPrice: $line->getPrice() * $line->getQuantity(),
        );
    }
}
