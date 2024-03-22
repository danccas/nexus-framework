namespace App\Http\Controllers{{ $context2 }};

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\{{ $context . $model }};

class {{ $model }}Controller extends Controller
{

  public function index()
  {
    return view('{{ $view }}.index');
  }

  public function show({{ $model }} ${{ $instance }})
  {
    return view('{{ $view }}.show',compact('{{ $instance }}'));
  }

  public function create() {
    $form = {{ $model }}::form();
    return view('{{ $view }}.create', compact('form'));
  }

  public function store(Request $request)
  {
    $form = {{ $model }}::form();
    if (!$form->valid()) {
      return back()->withInputs()->with('message', $form->error());
    }
    $data = $form->data();
    {{ $model }}::create($data);
    return reponse()->redirect('{{ $view }}.index');
  }

  public function edit({{ $model }} ${{ $instance }})
  {
    $form = {{ $model }}::form();
    $form->setPreData((array) ${{ $instance }}->toArray());
    return view('{{ $view }}.edit', compact('{{ $instance }}', 'form'));
  }

  public function update({{ $model }} ${{ $instance }})
  {
    $form = {{ $model }}::form();
    if (!$form->valid()) {
      return back()->withInputs()->with('message', $form->error());
    }

    $data = $form->data();
    ${{ $instance }}->update((array) $data);
    return response()->redirect('{{ $view }}.index');
  }
}
