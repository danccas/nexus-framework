{{ '@' }}extends('layouts.ajax')
{{ '@' }}section('title','Detalles')
{{ '@' }}section('content')
<div class="card">
  <div class="card-body">
    <table class="table">
@foreach($columns->toArray() as $c)
      <tr>
        <th>{{ $c['name']}}</th>
        <td>{{ "{"."{" }} ${{ $instance }}->{{ $c['name'] }} {{"}"."}"}}</td>
      </tr>
@endforeach
    </table>
</div>
</div>
{{ '@' }}endsection
