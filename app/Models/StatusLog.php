<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusLog extends Model
{
    protected $table = 'status_logs';

    protected $fillable = [
        'operacao_id',
        'status_anterior',
        'status_novo',
        'user_id',
    ];

    public function operacao()
    {
        return $this->belongsTo(Operacao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAnteriorLabelAttribute(): string
    {
        return Operacao::STATUS[$this->status_anterior] ?? '?';
    }

    public function getStatusNovoLabelAttribute(): string
    {
        return Operacao::STATUS[$this->status_novo] ?? '?';
    }
}