<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Invoices\Domain\Enums\StatusEnum;

/**
 * @property string $id
 * @property string $customer_name
 * @property string $customer_email
 * @property StatusEnum $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property InvoiceProductLineModel[]|Collection $productLines
 */
class InvoiceModel extends Model
{
    use HasUuids;

    protected $table = 'invoices';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'status',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
    ];

    public function productLines(): HasMany
    {
        return $this->hasMany(InvoiceProductLineModel::class, 'invoice_id');
    }
}
