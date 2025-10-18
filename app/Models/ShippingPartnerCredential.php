<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ShippingPartnerCredential extends Authenticatable
{
    use HasFactory;

    protected $table = 'shipping_partner_credentials';

    protected $fillable = [
        'shop_id',
        'base_url',
        'api_username',
        'api_password',
        'password',
        'partner',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'shop_id');
    }
}

