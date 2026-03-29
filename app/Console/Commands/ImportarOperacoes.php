<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\{Cliente, Operacao, Parcela};
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Carbon\Carbon;

class ImportarOperacoes extends Command
{
    protected $signature = 'operacoes:importar
                            {--arquivo=operacoes.xlsx}';
    protected $description = 'Importa operacoes financeiras a partir do arquivo Excel';

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        $arquivo = storage_path('app/imports/' . $this->option('arquivo'));

        if (!file_exists($arquivo)) {
            $this->error('Arquivo nao encontrado: ' . $arquivo);
            return;
        }

        $this->info('Carregando planilha...');

        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($arquivo);
        $sheet       = $spreadsheet->getActiveSheet();

        $this->info('Processando registros...');

        $chunk    = [];
        $total    = 0;
        $erros    = 0;
        $primeiraLinha = true;

        foreach ($sheet->getRowIterator() as $row) {
            if ($primeiraLinha) {
                $primeiraLinha = false;
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[$cell->getColumn()] = $cell->getValue();
            }

            // Converter para array indexado por posicao (0-based)
            $cols = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T'];
            $linha = [];
            foreach ($cols as $col) {
                $linha[] = $rowData[$col] ?? null;
            }

            $chunk[] = $linha;
            $total++;

            if (count($chunk) >= 500) {
                $this->processarChunk($chunk, $erros);
                $chunk = [];
                $this->info("Processados: {$total} registros...");
            }
        }

        // Processar restante
        if (!empty($chunk)) {
            $this->processarChunk($chunk, $erros);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->info("Importacao concluida! Total: {$total} registros.");
        if ($erros) $this->warn("{$erros} chunks com erro.");
    }

    private function processarChunk(array $chunk, int &$erros): void
    {
        try {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $row) {
                    $this->processarLinha($row);
                }
            });
        } catch (\Exception $e) {
            $erros++;
            $this->error('Erro no chunk: ' . $e->getMessage());
        }
    }

    private function processarLinha(array $row): void
    {
        $cliente = Cliente::firstOrCreate(
            ['cpf' => (string)($row[15] ?? '')],
            [
                'nome'    => (string)($row[16] ?? ''),
                'dt_nasc' => $this->parseDate($row[17]),
                'sexo'    => (string)($row[18] ?? ''),
                'email'   => (string)($row[19] ?? ''),
            ]
        );

        $operacao = Operacao::create([
            'cliente_id'           => $cliente->id,
            'conveniada_id'        => (int)($row[10] ?? 1),
            'valor_requerido'      => (float)($row[0] ?? 0),
            'valor_desembolso'     => (float)($row[1] ?? 0),
            'total_juros'          => (float)($row[2] ?? 0),
            'taxa_juros'           => (float)($row[3] ?? 0),
            'taxa_multa'           => (float)($row[4] ?? 0),
            'taxa_mora'            => (float)($row[5] ?? 0),
            'status_id'            => (int)($row[6] ?? 1),
            'produto'              => (string)($row[9] ?? ''),
            'data_criacao'         => $this->parseDate($row[7]),
            'data_pagamento'       => $this->parseDate($row[8]),
            'assinatura_concluida' => (int)($row[6] ?? 0) >= 5,
        ]);

        $qtd       = (int)($row[11] ?? 0);
        $dataBase  = Carbon::parse($this->parseDate($row[12]) ?? now());
        $valorParc = (float)($row[13] ?? 0);
        $pagas     = (int)($row[14] ?? 0);

        if ($qtd <= 0) return;

        $parcelas = [];
        for ($i = 1; $i <= $qtd; $i++) {
            $parcelas[] = [
                'operacao_id'     => $operacao->id,
                'numero'          => $i,
                'data_vencimento' => $dataBase->copy()->addDays(($i - 1) * 30)->toDateString(),
                'valor'           => $valorParc,
                'status'          => $i <= $pagas ? 'PAGO' : 'PENDENTE',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        Parcela::insert($parcelas);
    }

    private function parseDate($value): ?string
    {
        if (!$value) return null;
        try {
            if ($value instanceof \DateTime) return $value->format('Y-m-d');
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}