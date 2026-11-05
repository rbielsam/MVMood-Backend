<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'rol', 'used_at'])]
class UserWhitelist extends Model
{
    protected $table = 'user_whitelist';

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
