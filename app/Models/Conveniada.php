<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conveniada extends Model
{
    protected $table = 'conveniadas';

    protected $fillable = ['nome'];

    public function operacoes()
    {
        return $this->hasMany(Operacao::class);
    }
}