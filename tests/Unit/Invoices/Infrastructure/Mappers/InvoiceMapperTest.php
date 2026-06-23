<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Infrastructure\Mappers;

use Illuminate\Database\Eloquent\Collection;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Infrastructure\Persistence\Mappers\InvoiceMapper;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceProductLineModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceMapperTest extends TestCase
{
    private InvoiceMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new InvoiceMapper();
    }

    // --- mapToModel ---

    public function testMapToModelReturnsInvoiceModelInstance(): void
    {
        $model = $this->mapper->mapToModel($this->makeInvoice());

        $this->assertInstanceOf(InvoiceModel::class, $model);
    }

    public function testMapToModelPreservesId(): void
    {
        $invoice = $this->makeInvoice();

        $model = $this->mapper->mapToModel($invoice);

        $this->assertSame($invoice->getId()->toString(), $model->id);
    }

    public function testMapToModelPreservesCustomerName(): void
    {
        $invoice = $this->makeInvoice(customerName: 'Jane Doe');

        $model = $this->mapper->mapToModel($invoice);

        $this->assertSame('Jane Doe', $model->customer_name);
    }

    public function testMapToModelPreservesCustomerEmail(): void
    {
        $invoice = $this->makeInvoice(customerEmail: 'jane@example.com');

        $model = $this->mapper->mapToModel($invoice);

        $this->assertSame('jane@example.com', $model->customer_email);
    }

    #[DataProvider('statusProvider')]
    public function testMapToModelPreservesStatus(StatusEnum $status): void
    {
        $invoice = $this->makeInvoice(status: $status);

        $model = $this->mapper->mapToModel($invoice);

        $this->assertSame($status, $model->status);
    }

    // --- mapProductLineToModel ---

    public function testMapProductLineToModelReturnsCorrectInstance(): void
    {
        $model = $this->mapper->mapProductLineToModel($this->makeProductLine(), Uuid::uuid4()->toString());

        $this->assertInstanceOf(InvoiceProductLineModel::class, $model);
    }

    public function testMapProductLineToModelPreservesId(): void
    {
        $productLine = $this->makeProductLine();

        $model = $this->mapper->mapProductLineToModel($productLine, Uuid::uuid4()->toString());

        $this->assertSame($productLine->getId()->toString(), $model->id);
    }

    public function testMapProductLineToModelSetsInvoiceId(): void
    {
        $invoiceId = Uuid::uuid4()->toString();

        $model = $this->mapper->mapProductLineToModel($this->makeProductLine(), $invoiceId);

        $this->assertSame($invoiceId, $model->invoice_id);
    }

    public function testMapProductLineToModelPreservesAllProductData(): void
    {
        $productLine = new InvoiceProductLine(
            id: Uuid::uuid4(),
            name: 'Widget Pro',
            price: 499,
            quantity: 3,
        );

        $model = $this->mapper->mapProductLineToModel($productLine, Uuid::uuid4()->toString());

        $this->assertSame('Widget Pro', $model->name);
        $this->assertSame(499, $model->price);
        $this->assertSame(3, $model->quantity);
    }

    // --- mapToEntity ---

    public function testMapToEntityReturnsInvoiceInstance(): void
    {
        $invoiceModel = $this->makeInvoiceModel();
        $invoiceModel->setRelation('productLines', new Collection());

        $this->assertInstanceOf(Invoice::class, $this->mapper->mapToEntity($invoiceModel));
    }

    public function testMapToEntityPreservesId(): void
    {
        $invoiceModel = $this->makeInvoiceModel();
        $invoiceModel->setRelation('productLines', new Collection());

        $invoice = $this->mapper->mapToEntity($invoiceModel);

        $this->assertSame($invoiceModel->id, $invoice->getId()->toString());
    }

    public function testMapToEntityPreservesCustomerData(): void
    {
        $invoiceModel = $this->makeInvoiceModel(
            customerName: 'Alice Smith',
            customerEmail: 'alice@example.com',
        );
        $invoiceModel->setRelation('productLines', new Collection());

        $invoice = $this->mapper->mapToEntity($invoiceModel);

        $this->assertSame('Alice Smith', $invoice->getCustomerName());
        $this->assertSame('alice@example.com', $invoice->getCustomerEmail());
    }

    #[DataProvider('statusProvider')]
    public function testMapToEntityPreservesStatus(StatusEnum $status): void
    {
        $invoiceModel = $this->makeInvoiceModel(status: $status);
        $invoiceModel->setRelation('productLines', new Collection());

        $invoice = $this->mapper->mapToEntity($invoiceModel);

        $this->assertSame($status, $invoice->getStatus());
    }

    public function testMapToEntityWithNoProductsHasEmptyProductsArray(): void
    {
        $invoiceModel = $this->makeInvoiceModel();
        $invoiceModel->setRelation('productLines', new Collection());

        $invoice = $this->mapper->mapToEntity($invoiceModel);

        $this->assertEmpty($invoice->getProducts());
    }

    public function testMapToEntityWithOneProductMapsCorrectly(): void
    {
        $invoiceModel = $this->makeInvoiceModel();
        $productModel = $this->makeProductLineModel();
        $invoiceModel->setRelation('productLines', new Collection([$productModel]));

        $invoice = $this->mapper->mapToEntity($invoiceModel);

        $this->assertCount(1, $invoice->getProducts());
        $product = $invoice->getProducts()[0];
        $this->assertSame($productModel->id, $product->getId()->toString());
        $this->assertSame($productModel->name, $product->getName());
        $this->assertSame($productModel->price, $product->getPrice());
        $this->assertSame($productModel->quantity, $product->getQuantity());
    }

    public function testMapToEntityWithMultipleProductsMapsAll(): void
    {
        $invoiceModel = $this->makeInvoiceModel();
        $productModels = [
            $this->makeProductLineModel(name: 'Widget A', price: 100, quantity: 2),
            $this->makeProductLineModel(name: 'Widget B', price: 200, quantity: 1),
            $this->makeProductLineModel(name: 'Widget C', price: 50, quantity: 5),
        ];
        $invoiceModel->setRelation('productLines', new Collection($productModels));

        $invoice = $this->mapper->mapToEntity($invoiceModel);

        $this->assertCount(3, $invoice->getProducts());
        $this->assertSame('Widget A', $invoice->getProducts()[0]->getName());
        $this->assertSame('Widget B', $invoice->getProducts()[1]->getName());
        $this->assertSame('Widget C', $invoice->getProducts()[2]->getName());
    }

    public static function statusProvider(): array
    {
        return array_map(fn(StatusEnum $s) => [$s], StatusEnum::cases());
    }

    // --- helpers ---

    private function makeInvoice(
        string $customerName = 'John Doe',
        string $customerEmail = 'john@example.com',
        StatusEnum $status = StatusEnum::Draft,
    ): Invoice {
        return new Invoice(
            id: Uuid::uuid4(),
            customerName: $customerName,
            customerEmail: $customerEmail,
            status: $status,
        );
    }

    private function makeProductLine(
        string $name = 'Widget',
        int $price = 100,
        int $quantity = 1,
    ): InvoiceProductLine {
        return new InvoiceProductLine(
            id: Uuid::uuid4(),
            name: $name,
            price: $price,
            quantity: $quantity,
        );
    }

    private function makeInvoiceModel(
        string $customerName = 'John Doe',
        string $customerEmail = 'john@example.com',
        StatusEnum $status = StatusEnum::Draft,
    ): InvoiceModel {
        $model = new InvoiceModel();
        $model->id = Uuid::uuid4()->toString();
        $model->setAttribute('customer_name', $customerName);
        $model->setAttribute('customer_email', $customerEmail);
        $model->setAttribute('status', $status->value);

        return $model;
    }

    private function makeProductLineModel(
        string $name = 'Widget',
        int $price = 100,
        int $quantity = 1,
    ): InvoiceProductLineModel {
        $model = new InvoiceProductLineModel();
        $model->id = Uuid::uuid4()->toString();
        $model->setAttribute('name', $name);
        $model->setAttribute('price', $price);
        $model->setAttribute('quantity', $quantity);

        return $model;
    }
}
