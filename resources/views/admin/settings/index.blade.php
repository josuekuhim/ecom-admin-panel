@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Configurações</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.store') }}" method="POST">
        @csrf
        <div class="card mb-4">
            <div class="card-header">
                Configurações de Envio
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="shipping_origin_cep" class="form-label">CEP de Origem</label>
                    <input type="text" class="form-control" id="shipping_origin_cep" name="shipping_origin_cep" value="{{ old('shipping_origin_cep', $settings['shipping_origin_cep']) }}">
                    <div class="form-text">O CEP de onde os produtos serão enviados.</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Integração InfinitePay
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="infinitepay_client_id" class="form-label">ID do Cliente</label>
                    <input type="text" class="form-control" id="infinitepay_client_id" name="infinitepay_client_id" value="{{ old('infinitepay_client_id', $settings['infinitepay_client_id']) }}">
                </div>
                <div class="mb-3">
                    <label for="infinitepay_client_secret" class="form-label">Chave Secreta</label>
                    <input type="password" class="form-control" id="infinitepay_client_secret" name="infinitepay_client_secret" value="{{ old('infinitepay_client_secret', $settings['infinitepay_client_secret']) }}">
                    <div class="form-text">Deixe em branco para manter a chave atual.</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
    </form>
</div>
@endsection
