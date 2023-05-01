<?php

namespace Core;

use Core\Concerns\Collection;

class PaginationQuery
{

    private $action    = null;
    protected $query_a   = null;
    protected $query_b   = [];
    public $total      = null;
    public $per_page   = 50;
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
    private $classHydrate = null;
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

    public $actions      = [];

    public function query($q, $params = [])
    {
        $this->query_a = $q;
        $this->query_b = $params;
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
        $this->classHydrate = $cName;
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
        if (!($this->per_page > 0)) {
            //      return false;
        }
        $this->last_page = ceil($this->total / $this->per_page);
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
        if (isset($input['per_page'])) {
            $this->per_page = (int) $input['per_page'];
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
        if (empty($this->classHydrate)) {
            return false;
        }
        if (isset($this->events[$column][$event])) {
            return false;
        }
        $ccname = $this->classHydrate;
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
    private function execute()
    {
        if ($this->executed) {
            return $this;
        }
        $this->executed = true;
        if (!$this->is_appends) {
            $this->appends([]);
        }
        if (!empty($this->classHydrate)) {
            $name = $this->classHydrate;
            $this->fillable_columns = (new $name)->getFillable();
            if (method_exists($name, 'tablefy')) {
                $name::tablefy($this);
            }
        }
        if ($this->action == 'distinct') {
            if (!empty($this->inputs['column'])) {
                if (($ee = $this->eventColumn('edit', $this->inputs['column'])) || true) {
                    $query = $this->queryAddFilters($this->query_a, $this->inputs['column']);
                    $query = "SELECT dz." . $this->inputs['column'] . " as value, COUNT(*) cantidad
            FROM (" . $query . ") dz
            GROUP BY dz." . $this->inputs['column'] . "
            ORDER BY 2 DESC, 1 ASC
            LIMIT 50";
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
                    if (!empty($this->classHydrate)) {
                        $name = $this->classHydrate;
                        $row = $name::find($this->inputs['id']);
                        if (method_exists($row, '__validData')) {
                            $row->__validData();
                        }
                    } else {
                        $row = $this->inputs['id'];
                    }
                    $this->setItems([
                        'success' => true,
                        'result'  => $ee($row, $this->inputs['column']),
                    ]);
                    return $this;
                } else {
                    $this->setItems([
                        'success' => false,
                        'message' => 'No data0',
                    ]);
                    return $this;
                }
            }
            $this->setItems([
                'success' => false,
                'message' => 'No data1'
            ]);
            return $this;
        } elseif ($this->action == 'save') {
            if (!empty($this->inputs['column'])) {
                if ($ee = $this->eventColumn($this->action, $this->inputs['column'])) {
                    if ($this->hasRow()) {
                        $row = $this->getRow($this->inputs['id']);
                    } else {
                        if (!empty($this->classHydrate)) {
                            $name = $this->classHydrate;
                            $row = $name::find($this->inputs['id']);
                        } else {
                            $row = $this->inputs['id'];
                        }
                    }
                    $response = new ResponsePagination();
                    $response->id    = $this->inputs['id'];
                    $response->field = $this->inputs['column'];
                    $response->value = $this->inputs['value'];

                    $rp = $ee($row, $response);
                    if (!$rp) {
                        $this->setItems([
                            'success' => false,
                        ]);
                        return $this;
                    }
                    if (!empty($this->cbRow)) {
                        $row->_map = ($this->cbRow)($row);
                    }
                    $this->setItems([
                        'success' => true,
                        'row'     => $row,
                    ]);
                    return $this;
                }
            }
            $this->setItems([
                'success' => false,
                'message' => 'No data2',
                'ee' => $ee
            ]);
            return $this;
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
            $cantidad = db()->collect($cantidad, $this->query_b);
            $numero   = $cantidad->first()->total;
        } else {
            $cantidad = "EXPLAIN (FORMAT JSON) " . $queryCount;
            $cantidad = db()->collect($cantidad, $this->query_b);
            $log      = $cantidad->first()->{'QUERY PLAN'};
            $log      = json_decode($log);
            $numero   = ($log[0]->Plan->{'Plan Rows'});
        }

        $this->offset = (($this->page - 1) * $this->per_page);

        $query = $this->queryAddFilters($this->query_a);

        if (strpos($query, '--pagination') !== FALSE && empty($this->filters)) {
            $query = str_replace(
                '--pagination',
                " LIMIT " . $this->per_page . " OFFSET " . $this->offset,
                $query
            );
            $this->sublimit = true;
        } else {
            $query = "
        SELECT * FROM (" . $query . ")x
        LIMIT " . $this->per_page . " OFFSET " . $this->offset;
        }

        $query = str_replace('--started', '', $query);
        $data = db()->collect($query, $this->query_b);
        $this->time_count = $cantidad->execute->time;
        $this->time_query = $data->execute->time;
        $this->time_total = $this->sum_times($this->time_count, $this->time_query);

        if (!empty($this->classHydrate)) {
            $name = $this->classHydrate;
            $this->items = $name::hydrate($data->all());
            $this->items->map(function ($n) {
                if (method_exists($n, '__validData')) {
                    $n->__validData();
                }
                return $n;
            });
            if ($this->items->first() !== null) {
                $keys = (array) $this->items->first();
            }
        } else {
            $this->items = $data->all();
            $keys = isset($this->items[0]) ?  (array) $this->items[0] : [];
        }
        if (!empty($keys)) {
            $keys = array_keys($keys);
            unset($keys['id']);
            foreach ($keys as $k) {
                $this->eventColumnDefault('edit', $k);
                $this->eventColumnDefault('save', $k);
            }
        }

        $ce = &$this;
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
                    $ce->order_columns = array_keys((array) $n);
                }
                return $n;
            });
        }

        $this->total = $numero;
        $this->is_estimate = $this->countEstimate;
        if ($this->countEstimate && count($this->items) < $this->per_page) {
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
        $data = db()->collect($query, $this->query_b + [
            'id' => $id
        ]);
        $this->time_count = 0;
        $this->time_query = $data->execute->time;
        $this->time_total = $data->execute->time;

        if (!empty($this->classHydrate)) {
            $name = $this->classHydrate;
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
        $this->execute();
        return $this->toArray(); ##$this;
    }
    public function toArray()
    {
        $this->execute();
        return [
            'success' => true,
            'result' => [
                'total' => $this->total,
                'per_page' => $this->per_page,
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
                'modifiable_columns' => $this->modifiable_columns,
                'order_columns' => $this->order_columns,
                'page_prev' => $this->page_prev,
                'page_next' => $this->page_next,
                'actions' => $this->actions,
                'items' => !empty($this->items) ?  $this->items->toArray() : [],
            ]
        ];
    }
}
