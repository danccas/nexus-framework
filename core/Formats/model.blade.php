namespace App\Models;

use Core\Model;

class {{ $model }} extends Model
{
    protected $connection = '{{ $dsn }}';


    protected $table = '{{ $table }}';

@php
$fillable = array_map(function($n) {
  return $n['name'];
}, $columns->toArray());
@endphp

    protected $fillable = ['{{ implode("', '", $fillable) }}'];

}
