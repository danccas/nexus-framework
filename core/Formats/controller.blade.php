namespace App\Http\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\{{ $model }};

class {{ $model }}Controller extends Controller
{

  public function index()
  {
    return view('{{ $view }}.index');
  }

  public function show({{ $model }} ${{ $view }})
  {
    return view('{{ $view }}.show',compact('{{ $view}}'));
  }


  function create() {
    $form = {{ $model }}::form();
    return view('{{ $view }}.create', compact('form'));
  }
  function store(Request $request)
  {
    $form = {{ $model }}::form();
    if (!$form->valid()) {
      return back()->withInputs()->with('message', $form->error());
    }
    $data = $form->data();
    {{ $model }}::create($data);
    return reponse()->redirect('{{ $view }}.index');
  }


  public function edit({{ $model }} ${{ $view }})
  {
    $form = {{ $model }}::form();
    $form->setPreData((array) ${{ $view }}->toArray());
    return view('{{ $view }}.edit', compact('{{ $view }}', 'form'));
  }
  public function update({{ $model }} ${{ $view }})
  {
    $form = {{ $model }}::form();
    if (!$form->valid()) {
      return back()->withInputs()->with('message', $form->error());
    }

    $data = $form->data();
    ${{ $view }}->update($data);
    return reponse()->redirect('{{ $view }}.index');
  }
}
