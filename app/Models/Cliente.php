<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'cpf',
        'dt_nasc',
        'sexo',
        'email',
    ];

    public function operacoes()
    {
        return $this->hasMany(Operacao::class);
    }
}