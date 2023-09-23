
namespace App\Http\Controllers;

use Core\Controller;
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
    $form = Chip::form();
    return view('chip.create', compact('form'));
  }
  function store(Request $request)
  {
    $form = Chip::form();
    if (!$form->valid()) {
      return back()->withInputs()->with('message', $form->error());
    }
    $data = $form->data();
    {{ $model }}::create($data);
    return reponse()->redirect('{{ $view }}.index');
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
    $chip->update($data);
    return reponse()->redirect('{{ $view }}.index');
  }


}

