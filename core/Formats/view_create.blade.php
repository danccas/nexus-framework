{{ '@' }}extends('layouts.ajax')
{{ '@' }}section('title', 'Registrar')
{{ '@' }}section('content')
<div class="card">
    <div class="card-body">
        {{ "{{ \$form->submit('" . $view . ".store')->begin() }}" }}

            <div class="modal-body">
                {{ '@' }}include('{{ $view }}.form')
            </div>
            <div class="hstack gap-2 justify-content-end">
                <button class="btn btn-primary" type="submit">Registrar</button>
            </div>

        {{ "{{ \$form->end() }}" }}

    </div>
</div>
{{ '@' }}endsection
