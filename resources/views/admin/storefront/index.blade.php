@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Integração com Storefront</h1>
        <div class="btn-group">
            <a href="{{ route('admin.storefront.settings') }}" class="btn btn-outline-primary">Configurações</a>
            <button id="refreshStats" class="btn btn-outline-info">Atualizar</button>
            <button id="clearCache" class="btn btn-outline-warning">Limpar Cache</button>
        </div>
    </div>

    {{-- (Removido) Status da API movido para página de Monitoramento --}}

    {{-- Estatísticas da Storefront --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Total de Clientes</span>
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ number_format($stats['total_customers']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Sessões Ativas</span>
                    <i class="fas fa-eye"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['active_sessions'] }}</h5>
                    <p class="card-text small">Última hora</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white mb-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Pedidos Hoje</span>
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['orders_today'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white mb-3" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Receita Hoje</span>
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">R$ {{ number_format($stats['revenue_today'], 2, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- Métricas de Performance --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white {{ $stats['pending_orders'] > 0 ? 'bg-warning' : 'bg-success' }} mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Pedidos Pendentes</span>
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['pending_orders'] }}</h5>
                    <p class="card-text small">Aguardando pagamento</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Taxa de Conversão</span>
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['conversion_rate'] }}%</h5>
                    <p class="card-text small">Carrinho → Pedido pago</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-dark bg-light mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Ações Rápidas</span>
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary mb-1">Novo Produto</a>
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn btn-sm btn-warning mb-1">Ver Pendentes</a>
                    {{-- Teste de API removido desta página (use Monitoramento) --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Produtos e Pedidos Recentes --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Produtos em Destaque</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm td">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Variantes</th>
                                <th>Imagens</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $product)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.show', $product) }}">
                                            {{ Str::limit($product->name, 25) }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-primary">{{ $product->variants_count }}</span></td>
                                    <td><span class="badge bg-info">{{ $product->computed_images_count ?? $product->images_count }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-xs btn-outline-primary">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum produto encontrado</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pedidos Recentes</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm td">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}">#{{ $order->id }}</a>
                                    </td>
                                    <td>{{ Str::limit($order->user->name, 15) }}</td>
                                    <td>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status === 'paid' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('d/m H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum pedido encontrado</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Limpar cache
    document.getElementById('clearCache').addEventListener('click', function() {
        this.disabled = true;
        this.textContent = 'Limpando...';

        fetch('{{ route("admin.storefront.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache limpo com sucesso!');
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro ao limpar cache');
            console.error(error);
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = 'Limpar Cache';
        });
    });

    // Atualizar estatísticas
    document.getElementById('refreshStats').addEventListener('click', function() {
        location.reload();
    });

    // Status da API removido desta página — consulte Monitoramento
});
</script>
@endpush
