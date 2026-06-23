<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Http;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceNotDraftException;
use Modules\Invoices\Domain\Exceptions\NoProductsException;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceProductLineModel;
use RuntimeException;
use Tests\TestCase;

final class InvoiceControllerTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        $this->setUpFaker();
        parent::setUp();
    }

    // =========================================================================
    // POST /invoices
    // =========================================================================

    public function testStoreReturns201WithValidDataAndProducts(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['name' => 'Widget', 'price' => 100, 'quantity' => 2],
            ],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertCreated();
    }

    public function testStoreReturns201WithEmptyProductsArray(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertCreated();
    }

    public function testStoreCreatesInvoiceInDatabase(): void
    {
        $payload = [
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'products'       => [],
        ];

        $this->postJson(route('invoices.store'), $payload);

        $this->assertDatabaseHas('invoices', [
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'status'         => StatusEnum::Draft->value,
        ]);
    }

    public function testStoreCreatesProductLinesInDatabase(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['name' => 'Widget A', 'price' => 150, 'quantity' => 3],
                ['name' => 'Widget B', 'price' => 200, 'quantity' => 1],
            ],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('invoice_product_lines', ['name' => 'Widget A', 'price' => 150, 'quantity' => 3]);
        $this->assertDatabaseHas('invoice_product_lines', ['name' => 'Widget B', 'price' => 200, 'quantity' => 1]);
    }

    public function testStoreFailsWhenCustomerNameIsMissing(): void
    {
        $payload = [
            'customer_email' => $this->faker->email(),
            'products'       => [],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name']);
    }

    public function testStoreFailsWhenCustomerEmailIsMissing(): void
    {
        $payload = [
            'customer_name' => $this->faker->name(),
            'products'      => [],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function testStoreFailsWhenEmailFormatIsInvalid(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => 'not-an-email',
            'products'       => [],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function testStoreFailsWhenProductPriceIsBelowOne(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['name' => 'Free thing', 'price' => 0, 'quantity' => 1],
            ],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['products.0.price']);
    }

    public function testStoreFailsWhenProductQuantityIsBelowOne(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['name' => 'Widget', 'price' => 100, 'quantity' => 0],
            ],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['products.0.quantity']);
    }

    public function testStoreFailsWhenProductNameIsMissing(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['price' => 100, 'quantity' => 1],
            ],
        ];

        $this->postJson(route('invoices.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['products.0.name']);
    }

    // =========================================================================
    // GET /invoices/{id}
    // =========================================================================

    public function testShowReturnsCorrectJsonStructure(): void
    {
        $invoice = $this->createInvoice();
        $this->createProductLine($invoice->id, name: 'Widget', price: 200, quantity: 3);

        $this->getJson(route('invoices.show', ['id' => $invoice->id]))
            ->assertOk()
            ->assertJsonStructure([
                'invoice_id',
                'invoice_status',
                'customer_name',
                'customer_email',
                'invoice_product_lines' => [
                    '*' => ['product_name', 'quantity', 'unit_price', 'total_unit_price'],
                ],
                'total_price',
            ]);
    }

    public function testShowReturnsCorrectInvoiceData(): void
    {
        $invoice = $this->createInvoice(customerName: 'Alice', customerEmail: 'alice@example.com');

        $this->getJson(route('invoices.show', ['id' => $invoice->id]))
            ->assertOk()
            ->assertJsonFragment([
                'invoice_id'     => $invoice->id,
                'invoice_status' => StatusEnum::Draft->value,
                'customer_name'  => 'Alice',
                'customer_email' => 'alice@example.com',
            ]);
    }

    public function testShowCalculatesCorrectTotalPrice(): void
    {
        $invoice = $this->createInvoice();
        $this->createProductLine($invoice->id, price: 100, quantity: 2);
        $this->createProductLine($invoice->id, price: 50, quantity: 4);

        $this->getJson(route('invoices.show', ['id' => $invoice->id]))
            ->assertOk()
            ->assertJsonFragment(['total_price' => 400]);
    }

    public function testShowReturnsNotFoundForInvalidUuidFormat(): void
    {
        $this->getJson('/api/invoices/not-a-uuid')
            ->assertNotFound();
    }

    public function testShowThrowsForNonExistentInvoice(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);

        $this->getJson(route('invoices.show', ['id' => $this->faker->uuid()]));
    }

    // =========================================================================
    // PUT /invoices/{id}
    // =========================================================================

    public function testUpdateReturns200WithValidData(): void
    {
        $invoice = $this->createInvoice();

        $payload = [
            'customer_name'  => 'Updated Name',
            'customer_email' => 'updated@example.com',
            'products'       => [
                ['name' => 'New Product', 'price' => 300, 'quantity' => 1],
            ],
        ];

        $this->putJson(route('invoices.update', ['id' => $invoice->id]), $payload)
            ->assertOk();
    }

    public function testUpdatePersistsChangesToDatabase(): void
    {
        $invoice = $this->createInvoice(customerName: 'Old Name', customerEmail: 'old@example.com');

        $payload = [
            'customer_name'  => 'New Name',
            'customer_email' => 'new@example.com',
            'products'       => [],
        ];

        $this->putJson(route('invoices.update', ['id' => $invoice->id]), $payload);

        $this->assertDatabaseHas('invoices', [
            'id'             => $invoice->id,
            'customer_name'  => 'New Name',
            'customer_email' => 'new@example.com',
        ]);
    }

    public function testUpdateRemovesOldProductLinesAndAddsNew(): void
    {
        $invoice = $this->createInvoice();
        $oldProduct = $this->createProductLine($invoice->id, name: 'Old Product');

        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['name' => 'New Product', 'price' => 100, 'quantity' => 1],
            ],
        ];

        $this->putJson(route('invoices.update', ['id' => $invoice->id]), $payload);

        $this->assertDatabaseMissing('invoice_product_lines', ['id' => $oldProduct->id]);
        $this->assertDatabaseHas('invoice_product_lines', ['name' => 'New Product']);
    }

    public function testUpdateKeepsExistingProductLineWhenIdProvided(): void
    {
        $invoice = $this->createInvoice();
        $existingProduct = $this->createProductLine($invoice->id, name: 'Original');

        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [
                ['id' => $existingProduct->id, 'name' => 'Renamed', 'price' => 99, 'quantity' => 2],
            ],
        ];

        $this->putJson(route('invoices.update', ['id' => $invoice->id]), $payload);

        $this->assertDatabaseHas('invoice_product_lines', ['id' => $existingProduct->id, 'name' => 'Renamed']);
    }

    public function testUpdateFailsWhenCustomerEmailIsInvalid(): void
    {
        $invoice = $this->createInvoice();

        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => 'bad-email',
            'products'       => [],
        ];

        $this->putJson(route('invoices.update', ['id' => $invoice->id]), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function testUpdateFailsWhenCustomerNameIsMissing(): void
    {
        $invoice = $this->createInvoice();

        $payload = [
            'customer_email' => $this->faker->email(),
            'products'       => [],
        ];

        $this->putJson(route('invoices.update', ['id' => $invoice->id]), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name']);
    }

    public function testUpdateReturnsNotFoundForInvalidUuidFormat(): void
    {
        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
        ];

        $this->putJson('/api/invoices/not-a-uuid', $payload)
            ->assertNotFound();
    }

    public function testUpdateThrowsForNonExistentInvoice(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);

        $payload = [
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'products'       => [],
        ];

        $this->putJson(route('invoices.update', ['id' => $this->faker->uuid()]), $payload);
    }

    // =========================================================================
    // POST /invoices/{id}/send
    // =========================================================================

    public function testSendReturnsOkAndChangesStatusToSending(): void
    {
        $invoice = $this->createInvoice();
        $this->createProductLine($invoice->id);

        $this->postJson(route('invoices.send', ['id' => $invoice->id]))
            ->assertOk();

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => StatusEnum::Sending->value,
        ]);
    }

    public function testSendReturnsNotFoundForInvalidUuidFormat(): void
    {
        $this->postJson('/api/invoices/not-a-uuid/send')
            ->assertNotFound();
    }

    public function testSendThrowsForNonExistentInvoice(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);

        $this->postJson(route('invoices.send', ['id' => $this->faker->uuid()]));
    }

    public function testSendThrowsWhenInvoiceIsAlreadySending(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvoiceNotDraftException::class);

        $invoice = $this->createInvoice(status: StatusEnum::Sending);
        $this->createProductLine($invoice->id);

        $this->postJson(route('invoices.send', ['id' => $invoice->id]));
    }

    public function testSendThrowsWhenInvoiceIsSentToClient(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvoiceNotDraftException::class);

        $invoice = $this->createInvoice(status: StatusEnum::SentToClient);
        $this->createProductLine($invoice->id);

        $this->postJson(route('invoices.send', ['id' => $invoice->id]));
    }

    public function testSendThrowsWhenInvoiceHasNoProducts(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(NoProductsException::class);

        $invoice = $this->createInvoice();

        $this->postJson(route('invoices.send', ['id' => $invoice->id]));
    }

    // =========================================================================
    // helpers
    // =========================================================================

    private function createInvoice(
        string $customerName = 'John Doe',
        string $customerEmail = 'john@example.com',
        StatusEnum $status = StatusEnum::Draft,
    ): InvoiceModel {
        $model = new InvoiceModel([
            'customer_name'  => $customerName,
            'customer_email' => $customerEmail,
            'status'         => $status,
        ]);
        $model->save();

        return $model;
    }

    private function createProductLine(
        string $invoiceId,
        string $name = 'Test Product',
        int $price = 100,
        int $quantity = 1,
    ): InvoiceProductLineModel {
        $model = new InvoiceProductLineModel([
            'invoice_id' => $invoiceId,
            'name'       => $name,
            'price'      => $price,
            'quantity'   => $quantity,
        ]);
        $model->save();

        return $model;
    }
}
