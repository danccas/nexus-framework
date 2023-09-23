{{ '@' }}extends('layouts.modern')
{{ '@' }}section('content')
    <div class="container">
        <{{ 'nexus:tablefy' }} :route="library.tablefy">
        </{{ 'nexus:tablefy' }}>
    </div>
{{ '@' }}endsection
