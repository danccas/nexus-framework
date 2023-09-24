{{ '@' }}extends('layouts.modern')
{{ '@' }}section('content')
    <div>
      @if(!empty($create))
      <div style="padding: 10px 0;text-align: right;">
        <a href="{{ "{{ route('" . $create . "') }}" }}" data-popup class="btn btn-primary">Registrar</a>
      </div>
      @endif
<{{ 'nexus:tablefy' }} :route="{{ $repository }}">
</{{ 'nexus:tablefy' }}>
    </div>
{{ '@' }}endsection
