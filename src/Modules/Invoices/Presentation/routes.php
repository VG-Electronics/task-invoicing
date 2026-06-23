<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\InvoiceController;
use Ramsey\Uuid\Validator\GenericValidator;

Route::pattern('id', (new GenericValidator)->getPattern());

Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
Route::post('/invoices/{id}/send', [InvoiceController::class, 'send'])->name('invoices.send');
