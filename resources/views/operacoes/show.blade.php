@extends('layouts.app')
@section('title', 'Detalhe da Operacao')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Operacao: {{ $operacao->codigo }}</h4>
    <a href="{{ route('operacoes.index') }}" class="btn btn-outline-secondary btn-sm">
        Voltar
    </a>
</div>

<div class="row g-4">

    {{-- Dados do Cliente --}}
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-bold">Dados do Cliente</div>
            <div class="card-body">
                <p><strong>Nome:</strong> {{ $operacao->cliente->nome }}</p>
                <p><strong>CPF:</strong> {{ $operacao->cliente->cpf }}</p>
                <p><strong>Email:</strong> {{ $operacao->cliente->email }}</p>
                <p><strong>Sexo:</strong> {{ $operacao->cliente->sexo }}</p>
                <p class="mb-0"><strong>Nascimento:</strong>
                    {{ $operacao->cliente->dt_nasc ? \Carbon\Carbon::parse($operacao->cliente->dt_nasc)->format('d/m/Y') : '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Dados da Operacao --}}
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-bold">Dados da Operacao</div>
            <div class="card-body">
                <p><strong>Status:</strong>
                    <span class="badge badge-status-{{ $operacao->status_id }}">
                        {{ $operacao->status_label }}
                    </span>
                </p>
                <p><strong>Produto:</strong> {{ $operacao->produto }}</p>
                <p><strong>Conveniada:</strong> {{ $operacao->conveniada->nome }}</p>
                <p><strong>Valor Requerido:</strong> R$ {{ number_format($operacao->valor_requerido, 2, ',', '.') }}</p>
                <p><strong>Valor Desembolso:</strong> R$ {{ number_format($operacao->valor_desembolso, 2, ',', '.') }}</p>
                <p><strong>Total Juros:</strong> R$ {{ number_format($operacao->total_juros, 2, ',', '.') }}</p>
                <p><strong>Taxa Juros:</strong> {{ $operacao->taxa_juros }}%</p>
                <p><strong>Taxa Multa:</strong> {{ $operacao->taxa_multa }}%</p>
                <p><strong>Taxa Mora:</strong> {{ $operacao->taxa_mora }}%</p>
                <p><strong>Data Criacao:</strong> {{ $operacao->data_criacao ? \Carbon\Carbon::parse($operacao->data_criacao)->format('d/m/Y') : '-' }}</p>
                <p class="mb-0"><strong>Data Pagamento:</strong> {{ $operacao->data_pagamento ? \Carbon\Carbon::parse($operacao->data_pagamento)->format('d/m/Y') : '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Parcelas --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Parcelas</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Valor Presente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parcelas as $p)
                        <tr>
                            <td>{{ $p->numero }}</td>
                            <td>{{ $p->data_vencimento->format('d/m/Y') }}</td>
                            <td>R$ {{ number_format($p->valor, 2, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $p->status === 'PAGO' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $p->status }}
                                </span>
                            </td>
                            <td>R$ {{ number_format($p->valor_presente, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Alterar Status --}}
    @if($transicoesValidas->isNotEmpty())
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Alterar Status</div>
            <div class="card-body">
                <form method="POST"
                      action="{{ route('operacoes.updateStatus', $operacao) }}"
                      class="d-flex gap-2 align-items-center">
                    @csrf
                    @method('PATCH')
                    <select name="status_id" class="form-select">
                        @foreach($transicoesValidas as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary">Salvar</button>
                </form>
            </div>
        </div>
    </div>
    @else
    <div class="col-md-6">
        <div class="alert alert-secondary">
            Esta operacao esta em status final e nao pode ser alterada.
        </div>
    </div>
    @endif

    {{-- Historico --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Historico de Status</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Data</th>
                            <th>De</th>
                            <th>Para</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operacao->logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->status_anterior_label }}</td>
                            <td>{{ $log->status_novo_label }}</td>
                            <td>{{ $log->user->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Nenhuma alteracao registrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
