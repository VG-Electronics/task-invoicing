<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\Commands\CreateInvoiceCommand;
use Modules\Invoices\Application\Commands\SendInvoiceCommand;
use Modules\Invoices\Application\Commands\UpdateInvoiceCommand;
use Modules\Invoices\Application\Queries\ShowInvoiceQuery;
use Modules\Invoices\Presentation\Http\Requests\CreateInvoiceRequest;
use Modules\Invoices\Presentation\Http\Requests\SendInvoiceRequest;
use Modules\Invoices\Presentation\Http\Requests\ShowInvoiceRequest;
use Modules\Invoices\Presentation\Http\Requests\UpdateInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class InvoiceController
{
    public function __construct(
        private CreateInvoiceCommand $createInvoiceCommand,
        private UpdateInvoiceCommand $updateInvoiceCommand,
        private SendInvoiceCommand $sendInvoiceCommand,
        private ShowInvoiceQuery $showInvoiceQuery,
    ) {}

    public function show(ShowInvoiceRequest $request, string $id): JsonResponse
    {
        $dto = $this->showInvoiceQuery->execute($id);

        return new JsonResponse(data: $dto, status: Response::HTTP_OK);
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $this->createInvoiceCommand->execute(
            customerName: $request->validated('customer_name'),
            customerEmail: $request->validated('customer_email'),
            products: $request->validated('products'),
        );

        return new JsonResponse(data: null, status: Response::HTTP_CREATED);
    }

    public function update(UpdateInvoiceRequest $request, string $id): JsonResponse
    {
        $this->updateInvoiceCommand->execute(
            invoiceId: $id,
            customerName: $request->validated('customer_name'),
            customerEmail: $request->validated('customer_email'),
            products: $request->validated('products') ?? [],
        );

        return new JsonResponse(data: null, status: Response::HTTP_OK);
    }

    public function send(SendInvoiceRequest $request, string $id): JsonResponse
    {
        $this->sendInvoiceCommand->execute(invoiceId: $id);

        return new JsonResponse(data: null, status: Response::HTTP_OK);
    }
}
