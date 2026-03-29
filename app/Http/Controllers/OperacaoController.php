<?php

namespace App\Http\Controllers;

use App\Models\Operacao;
use App\Models\Conveniada;
use App\Models\StatusLog;
use App\Services\ValorPresenteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OperacoesExport;

class OperacaoController extends Controller
{
    public function index(Request $request)
    {
        $operacoes = Operacao::with(['cliente', 'conveniada'])
            ->when($request->filled('status'),
                fn($q) => $q->where('status_id', $request->status))
            ->when($request->filled('produto'),
                fn($q) => $q->where('produto', $request->produto))
            ->when($request->filled('conveniada'),
                fn($q) => $q->where('conveniada_id', $request->conveniada))
            ->when($request->filled('operacao'),
                fn($q) => $q->where('codigo', 'like', '%'.$request->operacao.'%'))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $conveniadas = Conveniada::orderBy('nome')->get();

        return view('operacoes.index', [
            'operacoes'   => $operacoes,
            'conveniadas' => $conveniadas,
            'statusList'  => Operacao::STATUS,
            'filtros'     => $request->only(['status','produto','conveniada','operacao']),
        ]);
    }

    public function show(Operacao $operacao)
    {
        $operacao->load(['cliente', 'conveniada', 'parcelas', 'logs.user']);

        $hoje = now();
        $svc  = new ValorPresenteService();

        $parcelas = $operacao->parcelas->map(function ($p) use ($svc, $operacao, $hoje) {
            $p->valor_presente = $svc->calcular($p, $operacao, $hoje);
            return $p;
        });

        $transicoesValidas = collect(Operacao::TRANSICOES[$operacao->status_id] ?? [])
            ->mapWithKeys(fn($id) => [$id => Operacao::STATUS[$id]]);

        return view('operacoes.show', [
            'operacao'          => $operacao,
            'parcelas'          => $parcelas,
            'transicoesValidas' => $transicoesValidas,
        ]);
    }

    public function updateStatus(Request $request, Operacao $operacao)
    {
        if (empty(Operacao::TRANSICOES[$operacao->status_id])) {
            return back()->with('error', 'Esta operacao nao pode mais ser alterada.');
        }

        $novoStatus = (int) $request->status_id;

        if (!in_array($novoStatus, Operacao::TRANSICOES[$operacao->status_id])) {
            return back()->with('error', 'Transicao de status invalida.');
        }

        if ($novoStatus === 8 && !$operacao->assinatura_concluida) {
            return back()->with('error', 'A operacao precisa ter passado por Assinatura Concluida antes de ser paga.');
        }

        DB::transaction(function () use ($operacao, $novoStatus) {
            $anterior = $operacao->status_id;

            if ($novoStatus === 5) {
                $operacao->assinatura_concluida = true;
            }

            if ($novoStatus === 8) {
                $operacao->data_pagamento = now()->toDateString();
            }

            $operacao->status_id = $novoStatus;
            $operacao->save();

            StatusLog::create([
                'operacao_id'     => $operacao->id,
                'status_anterior' => $anterior,
                'status_novo'     => $novoStatus,
                'user_id'         => auth()->id(),
            ]);
        });

        return back()->with('success', 'Status atualizado com sucesso!');
    }

    public function exportar(Request $request)
{
    ini_set('memory_limit', '512M');
    set_time_limit(300);

    $filtros  = $request->only(['status', 'produto', 'conveniada', 'operacao']);
    $filename = 'operacoes-' . now()->format('Ymd-His') . '.csv';

    $headers = [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        'X-Accel-Buffering'   => 'no',
        'Cache-Control'       => 'no-cache',
    ];

    $callback = function () use ($filtros) {
        $handle = fopen('php://output', 'w');
        fputs($handle, "\xEF\xBB\xBF"); // BOM para Excel abrir UTF-8 corretamente

        // Cabecalho
        fputcsv($handle, [
            'Codigo', 'Cliente', 'CPF', 'Valor Requerido',
            'Status', 'Produto', 'Conveniada', 'Valor Presente (VP)'
        ], ';');

        $hoje = now();

        Operacao::with(['cliente', 'conveniada', 'parcelas'])
            ->when($filtros['status'] ?? null,
                fn($q, $v) => $q->where('status_id', $v))
            ->when($filtros['produto'] ?? null,
                fn($q, $v) => $q->where('produto', $v))
            ->when($filtros['conveniada'] ?? null,
                fn($q, $v) => $q->where('conveniada_id', $v))
            ->chunk(500, function ($operacoes) use ($handle, $hoje) {
                foreach ($operacoes as $op) {
                    $vp = 0;
                    foreach ($op->parcelas as $p) {
                        $v    = (float) $p->valor;
                        $venc = \Carbon\Carbon::parse($p->data_vencimento);
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

                    fputcsv($handle, [
                        $op->codigo,
                        $op->cliente->nome  ?? '-',
                        $op->cliente->cpf   ?? '-',
                        number_format($op->valor_requerido, 2, ',', '.'),
                        Operacao::STATUS[$op->status_id] ?? '?',
                        $op->produto,
                        $op->conveniada->nome ?? '-',
                        number_format($vp, 2, ',', '.'),
                    ], ';');
                }
            });

        fclose($handle);
    };

    return response()->stream($callback, 200, $headers);
}
}