@extends('layouts.admin')

@section('content')
<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 dashboard-header">
        <div>
            <h1 class="mb-1">Dashboard</h1>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Bem-vindo de volta! Aqui está o resumo do seu negócio.</p>
        </div>
        <div class="btn-group period-selector" role="group">
            <a href="{{ route('admin.dashboard', ['period' => '7d']) }}" class="btn btn-outline-primary {{ $period == '7d' ? 'active' : '' }}">7 Dias</a>
            <a href="{{ route('admin.dashboard', ['period' => '30d']) }}" class="btn btn-outline-primary {{ $period == '30d' ? 'active' : '' }}">30 Dias</a>
            <a href="{{ route('admin.dashboard', ['period' => 'this_month']) }}" class="btn btn-outline-primary {{ $period == 'this_month' ? 'active' : '' }}">Este Mês</a>
            <a href="{{ route('admin.dashboard', ['period' => 'last_month']) }}" class="btn btn-outline-primary {{ $period == 'last_month' ? 'active' : '' }}">Mês Passado</a>
        </div>
    </div>

    {{-- Main Stats Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-primary card-hover">
                <div class="stat-icon">
                    <i class="fa-solid fa-brazilian-real-sign"></i>
                </div>
                <div class="stat-value">R$ {{ number_format($stats['total_revenue'], 2, ',', '.') }}</div>
                <div class="stat-label">Receita Total</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-success card-hover">
                <div class="stat-icon">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-value">{{ $stats['paid_orders_count'] }}</div>
                <div class="stat-label">Pedidos Pagos</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-info card-hover">
                <div class="stat-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="stat-value">R$ {{ number_format($stats['average_ticket'], 2, ',', '.') }}</div>
                <div class="stat-label">Ticket Médio</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-warning card-hover">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-value">{{ $stats['new_users'] }}</div>
                <div class="stat-label">Novos Usuários</div>
            </div>
        </div>
    </div>

    {{-- Secondary Stats Row --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card card-hover h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.15);">
                        <i class="fa-solid fa-clock text-warning"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5">{{ $stats['pending_orders'] }}</div>
                        <div class="text-muted small">Pedidos Pendentes</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-hover h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(139, 92, 246, 0.15);">
                        <i class="fa-solid fa-boxes-stacked" style="color: var(--dark-accent);"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5">{{ $stats['total_products'] }}</div>
                        <div class="text-muted small">Total de Produtos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-hover h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: {{ $stats['low_stock_products'] > 0 ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' }};">
                        <i class="fa-solid fa-triangle-exclamation" style="color: {{ $stats['low_stock_products'] > 0 ? 'var(--dark-danger)' : 'var(--dark-success)' }};"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5">{{ $stats['low_stock_products'] }}</div>
                        <div class="text-muted small">Estoque Baixo</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-hover h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(6, 182, 212, 0.15);">
                        <i class="fa-solid fa-cart-shopping text-info"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5">{{ $stats['active_carts'] }}</div>
                        <div class="text-muted small">Carrinhos Ativos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions + Abandoned Carts --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fa-solid fa-bolt text-warning"></i>
                    <span>Ações Rápidas</span>
                </div>
                <div class="card-body quick-actions">
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-2"></i>Adicionar Produto
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn btn-warning">
                        <i class="fa-solid fa-clock me-2"></i>Ver Pendentes
                    </a>
                    <a href="{{ route('admin.storefront.index') }}" class="btn btn-success">
                        <i class="fa-solid fa-store me-2"></i>Storefront
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-cart-arrow-down" style="color: {{ $stats['abandoned_carts'] > 0 ? 'var(--dark-warning)' : 'var(--dark-success)' }};"></i>
                        <span>Carrinhos Abandonados</span>
                    </div>
                    <span class="badge {{ $stats['abandoned_carts'] > 0 ? 'bg-warning' : 'bg-success' }}">{{ $stats['abandoned_carts'] }}</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div class="display-4 fw-bold mb-2">{{ $stats['abandoned_carts'] }}</div>
                    <p class="text-muted text-center mb-0">carrinhos inativos há mais de 24h</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-pie" style="color: var(--dark-accent);"></i>
                    <span>Visão Geral</span>
                </div>
                <div class="card-body">
                    <div class="system-overview">
                        <div class="system-overview-item">
                            <strong>{{ $stats['total_products'] }}</strong>
                            <small>Produtos</small>
                        </div>
                        <div class="system-overview-item">
                            <strong>{{ $stats['active_carts'] }}</strong>
                            <small>Carrinhos</small>
                        </div>
                        <div class="system-overview-item">
                            <strong>{{ $stats['new_users'] }}</strong>
                            <small>Usuários</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Chart --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-area" style="color: var(--dark-accent);"></i>
                        <span>Gráfico de Vendas</span>
                    </div>
                    <span class="badge bg-secondary">{{ str_replace(['7d', '30d', 'this_month', 'last_month'], ['Últimos 7 Dias', 'Últimos 30 Dias', 'Este Mês', 'Mês Passado'], $period) }}</span>
                </div>
                <div class="card-body chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-trophy text-warning"></i>
                        <span>Top 5 Produtos Mais Vendidos</span>
                    </div>
                    <span class="badge bg-secondary">{{ str_replace(['7d', '30d', 'this_month', 'last_month'], ['7 Dias', '30 Dias', 'Este Mês', 'Mês Passado'], $period) }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Variante</th>
                                <th class="text-end">Vendido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSellingProducts as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.show', $item->variant->product) }}" class="fw-medium">
                                            {{ $item->variant->product->name }}
                                        </a>
                                    </td>
                                    <td class="text-muted">{{ $item->variant->name }} - {{ $item->variant->value }}</td>
                                    <td class="text-end"><span class="badge bg-success">{{ $item->total_sold }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-chart-simple mb-2" style="font-size: 1.5rem;"></i>
                                        <p class="mb-0">Nenhum dado disponível</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation" style="color: {{ $stats['low_stock_products'] > 0 ? 'var(--dark-danger)' : 'var(--dark-success)' }};"></i>
                        <span>Alerta de Estoque Baixo</span>
                    </div>
                    @if($stats['low_stock_products'] > 0)
                        <span class="badge bg-danger">{{ $stats['low_stock_products'] }} produtos</span>
                    @else
                        <span class="badge bg-success">Tudo certo!</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($lowStockProductsList->count() > 0)
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Variante</th>
                                    <th class="text-center">Estoque</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProductsList as $product)
                                    @foreach($product->variants as $variant)
                                        <tr>
                                            <td class="fw-medium">{{ $product->name }}</td>
                                            <td class="text-muted">{{ $variant->name }} - {{ $variant->value }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $variant->stock == 0 ? 'bg-danger' : 'bg-warning' }}">
                                                    {{ $variant->stock }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.variants.edit', $variant) }}" class="btn btn-xs btn-outline-primary">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-check-circle text-success mb-3" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mb-0">Todos os produtos têm estoque suficiente!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Storefront Status Row --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-globe" style="color: {{ $stats['storefront_enabled'] ? 'var(--dark-success)' : 'var(--dark-danger)' }};"></i>
                        <span class="fw-semibold">Status da Storefront</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $stats['storefront_enabled'] ? 'bg-success' : 'bg-danger' }}">
                            {{ $stats['storefront_enabled'] ? 'Online' : 'Offline' }}
                        </span>
                        <a href="{{ route('admin.storefront.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-cog me-1"></i>Gerenciar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Configuração</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-center gap-2 mb-2">
                                    <div class="rounded-circle" style="width: 8px; height: 8px; background: {{ $stats['storefront_enabled'] ? 'var(--dark-success)' : 'var(--dark-danger)' }};"></div>
                                    <span class="text-muted">Status:</span>
                                    <span class="fw-medium {{ $stats['storefront_enabled'] ? 'text-success' : 'text-danger' }}">
                                        {{ $stats['storefront_enabled'] ? 'Habilitada' : 'Desabilitada' }}
                                    </span>
                                </li>
                                <li class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-solid fa-link text-muted" style="width: 8px;"></i>
                                    <span class="text-muted">URL:</span>
                                    @if($stats['storefront_url'])
                                        <a href="{{ $stats['storefront_url'] }}" target="_blank" class="fw-medium">{{ $stats['storefront_url'] }}</a>
                                    @else
                                        <span class="text-muted fst-italic">Não configurada</span>
                                    @endif
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-code text-muted" style="width: 8px;"></i>
                                    <span class="text-muted">API:</span>
                                    <code style="font-size: 0.8rem;">{{ config('app.url') }}/api</code>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Endpoints Principais</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <code class="px-2 py-1 rounded" style="font-size: 0.75rem;">POST /api/register</code>
                                <code class="px-2 py-1 rounded" style="font-size: 0.75rem;">POST /api/login</code>
                                <code class="px-2 py-1 rounded" style="font-size: 0.75rem;">GET /api/products</code>
                                <code class="px-2 py-1 rounded" style="font-size: 0.75rem;">GET /api/cart</code>
                                <code class="px-2 py-1 rounded" style="font-size: 0.75rem;">POST /api/checkout/{order}</code>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.storefront.settings') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-sliders me-1"></i>Configurar
                                </a>
                                <a href="{{ asset('STOREFRONT_API_DOCS.md') }}" class="btn btn-sm btn-outline-info" target="_blank">
                                    <i class="fa-solid fa-book me-1"></i>Documentação
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart Data -->
<script type="application/json" id="chart-data">
{
    "labels": {!! json_encode($salesChartData['labels']) !!},
    "values": {!! json_encode($salesChartData['values']) !!}
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('salesChart');
        const chartDataElement = document.getElementById('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent);

        // Create gradient
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.3)');
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Receita (R$)',
                    data: chartData.values,
                    backgroundColor: gradient,
                    borderColor: 'rgb(139, 92, 246)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(139, 92, 246)',
                    pointBorderColor: '#18181b',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#27272a',
                        titleColor: '#fafafa',
                        bodyColor: '#a1a1aa',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(63, 63, 70, 0.5)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#71717a',
                            font: {
                                size: 11,
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(63, 63, 70, 0.5)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#71717a',
                            font: {
                                size: 11,
                            },
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush

