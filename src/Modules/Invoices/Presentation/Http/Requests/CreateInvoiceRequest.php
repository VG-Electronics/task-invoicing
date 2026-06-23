<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_email'         => ['required', 'email', 'max:255'],
            'products'               => ['nullable', 'array'],
            'products.*.name'        => ['required', 'string', 'max:255'],
            'products.*.price'       => ['required', 'integer', 'min:1'],
            'products.*.quantity'    => ['required', 'integer', 'min:1'],
        ];
    }
}
