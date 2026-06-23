<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;

final readonly class InvoiceProductLineDto
{
    public function __construct(
        public string $product_name,
        public int $quantity,
        public int $unit_price,
        public int $total_unit_price,
    ) {}

    public static function fromEntity(InvoiceProductLine $line): self
    {
        return new self(
            product_name: $line->getName(),
            quantity: $line->getQuantity(),
            unit_price: $line->getPrice(),
            total_unit_price: $line->getPrice() * $line->getQuantity(),
        );
    }
}
