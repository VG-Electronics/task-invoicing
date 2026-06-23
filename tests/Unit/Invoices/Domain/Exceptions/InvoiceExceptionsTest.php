<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Exceptions\InvoiceNotDraftException;
use Modules\Invoices\Domain\Exceptions\NoProductsException;
use Modules\Invoices\Domain\Exceptions\NoQuantityException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvoiceExceptionsTest extends TestCase
{
    public function testInvoiceNotDraftExceptionExtendsDomainException(): void
    {
        $this->assertInstanceOf(DomainException::class, new InvoiceNotDraftException('any-id'));
    }

    public function testNoProductsExceptionExtendsDomainException(): void
    {
        $this->assertInstanceOf(DomainException::class, new NoProductsException('any-id'));
    }

    public function testNoQuantityExceptionExtendsDomainException(): void
    {
        $this->assertInstanceOf(DomainException::class, new NoQuantityException('any-id'));
    }

    #[DataProvider('exceptionMessageProvider')]
    public function testExceptionMessageContainsInvoiceId(string $class, string $invoiceId): void
    {
        $exception = new $class($invoiceId);

        $this->assertStringContainsString($invoiceId, $exception->getMessage());
    }

    #[DataProvider('exceptionMessageProvider')]
    public function testExceptionMessageIsNotEmpty(string $class, string $invoiceId): void
    {
        $exception = new $class($invoiceId);

        $this->assertNotEmpty($exception->getMessage());
    }

    public function testInvoiceNotDraftExceptionMessageMentionsDraftStatus(): void
    {
        $exception = new InvoiceNotDraftException('abc-123');

        $this->assertStringContainsStringIgnoringCase('draft', $exception->getMessage());
    }

    public function testNoProductsExceptionMessageMentionsProduct(): void
    {
        $exception = new NoProductsException('abc-123');

        $this->assertStringContainsStringIgnoringCase('product', $exception->getMessage());
    }

    public function testNoQuantityExceptionMessageMentionsQuantityOrPrice(): void
    {
        $exception = new NoQuantityException('abc-123');

        $message = $exception->getMessage();
        $this->assertTrue(
            str_contains(strtolower($message), 'quantity') || str_contains(strtolower($message), 'price'),
        );
    }

    public static function exceptionMessageProvider(): array
    {
        $id = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        return [
            'InvoiceNotDraftException' => [InvoiceNotDraftException::class, $id],
            'NoProductsException'      => [NoProductsException::class, $id],
            'NoQuantityException'      => [NoQuantityException::class, $id],
        ];
    }
}
