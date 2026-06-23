<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class NoQuantityException extends DomainException
{
    public function __construct(string $invoiceId)
    {
        parent::__construct("Invoice {$invoiceId} contains a product line with quantity or unit price not greater than zero.");
    }
}
