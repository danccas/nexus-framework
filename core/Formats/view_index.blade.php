{{ '@' }}extends('layouts.modern')
{{ '@' }}section('content')
    <div class="container">
      <div style="padding: 10px 0;text-align: right;">
        <a href="{{ "{{ route('" . $view . ".create') }}" }}" data-popup class="btn btn-sm btn-primary">Registrar</a>
      </div>
        <{{ 'nexus:tablefy' }} :route="{{ $view }}.repository">
        </{{ 'nexus:tablefy' }}>
    </div>
{{ '@' }}endsection
