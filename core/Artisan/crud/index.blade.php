Route::resource('library', 'App\Http\Controllers\LibraryController');

<?php

namespace App\Models;

use Core\DB;
use Core\Model;
use Core\Formity;
use App\Scopes\MultiTenantScope;
use Core\Database\Builder;

class Chip extends Model
{

  protected $connection = 'sutran';
  protected $table = 'robusto.chip';

  /*
  public int $idchip;
  public int $tenant_id;
  public string $iccid;
  public string $operador;
  public bool $estado;
  public string $plan;
   */

  protected $fillable = ['id','tenant_id','created_by','iccid','operador','estado','plan','comunicacion','ctddatausage','ctdsmsusage','ctdvoiceusage','ctdsessioncount','sip_hash','msisdn','created_on','update_on'];

  protected $casts = [
    'create_on' => 'date',
    'update_on' => 'date',
  ];

  public static function tablefy()
  {

  }
}

########## CONTROLLER
<?php

namespace App\Http\Controllers;

use Collator;
use Core\Controller;
use App\Librarys\Helpers;
use App\Models\Chip;
use App\Models\Usuario;
use Core\DB;
use Core\Request;

class ChipController extends Controller
{
  function index()
  {
    return view('chip.index');
  }
  function tablefy()
  {
    $listado = Chip::tablefy()
      ->appends(request()->input())
      ->view('chip.tablefy_index')
      ->get();
    return response()->json($listado);
  }

  function create() {
    $form = Chip::form();
    return view('chip.create', compact('form'));
  }

  function store(Request $request)
  {
    $form = Chip::form();
    if (!$form->valid()) {
      return back()->withInputs()->with('message', $form->error());
    }

db()->insert('robusto.chip', [
  'iccid' => $data->iccid,
  'operador_id' => $data->operador_id,
  'plan' => $data->plan,
  'tenant_id' => user()->tenant_id,
  'created_by' => user()->id
]);

return Helpers::response('chips.index');
}

public function show(Chip $chip)
{
return view('chip.show',compact('chip'));
}

public function edit(Chip $chip)
{
$form = Chip::form();
$form->setPreData((array) $chip->toArray());
return view('chip.edit', compact('chip', 'form'));
}

public function update(Chip $chip)
{
$form = Chip::form();
if (!$form->valid()) {
  return back()->withInputs()->with('message', $form->error());
}

$data = $form->data();

if ($chip->iccid != $data->iccid) {
  $id_chip = db()->first("SELECT id from robusto.chip where iccid= :id", ['id' => $data->iccid]);
  if (!empty($id_chip->id)) {
    return response()->json(['success' => false, 'message' => 'El chip ya ha sido registrado. <br>Verifique el iccid - CHIP']);
  }
}

db()->update('robusto.chip', [
  'iccid' => $data->iccid,
  'operador_id' => $data->operador_id,
  'plan' => $data->plan,
  'update_on' => db()->time(),
], ['id' => $chip->id ] );

return Helpers::response('chips.index');
}
function delete()
  {
  }
}
~


##### INDEX
@extends('layouts.modern')
@section('content')
    <div class="container">
        <nexus:tablefy :route="library.tablefy">
        </nexus:tablefy>
    </div>
@endsection
##### SHOW
@extends('template.ajax')
@section('title','Detalles del chip')
@section('content')
<div class="card {{ request()->ajax() ? '' : 'col-lg-11 col-xl-9 col-xxl-7' }}">
  <div class="card-body">
    <table class="table">
      <tr>
        <th>Iccid</th>
        <td>{{ $chip->iccid }}</td>
      </tr>
    </table>
</div>
</div>
@endsection

##### EDIT
@extends('template.ajax')
@section('title', 'Actualizar conductor')
@section('content')
<div class="card {{ request()->ajax() ? '' : 'col-lg-11 col-xl-9 col-xxl-7' }}">
    <div class="card-body">
        {{ $form->submit('chips.update', ['chip' => $chip->id ])->begin() }}
            <div class="modal-body">
                @method('PUT')
                @include('chip.form')
            </div>
            <div class="hstack gap-2 justify-content-end">
                <button class="btn btn-primary" type="submit">Actualizar</button>
            </div>
        {{ $form->end() }}
    </div>
</div>
@endsection

##### UPDATE
##### CREATE
@extends('template.ajax')
@section('title', 'Registrar chip')
@section('content')
<div class="card {{ request()->ajax() ? '' : 'col-lg-11 col-xl-9 col-xxl-7' }}">
    <div class="card-body">
        {{ $form->submit('chips.store')->begin() }}
            <div class="modal-body">
                @include('chip.form')
            </div>
            <div class="hstack gap-2 justify-content-end">
                <button class="btn btn-primary" type="submit">Registrar</button>
            </div>
        {{ $form->end() }}
    </div>
</div>
@endsection
##### STORE
##### DELETE


##### FORM
<div class="mb-3">
    <label  class="form-label">Iccid</label>
    <?= $form->getField('iccid')->render(['placeholder' => 'Escriba el iccid']);?>
</div>

<div class="mb-3">
    <label class="form-label">Operador</label>
    {{ $form->field('operador_id') }}
</div>

<div class="mb-3">
    <label class="form-label">Plan</label>
    <?= $form->getField('plan')->render(['placeholder' => 'Escriba el plan']) ?>
</div>
