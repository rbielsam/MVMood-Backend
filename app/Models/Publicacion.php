<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'contenido', 'imagen'])]

class Publicacion extends Model
{
    use HasUuids;
    protected $table = 'publicacion';

    public function comentarios() {
        return $this->hasMany(Comentario::class)->orderBy('created_at','desc');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'publicacion_id');
    }
}
