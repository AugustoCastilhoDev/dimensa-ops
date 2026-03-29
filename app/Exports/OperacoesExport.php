<?php

namespace App\Exports;

use App\Models\Operacao;
use App\Services\ValorPresenteService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class OperacoesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    public function __construct(
        private array $filtros = [],
        private ?Carbon $hoje = null
    ) {
        $this->hoje = $hoje ?? now();
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function query()
    {
        return Operacao::with(['cliente', 'conveniada'])
            ->when($this->filtros['status'] ?? null,
                fn($q, $v) => $q->where('status_id', $v))
            ->when($this->filtros['produto'] ?? null,
                fn($q, $v) => $q->where('produto', $v))
            ->when($this->filtros['conveniada'] ?? null,
                fn($q, $v) => $q->where('conveniada_id', $v));
    }

    public function headings(): array
    {
        return [
            'Codigo',
            'Cliente',
            'CPF',
            'Valor Requerido',
            'Status',
            'Produto',
            'Conveniada',
            'Valor Presente (VP)',
        ];
    }

    public function map($op): array
    {
        // Calcular VP diretamente via query para nao carregar todas as parcelas em memoria
        $hoje  = $this->hoje;
        $vp    = 0;

        $parcelas = \App\Models\Parcela::where('operacao_id', $op->id)->get();

        foreach ($parcelas as $p) {
            $v    = (float) $p->valor;
            $venc = Carbon::parse($p->data_vencimento);
            $d    = $hoje->diffInDays($venc, false);

            if ($d < 0) {
                $dias  = abs($d);
                $multa = (float) $op->taxa_multa / 100;
                $mora  = (float) $op->taxa_mora  / 100;
                $vp   += $v + ($v * $multa) + ($v * ($mora / 30) * $dias);
            } elseif ($d > 0) {
                $i   = (float) $op->taxa_juros / 100;
                $vp += $i > 0 ? $v / pow(1 + $i, $d / 30) : $v;
            } else {
                $vp += $v;
            }
        }

        return [
            $op->codigo,
            $op->cliente->nome  ?? '-',
            $op->cliente->cpf   ?? '-',
            number_format($op->valor_requerido, 2, ',', '.'),
            Operacao::STATUS[$op->status_id] ?? '?',
            $op->produto,
            $op->conveniada->nome ?? '-',
            number_format($vp, 2, ',', '.'),
        ];
    }
}
