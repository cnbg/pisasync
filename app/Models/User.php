<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 */
class User extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'created' => 'boolean',
        'pdpa' => 'boolean',
        'created_error' => 'json',
        'pdpa_error' => 'json',
    ];
}
