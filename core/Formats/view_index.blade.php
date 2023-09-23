{{ '@' }}extends('layouts.modern')
{{ '@' }}section('content')
    <div class="container">
        <{{ 'nexus:tablefy' }} :route="{{ $view }}.repository">
        </{{ 'nexus:tablefy' }}>
    </div>
{{ '@' }}endsection
