<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcela extends Model
{
    protected $table = 'parcelas';

    protected $fillable = [
        'operacao_id',
        'numero',
        'data_vencimento',
        'valor',
        'status',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
    ];

    public function operacao()
    {
        return $this->belongsTo(Operacao::class);
    }
}