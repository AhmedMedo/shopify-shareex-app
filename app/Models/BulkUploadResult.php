<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkUploadResult extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'customer_id',
        'customer_name',
        'phone',
        'address',
        'area',
        'amount',
        'status',
        'shareex_serial',
        'error_message',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
