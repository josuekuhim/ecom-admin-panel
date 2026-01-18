<div class="d-flex flex-column flex-shrink-0 p-3 bg-surface admin-sidebar" style="width: 280px; height: 100vh; position: fixed;">
    {{-- Brand Section --}}
    <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
        <img src="{{ asset('images/logo.png') }}" alt="E-commerce Admin" class="brand-logo">
        <span class="visually-hidden">Painel Admin</span>
    </a>
    
    {{-- Notification Bell --}}
    <div class="mb-3 mt-2">
        <div class="d-flex align-items-center justify-content-between px-2 py-2 rounded" style="background: rgba(139, 92, 246, 0.1);">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-bell text-muted"></i>
                <span class="small" style="color: var(--dark-text-secondary);">Notificações</span>
            </div>
            <span id="notification-badge" class="badge bg-danger rounded-pill" style="display: none; font-size: 0.7rem;">0</span>
        </div>
    </div>
    
    <hr class="my-2">
    
    {{-- Navigation --}}
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" aria-current="page">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.drops.index') }}" class="nav-link {{ request()->routeIs('admin.drops.*') ? 'active' : '' }}">
                <i class="fa-solid fa-layer-group"></i>
                <span>Coleções</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="fa-solid fa-box-open"></i>
                <span>Produtos</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="fa-solid fa-receipt"></i>
                <span>Pedidos</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-group"></i>
                <span>Clientes</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.carts.index') }}" class="nav-link {{ request()->routeIs('admin.carts.*') ? 'active' : '' }}">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Carrinhos</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.storefront.index') }}" class="nav-link {{ request()->routeIs('admin.storefront.*') ? 'active' : '' }}">
                <i class="fa-solid fa-store"></i>
                <span>Storefront</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.monitoring.index') }}" class="nav-link {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}">
                <i class="fa-solid fa-heart-pulse"></i>
                <span>Monitoramento</span>
            </a>
        </li>
    </ul>
    
    {{-- Bottom Section --}}
    <div class="mt-auto">
        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }} mb-3">
            <i class="fa-solid fa-sliders"></i>
            <span>Configurações</span>
        </a>
        
        <hr class="my-2">
        
        {{-- User Dropdown --}}
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle p-2 rounded" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false" style="background: var(--dark-bg-subtle);">
                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 36px; height: 36px; background: var(--gradient-primary);">
                    <i class="fa-solid fa-user text-white" style="font-size: 0.875rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <strong class="d-block" style="font-size: 0.875rem; color: var(--dark-text-primary);">{{ Auth::user()->name }}</strong>
                    <small style="color: var(--dark-text-muted); font-size: 0.75rem;">Administrador</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser2">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Sair</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>
