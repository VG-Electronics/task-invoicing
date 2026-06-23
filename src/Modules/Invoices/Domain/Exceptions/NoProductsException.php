<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class NoProductsException extends DomainException
{
    public function __construct(string $invoiceId)
    {
        parent::__construct("Invoice {$invoiceId} must have at least one product line to be sent.");
    }
}
