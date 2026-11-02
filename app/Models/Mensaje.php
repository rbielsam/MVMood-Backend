<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Mensaje extends Model
{
    protected $fillable = ['uuid','chat_id','emisor_id','contenido'];

    protected static function boot() {
        parent::boot();
        static::creating(fn($model) => $model->uuid = (string) Str::uuid());
    }

    public function chat(){
        return $this->belongsTo(Chat::class);
    }
    
    public function emisor(){
        return $this->belongsTo(User::class, 'emisor_id');
    }
}
