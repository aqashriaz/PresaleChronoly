<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PreferredTokenBuy extends Model
{
    protected $fillable = [
        'user_id', 'currency', 'amount',
    ];
}
