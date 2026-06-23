<?php

namespace Modules\Invoices\Domain\Entities;

use Ramsey\Uuid\UuidInterface;

class InvoiceProductLine
{
    public function __construct(
        private readonly UuidInterface $id,
        private readonly string $name,
        private readonly int $price,
        private readonly int $quantity,
    ) {}

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
