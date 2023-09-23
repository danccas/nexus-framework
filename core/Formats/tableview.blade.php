namespace App\Http\Nexus\Views;

use Core\Nexus\Tablefy;
use Core\Nexus\Header;
use Core\Nexus\Action;
use Core\Request;
use App\Models\{{ $model }};

class {{ $model }}TableView extends Tablefy
{
    protected $model = {{ $model }}::class;
    protected $paginate = 15;

    public function headers(): array
    {
        return [
@foreach($columns->toArray() as $c)
            Header::name('{{ $c['name'] }}')->width({{ (900 / count($columns)) }}),
@endforeach
        ];
    }

    public function row($model)
    {
        return [
@foreach($columns->toArray() as $c)
            $model->{{ $c['name'] }},
@endforeach
        ];
    }
    protected function repository()
    {
        return $this->query("
            SELECT *
            FROM {{ $table }}
        ");
    }
    protected function actionsByRow($row)
    {
        return [
          Action::title('Ver')->icon('eye')->link('/dashboard'),
          Action::title('Editar')->icon('edit')->link('/dashboard'),
          Action::title('Eliminar')->icon('delete')->link('/dashboard'),
        ];
    }

    /** For bulk actions */
    protected function bulkActions()
    {
        return [
        ];
    }
    public function controller(Request $request)
    {
        $response = $this
          ->repository()
          ->appends(request()->input())
          ->get();
        return response()->json($response);
    }
}
