<?php

namespace App\Services;

use App\Models\{Operacao, Parcela};
use Carbon\Carbon;

class ValorPresenteService
{
    public function calcular(Parcela $parcela, Operacao $op, Carbon $hoje): float
    {
        $v    = (float) $parcela->valor;
        $venc = Carbon::parse($parcela->data_vencimento);
        $d    = $hoje->diffInDays($venc, false);

        if ($d < 0) {
            // Parcela atrasada
            $dias  = abs($d);
            $multa = (float) $op->taxa_multa / 100;
            $mora  = (float) $op->taxa_mora  / 100;
            return $v + ($v * $multa) + ($v * ($mora / 30) * $dias);
        }

        if ($d > 0) {
            // Parcela futura (adiantamento)
            $i = (float) $op->taxa_juros / 100;
            if ($i <= 0) return $v;
            return $v / pow(1 + $i, $d / 30);
        }

        return $v; // vence hoje
    }

    public function calcularOperacao(Operacao $op, Carbon $hoje): float
    {
        return $op->parcelas->sum(
            fn($p) => $this->calcular($p, $op, $hoje)
        );
    }
}