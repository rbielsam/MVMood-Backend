<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'publicacion_id'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function publicacion(){
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }
}
