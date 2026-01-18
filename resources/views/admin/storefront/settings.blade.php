@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Configurações da Storefront</h1>
        <a href="{{ route('admin.storefront.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.storefront.settings.update') }}" method="POST">
        @csrf

        {{-- Configurações Gerais --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações Gerais</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="storefront_url" class="form-label">URL da Storefront</label>
                            <input type="url" class="form-control" id="storefront_url" name="storefront_url" 
                                   value="{{ old('storefront_url', $settings['storefront_url']) }}"
                                   placeholder="https://sua-loja.vercel.app">
                            <div class="form-text">URL onde sua storefront está hospedada (Next.js, Vercel, etc.)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="api_rate_limit" class="form-label">Limite de Requisições por Minuto</label>
                            <input type="number" class="form-control" id="api_rate_limit" name="api_rate_limit" 
                                   value="{{ old('api_rate_limit', $settings['api_rate_limit']) }}"
                                   min="10" max="1000">
                            <div class="form-text">Número máximo de requisições por minuto por usuário</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status da Storefront --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Status da Storefront</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="storefront_enabled" name="storefront_enabled" value="1"
                                       {{ old('storefront_enabled', $settings['storefront_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="storefront_enabled">
                                    Storefront Habilitada
                                </label>
                            </div>
                            <div class="form-text">Quando desabilitada, a API retornará erro 503 para a storefront</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="maintenance_mode" name="maintenance_mode" value="1"
                                       {{ old('maintenance_mode', $settings['maintenance_mode']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="maintenance_mode">
                                    Modo de Manutenção
                                </label>
                            </div>
                            <div class="form-text">Ativa página de manutenção na storefront</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Informações da API --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações da API</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>URL Base da API:</h6>
                        <code>{{ config('app.url') }}/api</code>
                        
                        <h6 class="mt-3">Endpoints Principais:</h6>
                        <ul class="list-unstyled">
                            <li><code>POST /api/register</code> - Registro de usuário</li>
                            <li><code>POST /api/login</code> - Login</li>
                            <li><code>GET /api/products</code> - Listar produtos</li>
                            <li><code>GET /api/cart</code> - Carrinho do usuário</li>
                            <li><code>POST /api/orders</code> - Criar pedido</li>
                            <li><code>POST /api/checkout/{order}</code> - Processar pagamento</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Autenticação:</h6>
                        <p>A API usa <strong>Bearer Tokens</strong> (Laravel Sanctum)</p>
                        
                        <h6>Headers Obrigatórios:</h6>
                        <ul class="list-unstyled">
                            <li><code>Content-Type: application/json</code></li>
                            <li><code>Accept: application/json</code></li>
                            <li><code>Authorization: Bearer {token}</code> (para rotas protegidas)</li>
                        </ul>
                        
                        <h6 class="mt-3">CORS:</h6>
                        <p>Configure o domínio da sua storefront no arquivo <code>config/cors.php</code></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documentação --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Documentação para Desenvolvedores</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Fluxo de Checkout:</h6>
                        <ol>
                            <li>Cliente adiciona produtos ao carrinho</li>
                            <li>Cliente faz login/registro</li>
                            <li>Cliente finaliza carrinho (POST /api/orders)</li>
                            <li>Sistema gera cobrança PIX (POST /api/checkout/{order})</li>
                            <li>Cliente paga via PIX</li>
                            <li>Webhook confirma pagamento</li>
                            <li>Status do pedido muda para "paid"</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6>Integração com Next.js:</h6>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>// .env.local
NEXT_PUBLIC_API_URL={{ config('app.url') }}/api

// lib/api.js
const API_URL = process.env.NEXT_PUBLIC_API_URL;

export async function fetchProducts() {
  const response = await fetch(`${API_URL}/products`);
  return response.json();
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
        <a href="{{ route('admin.storefront.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação em tempo real da URL
    const urlInput = document.getElementById('storefront_url');
    urlInput.addEventListener('input', function() {
        const url = this.value;
        if (url && !url.match(/^https?:\/\/.+/)) {
            this.setCustomValidity('Por favor, insira uma URL válida começando com http:// ou https://');
        } else {
            this.setCustomValidity('');
        }
    });

    // Aviso sobre modo de manutenção
    const maintenanceToggle = document.getElementById('maintenance_mode');
    maintenanceToggle.addEventListener('change', function() {
        if (this.checked) {
            if (!confirm('Tem certeza que deseja ativar o modo de manutenção? Isso impedirá o acesso dos clientes à storefront.')) {
                this.checked = false;
            }
        }
    });
});
</script>
@endpush
