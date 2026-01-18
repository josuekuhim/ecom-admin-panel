@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Produtos</h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Criar Produto</a>
    </div>

    {{-- Search Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.products.index') }}" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Buscar por nome ou descrição..." value="{{ $search ?? '' }}">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table td">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Preço</th>
                        <th>Coleção</th>
                        <th>Variantes</th>
                        <th>Imagens</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->name }}</td>
                        <td>R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                        <td>{{ $product->drop->title }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $product->variants_count }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $product->computed_images_count ?? $product->images_count }}</span>
                        </td>
                        <td>{{ $product->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-info">Ver</a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-warning">Editar</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Nenhum produto encontrado. @if($search) Tente um termo de busca diferente. @endif</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Append search query to pagination links --}}
            {{ $products->appends(['search' => $search])->links() }}
        </div>
    </div>
</div>
@endsection
