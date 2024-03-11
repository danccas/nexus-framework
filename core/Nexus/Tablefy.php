<?php

namespace Core\Nexus;

use Core\Concerns\Collection;
use Core\Nexus\Header;
use Core\Blade;

class Tablefy implements \JsonSerializable
{
    public $directRoute = null;

    protected $listHeaders = [];
    private $action    = null;
    protected $query_a   = null;
    protected $query_b   = [];
    public $total      = null;
    protected $paginate   = 50;
    public $offset     = 0;
    public $last_page  = 0;
    public $items      = [];
    public $page       = 1;
    public $q          = null;
    public $filters    = null;
    private $is_appends = false;
    public $time_count  = null;
    public $time_query  = null;
    public $time_total  = null;
    private $countEstimate = false;
    public $is_estimate = false;
    public $sublimit   = false;
    protected $model = null;
    private $cbRow = null;
    private $events = [];
    private $executed = false;
    private $inputs   = [];

    public $modifiable_columns  = [];
    private $fillable_columns   = [];
    public $order_columns       = [];

    private $link_format = '?page=#';
    public $page_prev    = null;
    public $page_next    = null;
    public $link_prev    = null;
    public $link_next    = null;

    public $actions      = false;
    public $actions_group = [];
    public $querys = [];

    protected $_view = null;

    protected $listCbs = [];


    public function __construct()
    {
        if (method_exists($this, 'row')) {
            $ce = $this;
            $this->map(function ($n) use (&$ce) {
                return $ce->row($n);
            });
        }
        $this->prepare();
    }
    public function getHeaders()
    {
        return $this->listHeaders;
    }
    public function setRoute($route)
    {
        $this->directRoute = $route;
        return $this;
    }
    public function route()
    {
        return $this->directRoute;
    }
    public function query($q, $params = [])
    {
        $this->query_a = $q;
        $this->query_b = $params;
        return $this;
    }
    private function sum_times($time1, $time2)
    {
        $times = array($time1, $time2);
        $seconds = 0;
        foreach ($times as $time) {
            list($format, $micro) = explode('.', $time);
            list($hour, $minute, $second) = explode(':', $format);
            $seconds += $hour * 3600;
            $seconds += $minute * 60;
            $seconds += $second;
            $seconds += $micro / 1000;
        }
        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes  = floor($seconds / 60);
        $seconds -= $minutes * 60;
        return sprintf('%02d:%02d:%.3f', $hours, $minutes, $seconds);
    }
    public function countEstimate()
    {
        $this->countEstimate = true;
        return $this;
    }
    public function hydrate($cName)
    {
        $this->model = $cName;
        return $this;
    }
    public function formatPage($text)
    {
        $this->link_format = $text;
        return $this;
    }
    private function generateLink($format, $p)
    {
        return str_replace('#', $p, $format);
    }
    private function generatePages()
    {
        if (!($this->paginate > 0)) {
            //      return false;
        }
        $this->last_page = ceil($this->total / $this->paginate);
        if ($this->page < $this->last_page) {
            $this->page_next = $this->page + 1;
            $this->link_next = $this->generateLink($this->link_format, $this->page + 1);
        }
        if ($this->page > 1) {
            $this->page_prev = $this->page - 1;
            $this->link_prev = $this->generateLink($this->link_format, $this->page - 1);
        }
        return $this;
    }
    public function appends($input)
    {
        $this->is_appends = true;
        $this->inputs = array_merge($this->inputs, $input);
        if (empty($input['action'])) {
            $this->action = 'list';
        } else {
            $this->action = $input['action'];
        }
        if (isset($input['paginate'])) {
            $this->paginate = (int) $input['paginate'];
        }
        if (isset($input['page'])) {
            $this->page = (int) $input['page'];
        }
        if (!empty($input['q'])) {
            $this->q = strtoupper(trim($input['q']));
            $this->query_a = str_replace('--search', ' ', $this->query_a);
            $this->query_b = $this->query_b + ['q' => $this->q];
        }
        if (!empty($input['filters'])) {
            // dd($input['filters']); exit();
            // $input['filters'] = json_decode($input['filters'], true);
            if (is_array($input['filters'])) {
                $this->filters = $input['filters'];
            }
        }
        return $this;
    }
    private function eventColumnDefault($event, $column)
    {
        if (empty($this->model)) {
            return false;
        }
        if (isset($this->events[$column][$event])) {
            return false;
        }
        $ccname = $this->model;
        if (!in_array($column, array_keys((new $ccname)->getCasts()))) {
            return false;
        }
        $ddtype = (new $ccname)->getCastType($column);
        if (in_array($column, $this->fillable_columns) && (in_array($ddtype, ['date', 'string', 'integer', 'decimal']))) {
            if ($event == 'edit') {
                $this->on('edit', $column, function ($row, $res) use ($ddtype, $column) {
                    return [
                        'type' => $ddtype,
                        'attrs' => [
                            'value' => method_exists($row, 'getRawOriginal') ? $row->getRawOriginal($column) : null,
                        ]
                    ];
                });
            } else if ($event == 'save') {
                $this->on('save', $column, function ($row, $res) {
                    $editar = [];
                    $editar[$res->field] = $res->value;
                    unset($row->_map);
                    $row->update($editar);
                    return true;
                });
            } else {
                //        dd(123);
            }
            return $this->events[$column][$event];
        }
    }
    private function eventColumn($event, $column)
    {
        $rp = true;
        if (!isset($this->events[$column])) {
            $rp = false;
        }
        if (!isset($this->events[$column][$event])) {
            $rp = false;
        }
        if (!$rp) {
            if ($dd = $this->eventColumnDefault($event, $column)) {
                return $dd;
            }
            return false;
        }
        return $this->events[$column][$event];
    }
    public function callback($name, $cb) {
      $this->listCbs[$name] = $cb;
      return $this;
    }
    private function prepare()
    {
      if (method_exists($this, 'actionsByRow')) {
        $ce = $this;
        $this->listCbs['actionsByRow'] = function($row) use($ce) { return $ce->actionsByRow($row); };
      }
      if(!empty($this->listCbs['actionsByRow'])) {
        $this->actions = true;
      }
      if(empty($this->directRoute)) {
        $this->directRoute = route()->current();
      }
       $this->prepareColumns();
        if (method_exists($this, 'bulkActions')) {
            $this->actions_group = $this->bulkActions();
            if (!empty($this->actions_group)) {
                foreach ($this->actions_group as $key => $f) {
                    $f->setIndex('group' . $key)->prepare($this);
                }
            }
        }
    }
    private function prepareColumns()
    {
        if (method_exists($this, 'headers')) {
            $this->listHeaders = $this->headers();
            if (is_array($this->listHeaders)) {
                $this->listHeaders = array_map(function ($h) {
                    return is_string($h) ? new Header($h) : $h;
                }, $this->listHeaders);
                $widthAll = 0;
                if(!empty($this->actions)) {
                  $widthAll += 100;
                }
                foreach($this->listHeaders as $h) {
                  $widthAll += $h->width() == null ? 100 : $h->width();
                }
                foreach($this->listHeaders as $h) {
                  if($h->width() !== null) {
                    $h->width((($h->width() / $widthAll) * 100) . '%');
                  }
                }
            }
        }
    }
    private function execute()
    {
        if ($this->executed) {
            return $this;
        }
        $this->executed = true;
        $this->prepare();
        if (!$this->is_appends) {
            $this->appends([]);
        }
        if(method_exists($this, 'component')) {
          $this->component();

        }
        if (!empty($this->model)) {
            $name = $this->model;
            $this->fillable_columns = (new $name)->getFillable();
            if (method_exists($this, 'events')) {
                $this->events();
            }
            if (method_exists($name, 'tablefy')) {
                $name::tablefy($this);
            }
        }
        if ($this->action == 'click') {
            if (!empty($this->actions) && false) {
                foreach ($this->actions as $key => $f) {
                    if ($this->inputs['option'] == $f->uid()) {
                        $rp = ($this->model)::find($this->inputs['ids']);
                        if (empty($rp)) {
                            abort(404);
                        }
                        if (method_exists($f, 'handle')) {
                            $rp = $f->handle($rp);
                            return [
                                'success' => true,
                                'result'    => $rp
                            ];
                        }
                        return [
                            'success' => false,
                            'message' => 'no actions',
                        ];
                    }
                }
            }
            abort(404);
        } elseif ($this->action == 'distinct') {
            if (!empty($this->inputs['column'])) {
                if (($ee = $this->eventColumn('edit', $this->inputs['column'])) || true) {
                    $query = $this->queryAddFilters($this->query_a, $this->inputs['column']);
                    $query = "SELECT dz." . $this->inputs['column'] . " as value, COUNT(*) cantidad
            FROM (" . $query . ") dz
            GROUP BY dz." . $this->inputs['column'] . "
            ORDER BY 2 DESC, 1 ASC
						LIMIT 50";
                    $this->querys[] = [$query, $this->query_b];
                    $data = db()->collect($query, $this->query_b);
                    $box = !empty($ee) && is_callable($ee) ? $ee(new \stdClass, $this->inputs['column']) : null;
                    $valores = $data->toArray();
                    $valores = array_combine(array_map(function ($n) {
                        return $n->value;
                    }, $valores), $valores);
                    $valores = array_map(function ($n) use ($box) {
                        if (!empty($box)) {
                            if (!empty($box['options'])) {
                                if (isset($box['options'][$n->value])) {
                                    $n->value = $box['options'][$n->value];
                                }
                            }
                        }
                        return $n;
                    }, $valores);
                    $this->setItems([
                        'success' => true,
                        'result'  => [
                            'time_query' => $data->execute->time,
                            'data'       => $valores,
                        ],
                    ]);
                    return $this;
                }
            }
            return $this;
        } else if ($this->action == 'edit') {
            if (!empty($this->inputs['column'])) {
                if ($ee = $this->eventColumn($this->action, $this->inputs['column'])) {
                    if (!empty($this->model)) {
                        $name = $this->model;
                        $row = $name::find($this->inputs['id']);
                        if (method_exists($row, '__validData')) {
                            $row->__validData();
                        }
                    } else {
                        $row = $this->inputs['id'];
                    }
                    return [
                        'success' => true,
                        'result'  => $ee($row, $this->inputs['column']),
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No data0',
                    ];
                }
            }
            return [
                'success' => false,
                'message' => 'No data1'
            ];
        } elseif ($this->action == 'save') {
            if (!empty($this->inputs['column'])) {
                if ($ee = $this->eventColumn($this->action, $this->inputs['column'])) {
                    if ($this->hasRow()) {
                        $row = $this->getRow($this->inputs['id']);
                    } else {
                        if (!empty($this->model)) {
                            $name = $this->model;
                            $row = $name::find($this->inputs['id']);
                        } else {
                            $row = $this->inputs['id'];
                        }
                    }
                    $response = new \stdClass;
                    $response->id    = $this->inputs['id'];
                    $response->field = $this->inputs['column'];
                    $response->value = $this->inputs['value'];

                    $rp = $ee($row, $response);
                    if (!$rp) {
                        return [
                            'success' => false,
                        ];
                    }
                    if (!empty($this->cbRow)) {
                        $row->_map = ($this->cbRow)($row);
                    }
                    return [
                        'success' => true,
                        'row'     => $row,
                    ];
                }
            }
            return [
                'success' => false,
                'message' => 'No data2',
                'ee' => $ee
            ];
        }
        $queryCount = $this->query_a;
        if (strpos($queryCount, '--started') !== FALSE && strpos($queryCount, '--pagination') !== FALSE) {
            $queryCount = explode('--started', $queryCount);
            $queryCount = explode('--pagination', $queryCount[1]);
            $queryCount = $queryCount[0] . "\n --pagination\n";
        }
        $queryCount = $this->queryAddFilters($queryCount);

        if (empty($this->countEstimate)) {
            $cantidad = "SELECT count(*) total FROM (" . $queryCount . ")x";
            $this->querys[] = [$cantidad, $this->query_b];
            $cantidad = db()->collect($cantidad, $this->query_b);
            $numero   = $cantidad->first()->total;
        } else {
            $cantidad = "EXPLAIN (FORMAT JSON) " . $queryCount;
            $this->querys[] = [$cantidad, $this->query_b];
            $cantidad = db()->collect($cantidad, $this->query_b);
            $log      = $cantidad->first()->{'QUERY PLAN'};
            $log      = json_decode($log);
            $numero   = ($log[0]->Plan->{'Plan Rows'});
        }

        $this->offset = (($this->page - 1) * $this->paginate);

        $query = $this->queryAddFilters($this->query_a);

        if (strpos($query, '--pagination') !== FALSE) { #&& empty($this->filters)) {
            $query = str_replace(
                '--pagination',
                " LIMIT " . $this->paginate . " OFFSET " . $this->offset,
                $query
            );
            $this->sublimit = true;
        } else {
            $query = "
        SELECT * FROM (" . $query . ")x
        LIMIT " . $this->paginate . " OFFSET " . $this->offset;
        }

        $query = str_replace('--started', '', $query);
        $this->querys[] = [$query, $this->query_b];
        $data = db()->collect($query, $this->query_b);
        $this->time_count = $cantidad->execute->time;
        $this->time_query = $data->execute->time;
        $this->time_total = $this->sum_times($this->time_count, $this->time_query);

        if (!empty($this->model)) {
            $name = $this->model;
            $this->items = $name::hydrate($data->all());
            $this->items->map(function ($n) {
                if (method_exists($n, '__validData')) {
                    $n->__validData();
                }
                return $n;
            });
            if ($this->items->first() !== null) {
                $keys = ($this->items->first())->toArray();
            }
        } else {
            $this->items = $data->all();
            $keys = isset($this->items[0]) ?  (array) $this->items[0] : [];
        }
        if (!empty($keys)) {
            unset($keys['id']);
            $keys = array_keys($keys);
            foreach ($keys as $k) {
                $this->eventColumnDefault('edit', $k);
                $this->eventColumnDefault('save', $k);
            }
        }

        $ce = &$this;

        if (!empty($this->listCbs['actionsByRow'])) {
          $this->items->map(function($row) use ($ce) {
            $acts = ($this->listCbs['actionsByRow'])($row);
            if(!empty($acts)) {
              foreach ($acts as $key => $f) {
                $f->setIndex('row' . $key)->prepare($this);
              }
            }
            $row->__options = $acts;
            #array_map(function($n) {
            #  return $n->toArray();
            #}, $acts);
            return $row;
          });
        }

        if (!empty($this->_view)) {
            $this->cbRow = function ($n) use ($ce) {
                $html = $ce->_view->append(['row' => $n]);
                $html = explode('@split', $html);
                return array_map(function ($n) {
                    return trim($n);
                }, $html);
            };
        }

        if (is_array($this->items)) {
            $this->items = array_map(function ($n) use (&$ce) {
                if (!empty($ce->cbRow)) {
                    $n->_map = ($ce->cbRow)($n);
                    $ce->order_columns = array_keys($n->_map);
                } else {
                    $ce->order_columns = array_keys((array) $n);
                }
                return $n;
            }, $this->items);
        } else {
            $this->items->map(function ($n) use (&$ce) {
                if (!empty($ce->cbRow)) {
                    $n->_map = ($ce->cbRow)($n);
                    $ce->order_columns = array_keys((array) $n->_map);
                } else {
                    $ce->order_columns = array_keys($n->toArray());
                }
                return $n;
            });
        }

        $this->total = $numero;
        $this->is_estimate = $this->countEstimate;
        if ($this->countEstimate && count($this->items) < $this->paginate) {
            $this->is_estimate = false;
            $this->total       = count($this->items);
        }
        $this->generatePages();
        return $this;
    }
    public function queryAddFilters($query, $excluded = null)
    {
        if (strpos($query, '--filters') !== FALSE  || true) {
            $filtros = [];
            if (!empty($this->filters)) {
                foreach ($this->filters as $k => $f) {
                    if ($f == null || $f == '') {
                        continue;
                    }
                    if ($excluded === $k) {
                        continue;
                    }
                    if (is_array($f) && count($f) == 0) {
                        continue;
                    }
                    // dd($f); exit();
                    if (is_array($f)) {
                        $f = array_map(function ($n) {
                            if ($n === '') {
                                return 'nu11';
                            } else {
                                return "'" . $n . "'";
                            }
                            return $n;
                        }, $f);
                        if (in_array('nu11', $f)) {
                            $f = array_filter($f, function ($n) {
                                return $n !== 'nu11';
                            });
                            if (!empty($f)) {
                                $filtros[] = '(pz.' . $k . ' IS NULL OR pz.' . $k . " IN (" . implode(",", $f) . "))";
                            } else {
                                $filtros[] = '(pz.' . $k . ' IS NULL)';
                            }
                        }
                    } elseif ($f == 'true' || $f == 'false' || $f == 'null' || $f == 'not null') {
                        $filtros[] = 'pz.' . $k . " is " . strtoupper($f);
                    } elseif ($f >= 0 && is_numeric($f)) {
                        $filtros[] = 'pz.' . $k . " = " . $f;
                    } else if (is_string($f)) {
                        $filtros[] = 'pz.' . $k . " = " . "'" . $f . "'";
                    } else {
                        $filtros[] = 'pz.' . $k . " IN (" . implode(",", [$f]) . ")";
                    }
                }
            }
            if (!empty($filtros)) {
                $filtros = '(' . implode(' AND ', $filtros) . ')';
                $query = "
          SELECT pz.*
          FROM (" . $query . ") pz
          WHERE " . $filtros;
            }
        }
        return $query;
    }
    public function hasRow()
    {
        return strpos($this->query_a, '--row') !== false;
    }
    public function getRow($id)
    {
        $query = str_replace('--row', '', $this->query_a);
        $this->querys[] = [$query, $this->query_b + ['id' => $id]];
        $data = db()->collect($query, $this->query_b + [
            'id' => $id
        ]);
        $this->time_count = 0;
        $this->time_query = $data->execute->time;
        $this->time_total = $data->execute->time;

        if (!empty($this->model)) {
            $name = $this->model;
            $data = $name::hydrate($data->all());
            $data->map(function ($n) {
                $n->__validData();
                return $n;
            });
        } else {
            $data = $data->all();
        }
        $data = $data->first();

        //    if(!empty($this->cbRow)) {
        //      $data->_map = ($this->cbRow)($data);
        //    }
        return $data;
    }
    public function on($event, $field, $cb = null)
    {
        if (is_array($field)) {
            foreach ($field as $f) {
                if (!isset($this->events[$f])) {
                    $this->events[$f] = [];
                }
                if (!in_array($f, $this->modifiable_columns)) {
                    $this->modifiable_columns[] = $f;
                }
                $this->events[$f][$event] = $cb;
            }
        } else {
            if (!isset($this->events[$field])) {
                $this->events[$field] = [];
            }
            if (!in_array($field, $this->modifiable_columns)) {
                $this->modifiable_columns[] = $field;
            }
            $this->events[$field][$event] = $cb;
        }
        return $this;
    }
    public function view($name, $params = [])
    {
        $this->_view = (new Blade($name))->append($params);
        return $this;
    }

    public function map($cb)
    {
        $this->cbRow = $cb;
        return $this;
    }
    private function setItems($data)
    {
        $this->items = $data;
        return $this;
    }
    public function getItems()
    {
        return $this->items;
    }
    public function get()
    {
        return $this->execute();
        return $this->toArray(); ##$this;
    }
    public function toArray()
    {
      $this->execute();

      $listado = !empty($this->items) ? (is_array($this->items) ? $this->items : $this->items->toArray()) : [];
      $listado = array_map(function($n) {
        return is_object($n) ? (method_exists($n, 'toArray') ? $n->toArray() : ((array) $n)) : $n;
      }, $listado);
      $response = [
            'success' => true,
            'result' => [
                //'querys' => $this->querys,
                'total' => $this->total,
                'per_page' => $this->paginate,
                'offset' => $this->offset,
                'last_page' => $this->last_page,
                'page' => $this->page,
                'q' => $this->page,
                'filters' => $this->filters,
                'time_count' => $this->time_count,
                'time_query' => $this->time_query,
                'time_total' => $this->time_total,
                'is_estimate' => $this->is_estimate,
                'executed' => $this->executed,
                #                'columns'  => $this->listHeaders,
                'modifiable_columns' => $this->modifiable_columns,
                //                'order_columns' => $this->order_columns,
                'page_prev' => $this->page_prev,
                'page_next' => $this->page_next,
                'actions' => $this->actions,
                'actions_group' => $this->actions_group,
                'items' => $listado,
            ]
      ];
        if (!empty($this->listHeaders)) {
            $response['result']['order_columns'] = $this->listHeaders;
        }
        return $response;
    }
    public function getJSON() {
      return $this->toArray();
    }
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
      return $this->getJSON();
    }
}
