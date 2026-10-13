<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferAcceptance extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['accepted_at' => 'datetime'];
}
