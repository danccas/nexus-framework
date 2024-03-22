{{ '@' }}extends('layouts.ajax')
{{ '@' }}section('title', 'Actualizar')
{{ '@' }}section('content')
<div class="card">
    <div class="card-body">
        {{ "{{ \$form->submit('" . $view . ".update', $" . $instance . "->id)->begin() }}" }}

            <div class="modal-body">
                {{ '@' }}method('PUT')
                {{ '@' }}include('{{ $view }}.form')
            </div>
            <div class="hstack gap-2 justify-content-end">
                <button class="btn btn-primary" type="submit">Actualizar</button>
            </div>

            {{ "{{ \$form->end() }}" }}

    </div>
</div>
{{ '@' }}endsection
