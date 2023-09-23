{{ '@' }}extends('template.ajax')
{{ '@' }}section('title','Detalles del chip')
{{ '@' }}section('content')
<div class="card">
  <div class="card-body">
    <table class="table">
      <tr>
        <th>Iccid</th>
        <td>{{ "{{ $" . $view . "->iccid }}" }}</td>
      </tr>
    </table>
</div>
</div>
{{ '@' }}endsection
