<?php

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Ramsey\Uuid\UuidInterface;

class Invoice
{
    protected UuidInterface $id;
    protected string $customerName;
    protected string $customerEmail;
    /** @var InvoiceProductLine[] $products */
    protected array $products = [];
    protected StatusEnum $status;

    public function __construct(UuidInterface $id, string $customerName, string $customerEmail, StatusEnum $status)
    {
        $this->id = $id;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->status = $status;
    }

    // Type hinting in a separate method to ensure the correct data type is provided
    public function setProducts(InvoiceProductLine ...$products): self
    {
        $this->products = $products;

        return $this;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getStatus(): StatusEnum
    {
        return $this->status;
    }

    /** @return InvoiceProductLine[] */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function markAsSending(): void
    {
        $this->status = StatusEnum::Sending;
    }

    public function markAsSentToClient(): void
    {
        $this->status = StatusEnum::SentToClient;
    }
}