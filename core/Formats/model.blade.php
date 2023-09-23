namespace App\Models;

use Core\Model;
use Core\Formity;

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

    protected $casts = [
@foreach($columns as $c)
      '{{ $c->name }}' => '{{ $c->cast }}',
@endforeach
    ];

  public static function form() {
    $form = Formity::instance('{{ $view }}');
@foreach($columns->toArray() as $c)
      $form->addField('{{ $c['name'] }}', 'input:{{ $c['cast'] }}');
@endforeach
    return $form;
  }
}
