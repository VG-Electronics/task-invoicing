<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $invoice_id
 * @property string $name
 * @property int $price
 * @property int $quantity
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property InvoiceModel $invoice
 */
class InvoiceProductLineModel extends Model
{
    use HasUuids;

    protected $table = 'invoice_product_lines';

    protected $fillable = [
        'invoice_id',
        'name',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }
}
