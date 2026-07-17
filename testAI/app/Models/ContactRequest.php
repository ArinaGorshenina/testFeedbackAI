<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'comment',
        'sentiment', 'category', 'ai_summary', 'ai_available', 'ip',
    ];

    protected $casts = [
        'ai_available' => 'boolean',
    ];
}
