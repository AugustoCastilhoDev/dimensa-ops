@extends('layouts.app')
@section('title', 'Esteira de Operacoes')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Esteira de Operacoes</h4>
    <a href="{{ route('operacoes.exportar', request()->query()) }}"
       class="btn btn-success btn-sm">
        Exportar Excel
    </a>
</div>

{{-- Filtros --}}
<form method="GET" class="row g-2 mb-4 bg-white p-3 rounded shadow-sm">
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">Todos os Status</option>
            @foreach($statusList as $id => $label)
                <option value="{{ $id }}" {{ request('status') == $id ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
    <select name="produto" class="form-select form-select-sm">
        <option value="">Todos os Produtos</option>
        <option value="CONSIGNADO" {{ request('produto') == 'CONSIGNADO' ? 'selected' : '' }}>
            Consignado
        </option>
        <option value="NAO_CONSIGNADO" {{ request('produto') == 'NAO_CONSIGNADO' ? 'selected' : '' }}>
            Nao Consignado
        </option>
    </select>
</div>
    <div class="col-md-3">
        <select name="conveniada" class="form-select form-select-sm">
            <option value="">Todas as Conveniadas</option>
            @foreach($conveniadas as $c)
                <option value="{{ $c->id }}" {{ request('conveniada') == $c->id ? 'selected' : '' }}>
                    {{ $c->nome }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <input type="text" name="operacao" class="form-control form-control-sm"
               placeholder="Codigo da operacao" value="{{ request('operacao') }}">
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary btn-sm flex-fill">Filtrar</button>
        <a href="{{ route('operacoes.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
    </div>
</form>

{{-- Tabela --}}
<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover table-striped mb-0 align-middle">
        <thead class="table-dark">
            <tr>
                <th>Codigo</th>
                <th>Cliente</th>
                <th>CPF</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Produto</th>
                <th>Conveniada</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($operacoes as $op)
            <tr>
                <td><small class="text-muted">{{ $op->codigo }}</small></td>
                <td>{{ $op->cliente->nome }}</td>
                <td><small>{{ $op->cliente->cpf }}</small></td>
                <td>R$ {{ number_format($op->valor_requerido, 2, ',', '.') }}</td>
                <td>
                    <span class="badge badge-status-{{ $op->status_id }}">
                        {{ $op->status_label }}
                    </span>
                </td>
                <td>{{ $op->produto }}</td>
                <td>{{ $op->conveniada->nome }}</td>
                <td>
                    <a href="{{ route('operacoes.show', $op) }}"
                       class="btn btn-sm btn-outline-primary">Ver</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    Nenhuma operacao encontrada.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">
        Mostrando {{ $operacoes->firstItem() }} a {{ $operacoes->lastItem() }} 
        de {{ $operacoes->total() }} operacoes
    </small>
    {{ $operacoes->links() }}
</div>

@endsection