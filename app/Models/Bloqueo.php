<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Bloqueo extends Model
{
    use HasUuids;
    protected $fillable = ['bloqueador_id', 'bloqueado_id'];

    public function bloqueador(){
        return $this->belongsTo(User::class, 'bloqueador_id');
    }

    public function bloqueado(){
        return $this->belongsTo(User::class, 'bloqueado_id');
    }
}
