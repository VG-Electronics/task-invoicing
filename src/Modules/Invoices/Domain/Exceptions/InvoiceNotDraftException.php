<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvoiceNotDraftException extends DomainException
{
    public function __construct(string $invoiceId)
    {
        parent::__construct("Invoice {$invoiceId} must be in draft status to be sent.");
    }
}
