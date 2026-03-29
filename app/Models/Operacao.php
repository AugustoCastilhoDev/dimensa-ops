<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operacao extends Model
{
    protected $table = 'operacoes';

    protected $fillable = [
        'codigo',
        'cliente_id',
        'conveniada_id',
        'valor_requerido',
        'valor_desembolso',
        'total_juros',
        'taxa_juros',
        'taxa_multa',
        'taxa_mora',
        'status_id',
        'produto',
        'data_criacao',
        'data_pagamento',
        'assinatura_concluida',
    ];

    protected $casts = [
        'data_criacao'         => 'date',
        'data_pagamento'       => 'date',
        'assinatura_concluida' => 'boolean',
    ];

    const STATUS = [
        1 => 'DIGITANDO',
        2 => 'PRE-ANALISE',
        3 => 'EM ANALISE',
        4 => 'PARA ASSINATURA',
        5 => 'ASSINATURA CONCLUIDA',
        6 => 'APROVADA',
        7 => 'CANCELADA',
        8 => 'PAGO AO CLIENTE',
    ];

    const TRANSICOES = [
        1 => [2, 7],
        2 => [3, 7],
        3 => [4, 7],
        4 => [5, 7],
        5 => [6, 7],
        6 => [8, 7],
        7 => [],
        8 => [],
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($op) {
            do {
                $codigo = 'OP' . strtoupper(substr(md5(uniqid()), 0, 8));
            } while (self::where('codigo', $codigo)->exists());
            $op->codigo = $codigo;
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status_id] ?? '?';
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conveniada()
    {
        return $this->belongsTo(Conveniada::class);
    }

    public function parcelas()
    {
        return $this->hasMany(Parcela::class)->orderBy('numero');
    }

    public function logs()
    {
        return $this->hasMany(StatusLog::class)->latest();
    }
}