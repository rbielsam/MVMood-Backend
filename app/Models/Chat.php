<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{

    protected $fillable = ['id', 'nombre'];
    public $incrementing = false;
    protected $keyType = 'string';


    protected static function boot() {
        parent::boot();
        //static::creating(fn($model) => $model->uuid = (string) \Illuminate\Support\Str::uuid());
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function usuarios(): BelongsToMany {
        //return $this->belongsToMany(User::class,'chat_user');
        return $this->belongsToMany(User::class,'chat_user', 'chat_id', 'user_id');
    }

    public function mensajes(): HasMany {
        return $this->hasMany(Mensaje::class);
    }
}