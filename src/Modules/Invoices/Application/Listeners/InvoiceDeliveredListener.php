<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final readonly class InvoiceDeliveredListener
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
    ) {}

    public function handle(WebhookDeliveredEvent $event): void
    {
        $invoice = $this->invoiceRepository->getById($event->resourceId);

        if ($invoice === null || $invoice->getStatus() !== StatusEnum::Sending) {
            return;
        }

        $invoice->markAsSentToClient();

        $this->invoiceRepository->updateInvoice($invoice);
    }
}
