<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Queries;

use Modules\Invoices\Application\Dtos\InvoiceDto;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Ramsey\Uuid\Uuid;
use RuntimeException;

readonly class ShowInvoiceQuery
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
    ) {}

    public function execute(string $invoiceId): InvoiceDto
    {
        $invoice = $this->invoiceRepository->getById(Uuid::fromString($invoiceId));

        if ($invoice === null) {
            throw new RuntimeException("Invoice {$invoiceId} not found.");
        }

        return InvoiceDto::fromEntity($invoice);
    }
}
