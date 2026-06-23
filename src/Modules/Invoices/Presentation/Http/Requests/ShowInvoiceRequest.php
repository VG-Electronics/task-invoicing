<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShowInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
