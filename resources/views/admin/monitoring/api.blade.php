@extends('layouts.admin')

@section('content')
<div id="monitoring-root" class="container text-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Status da API</h1>
        <div class="btn-group">
            <button id="refreshNow" class="btn btn-outline-primary">Atualizar</button>
            <button id="clearAppCache" class="btn btn-outline-warning">Limpar Cache</button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-hover">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Visão Geral</strong>
                        <div class="small text-muted">Ambiente: <span id="env">-</span> • Laravel: <span id="laravel">-</span> • PHP: <span id="php">-</span></div>
                    </div>
                    <span id="overallBadge" class="badge bg-secondary">Carregando...</span>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="h3" id="endpointsOnline">0/0</div>
                            <div class="text-muted small">Endpoints Online</div>
                        </div>
                        <div class="col-md-3">
                            <div class="h3" id="avgResponse">0ms</div>
                            <div class="text-muted small">Tempo Médio</div>
                        </div>
                        <div class="col-md-3">
                            <div class="h3" id="duration">0ms</div>
                            <div class="text-muted small">Coleta</div>
                        </div>
                        <div class="col-md-3">
                            <div class="h3" id="timestamp">-</div>
                            <div class="text-muted small">Atualizado</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7 mb-3">
            <div class="card card-hover">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Endpoints</strong>
                    <span class="small text-muted">Monitoramento em tempo real</span>
                </div>
                <div class="card-body" id="endpointsList">
                    <!-- Preenchido via JS -->
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-3">
            <div class="card card-hover mb-3">
                <div class="card-header"><strong>Serviços</strong></div>
                <div class="card-body">
                    <ul class="list-group" id="servicesList">
                        <!-- Preenchido via JS -->
                    </ul>
                </div>
            </div>

            <div class="card card-hover">
                <div class="card-header"><strong>Ações Rápidas</strong></div>
                <div class="card-body">
                    <button class="btn btn-sm btn-outline-secondary" id="pingNow">Ping</button>
                    <a href="{{ url('/up') }}" class="btn btn-sm btn-outline-success" target="_blank">Health (/up)</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
/* Force all text to white on monitoring page */
#monitoring-root, #monitoring-root * { color: #ffffff !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const overallBadge = document.getElementById('overallBadge');
    const endpointsList = document.getElementById('endpointsList');
    const servicesList = document.getElementById('servicesList');
    const endpointsOnline = document.getElementById('endpointsOnline');
    const avgResponse = document.getElementById('avgResponse');
    const duration = document.getElementById('duration');
    const timestamp = document.getElementById('timestamp');
    const envSpan = document.getElementById('env');
    const laravelSpan = document.getElementById('laravel');
    const phpSpan = document.getElementById('php');

    const refreshBtn = document.getElementById('refreshNow');
    const pingBtn = document.getElementById('pingNow');
    const clearCacheBtn = document.getElementById('clearAppCache');

    const fetchStatus = () => {
        overallBadge.textContent = 'Carregando...';
        overallBadge.className = 'badge bg-secondary';
        fetch('{{ route('admin.monitoring.status') }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => render(data))
            .catch(err => {
                console.error(err);
                overallBadge.textContent = 'Indisponível';
                overallBadge.className = 'badge bg-danger';
            });
    };

    const render = (data) => {
        envSpan.textContent = data.app.env;
        laravelSpan.textContent = data.app.laravel;
        phpSpan.textContent = data.app.php;

        timestamp.textContent = new Date(data.timestamp).toLocaleTimeString();
        duration.textContent = `${data.metrics.duration_ms}ms`;
        avgResponse.textContent = `${data.metrics.avg_response_ms}ms`;
        endpointsOnline.textContent = `${data.metrics.endpoints_online}/${data.metrics.endpoints_total}`;

        const overallMap = { healthy: 'success', degraded: 'warning', down: 'danger' };
        const labelMap = { healthy: 'Saudável', degraded: 'Degradado', down: 'Fora do ar' };
        const cls = overallMap[data.overall] || 'secondary';
        overallBadge.textContent = labelMap[data.overall] || 'Desconhecido';
        overallBadge.className = `badge bg-${cls}`;

        // Endpoints
        endpointsList.innerHTML = data.endpoints.map(e => `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <div><strong>${e.name}</strong> <span class="badge bg-light text-dark ms-2">${e.method}</span></div>
                    <div class="small text-muted">${e.url}</div>
                </div>
                <div class="text-end">
                    <div><span class="badge ${e.status === 'online' ? 'bg-success' : (e.status === 'error' ? 'bg-warning' : 'bg-danger')}">${e.status.toUpperCase()}</span></div>
                    <div class="small text-muted">${e.time_ms}ms${e.status_code ? ` • ${e.status_code}` : ''}</div>
                </div>
            </div>
        `).join('');

        // Services
        servicesList.innerHTML = '';
        Object.entries(data.services).forEach(([key, s]) => {
            const friendly = key.charAt(0).toUpperCase() + key.slice(1);
            const status = s.status;
            const cls = status === 'ok' ? 'success' : (status === 'not_configured' ? 'secondary' : 'danger');
            const time = s.time_ms !== undefined ? ` • ${s.time_ms}ms` : '';
            const meta = s.driver ? ` • ${s.driver}` : (s.disk ? ` • ${s.disk}` : (s.mailer ? ` • ${s.mailer}` : ''));
            const err = s.error ? `
                <div class="small text-danger mt-1">${s.error}</div>
            ` : '';
            servicesList.insertAdjacentHTML('beforeend', `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">${friendly}</div>
                        <div class="small text-muted">${status}${meta}${time}</div>
                        ${err}
                    </div>
                    <span class="badge bg-${cls} rounded-pill">${status}</span>
                </li>
            `);
        });
    };

    refreshBtn.addEventListener('click', fetchStatus);
    pingBtn.addEventListener('click', fetchStatus);

    clearCacheBtn.addEventListener('click', () => {
        clearCacheBtn.disabled = true;
        clearCacheBtn.textContent = 'Limpando...';
        fetch('{{ route('admin.storefront.clear-cache') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => alert(data.message || 'OK'))
            .catch(() => alert('Falha ao limpar cache'))
            .finally(() => { clearCacheBtn.disabled = false; clearCacheBtn.textContent = 'Limpar Cache'; });
    });

    // Initial load and interval
    fetchStatus();
    setInterval(fetchStatus, 30000);
});
</script>
@endpush
