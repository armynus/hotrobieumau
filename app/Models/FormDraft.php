<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormDraft extends Model
{
    protected $table = 'form_drafts';
    protected $fillable = ['user_id', 'form_key', 'payload'];
    protected $casts = ['payload' => 'array'];
    public $timestamps = true;

}
