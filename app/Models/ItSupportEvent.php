<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItSupportEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_type', 'actor_id', 'actor_name', 'from_status', 'to_status', 'message'];

    protected $casts = ['created_at' => 'datetime'];
}
