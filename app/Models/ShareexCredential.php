<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ShareexCredential extends Authenticatable
{
    use HasFactory;

    protected $table = "shareex_credentials";

    protected $fillable = [
        "shop_id",
        "base_url",
        "api_username",
        "api_password",
    ];

    // Automatically encrypt these attributes when setting them
    // And decrypt when getting them.
    // Note: Laravel's built-in encrypted casting might not be suitable if you need to query by username.
    // For this use case, manual encryption/decryption or a different strategy might be better for username if it needs to be searchable.
    // However, for simplicity and consistency with password, we'll encrypt username and base_url as well.
    // If base_url or username needs to be partially visible or queried, consider storing them differently or using a dedicated encryption package.

    protected $casts = [
        'password' => 'hashed',
        // Laravel 9+ has built-in encrypted casting, but it's often better to handle explicitly for clarity
        // especially if you need to support older Laravel versions or have specific encryption needs.
        // For now, we will handle encryption/decryption via accessors/mutators for broader compatibility and control.
    ];

    // Mutator for base_url
//    public function setBaseUrlAttribute($value)
//    {
//        try {
//            $this->attributes["base_url"] = Crypt::encryptString($value);
//        } catch (EncryptException $e) {
//            // Handle encryption failure, perhaps log it or throw a custom exception
//            $this->attributes["base_url"] = null; // Or some default/error state
//        }
//    }
//
//    // Accessor for base_url
//    public function getBaseUrlAttribute($value)
//    {
//        try {
//            return $value ? Crypt::decryptString($value) : null;
//        } catch (DecryptException $e) {
//            // Handle decryption failure
//            return null; // Or some default/error state
//        }
//    }
//
//    // Mutator for api_username
//    public function setApiUsernameAttribute($value)
//    {
//        try {
//            $this->attributes["api_username"] = Crypt::encryptString($value);
//        } catch (EncryptException $e) {
//            $this->attributes["api_username"] = null;
//        }
//    }
//
//    // Accessor for api_username
//    public function getApiUsernameAttribute($value)
//    {
//        try {
//            return $value ? Crypt::decryptString($value) : null;
//        } catch (DecryptException $e) {
//            return null;
//        }
//    }
//
//    // Mutator for api_password
//    public function setApiPasswordAttribute($value)
//    {
//        try {
//            $this->attributes["api_password"] = Crypt::encryptString($value);
//        } catch (EncryptException $e) {
//            $this->attributes["api_password"] = null;
//        }
//    }
//
//    // Accessor for api_password
//    public function getApiPasswordAttribute($value)
//    {
//        try {
//            return $value ? Crypt::decryptString($value) : null;
//        } catch (DecryptException $e) {
//            // Important: Do not return the encrypted string on failure. Return null or throw.
//            return null;
//        }
//    }

    /**
     * Get the shop that owns the Shareex credential.
     */
    public function shop()
    {
        // kyon147/laravel-shopify uses User model as Shop model
        return $this->belongsTo(User::class, "shop_id");
    }
}

