<div class="admin-subnav mb-4">
    <a href="{{ route('admin.overview') }}" class="admin-subnav-link {{ request()->routeIs('admin.overview') ? 'is-active' : '' }}">
        Обзор
    </a>
    <a href="{{ route('admin.users') }}" class="admin-subnav-link {{ request()->routeIs('admin.users') ? 'is-active' : '' }}">
        Пользователи
    </a>
    <a href="{{ route('admin.useful') }}" class="admin-subnav-link {{ request()->routeIs('admin.useful') ? 'is-active' : '' }}">
        Полезное
    </a>
    <a href="{{ route('admin.support') }}" class="admin-subnav-link {{ request()->routeIs('admin.support') ? 'is-active' : '' }}">
        Поддержка
    </a>
    <a href="{{ route('admin.audit') }}" class="admin-subnav-link {{ request()->routeIs('admin.audit') ? 'is-active' : '' }}">
        Журнал
    </a>
</div>
