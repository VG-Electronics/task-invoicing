<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Commands;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceNotDraftException;
use Modules\Invoices\Domain\Exceptions\NoProductsException;
use Modules\Invoices\Domain\Exceptions\NoQuantityException;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final readonly class SendInvoiceCommand
{
    public function __construct(
        private InvoiceRepository           $invoiceRepository,
        private NotificationFacadeInterface $notificationFacade,
    ) {}

    public function execute(string $invoiceId): void
    {
        $uuid = Uuid::fromString($invoiceId);
        $invoice = $this->invoiceRepository->getById($uuid);

        if ($invoice === null) {
            throw new RuntimeException("Invoice {$invoiceId} not found.");
        }

        if ($invoice->getStatus() !== StatusEnum::Draft) {
            throw new InvoiceNotDraftException($invoiceId);
        }

        if (empty($invoice->getProducts())) {
            throw new NoProductsException($invoiceId);
        }

        foreach ($invoice->getProducts() as $product) {
            if ($product->getQuantity() <= 0 || $product->getPrice() <= 0) {
                throw new NoQuantityException($invoiceId);
            }
        }

        $invoice->markAsSending();

        $this->invoiceRepository->updateInvoice($invoice);

        $this->notificationFacade->notify(new NotifyData(
            resourceId: $invoice->getId(),
            toEmail: $invoice->getCustomerEmail(),
            subject: 'Your invoice is ready',
            message: $this->buildMessage($invoice->getCustomerName(), $invoice->getProducts()),
        ));
    }

    /** @param InvoiceProductLine[] $products */
    private function buildMessage(string $customerName, array $products): string
    {
        $lines = array_map(
            fn(InvoiceProductLine $p) => sprintf(
                '  - %s | Qty: %d | Unit price: %d | Total: %d',
                $p->getName(),
                $p->getQuantity(),
                $p->getPrice(),
                $p->getQuantity() * $p->getPrice(),
            ),
            $products,
        );

        $totalPrice = array_sum(array_map(
            fn(InvoiceProductLine $p) => $p->getQuantity() * $p->getPrice(),
            $products,
        ));

        return implode("\n", [
            "Dear {$customerName},",
            '',
            'Please find your invoice details below:',
            '',
            implode("\n", $lines),
            '',
            "Total price: {$totalPrice}",
        ]);
    }
}
