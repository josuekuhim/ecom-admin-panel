@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Drops</h1>
    <a href="{{ route('admin.drops.create') }}" class="btn btn-primary mb-3">Create Drop</a>

    <table class="table td">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($drops as $drop)
            <tr>
                <td>{{ $drop->id }}</td>
                <td>{{ $drop->title }}</td>
                <td>{{ $drop->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <a href="{{ route('admin.drops.edit', $drop) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('admin.drops.destroy', $drop) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $drops->links() }}
</div>
@endsection
