<?php

namespace PaymentSystem\Laravel\Nuvei\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property-read UuidInterface $id
 * @property string $merchant_id
 * @property string $site_id
 * @property string $secret_key
 */
class Credentials extends Model
{
    use HasUuids;

    protected $table = 'nuvei_credentials';

    protected $fillable = [
        'merchant_id',
        'site_id',
        'secret_key',
    ];

    protected $casts = [
        'merchant_id' => 'encrypted',
        'site_id' => 'encrypted',
        'secret_key' => 'encrypted',
    ];
}