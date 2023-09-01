<?php

namespace Core;
use Core\ElegantException;
use Core\DBFuncs;

class DBPSQL
{
    protected $central;
    private $action = 'get';
    private $selects = [];
    private $tables = [];
		private $wheres = [];
		private $orders = [];
    private $prepares = [];
    private $connect = null;

    public function __construct($central)
    {
        $this->central = $central;
    }
    public function clearQuery()
		{
				$this->action = 'get';
        $this->selects = ['*'];
        $this->tables = [];
				$this->wheres = [];
				$this->orders = [];
        $this->prepares = [];
        return $this;
    }
    public function select($columns)
    {
        if (is_array($columns)) {
            $this->selects = [];
            foreach ($columns as $c) {
                $this->selects[] = $c;
            }
				} else {
					$this->selects[] = $columns;
        }
    }
    public function from($table)
    {
        $this->tables[] = $table;
    }
    public function where($a, $b, $c)
    {
        $this->wheres[] = [$a, $b, $c];
		}
		public function order($a, $b = 'ASC')
    {
        $this->orders[] = [$a, $b];
    }

    public function insert($data)
    {
        $this->action = 'insert';
        $this->prepares = $data;
		}
		public function update($data)
    {
        $this->action = 'update';
				$this->prepares = $data;
    }

    public function prepareQuery()
		{
			$ce = $this;
			$ce->prepares_mod = $ce->prepares;
			if (!empty($this->wheres) && in_array($this->action, ['get','update'])) {
                $this->wheres = array_map(function ($n) use ($ce) {
                    $uu = 'v' . uniqid();
                    if ($n[1] == '=') {
                        $ce->prepares_mod[$uu] = $n[2];
                        return $n[0] . ' = :' . $uu;
                    }
                    return $n[0];
                }, $this->wheres);
			}
			if($this->action == 'insert') {
				DBFuncs::process_data('values', $this->prepares, $fieldlist, $datalist, $duplelist);
//				dd([$fieldlist, $datalist, $duplelist]);
				$rp = 'INSERT INTO ' . implode(', ', $this->tables) . ' ';
				$rp .= $fieldlist;
//						$rp .= " (" . (implode(',', array_map(function($e) { return $e; }, array_keys($this->prepares)))) . ")";
//						$rp .= " VALUES (" . $fieldlist . ")";
//            $rp .= " VALUES (" . (implode(',', array_map(function($e) { return ':' . $e; }, array_keys($this->prepares)))) . ")";
						//$rp .= " RETURNING id";
						if (!empty($duplelist)) {
	            $rp .= ' ON DUPLICATE KEY UPDATE ' . $duplelist;
		        }
						$rp .= " RETURNING *";
#						if(in_array('robusto.terminal_permiso', $this->tables)) {
#							dd([$fieldlist, $datalist, $duplelist]);
#						}
						return [$rp, $datalist];

				} elseif($this->action == 'update') {
					if(empty($ce->prepares)) {
						return null;
					}
					$rp1 = 'UPDATE ' . implode(', ', $this->tables) . ' SET ';
					$rp = [];
					foreach($ce->prepares as $k => $v) {
						$rp[] = $k . ' = :' . $k;
					}
					$rp1 .= implode(', ', $rp) . ' ';
					if(!empty($this->wheres)) {
	          $rp1 .= 'WHERE ' . implode(' AND ', $this->wheres) . "\n";
          }
					return [$rp1, $this->prepares_mod];

        } elseif($this->action == 'get') {
            $rp = 'SELECT ' . (empty($this->selects) ? '*' : implode(', ', $this->selects)) . "\n";
            $rp .= 'FROM ' . implode(', ', $this->tables) . "\n";
            if(!empty($this->wheres)) {
                $rp .= 'WHERE ' . implode(' AND ', $this->wheres) . "\n";
						}
						if(!empty($this->orders)) {
							$this->orders = array_map(function ($n) use ($ce) {
								return $n[0] . ' ' . $n[1];
							}, $this->orders);
              $rp .= 'ORDER BY ' . implode(', ', $this->orders);
						}
#						print_r($rp);
            return [$rp, $this->prepares_mod];
        }
    }
    public function connect($central)
    {
        try {
            if (!empty($central->getHosts()['port'])) {
                $dsn = $central->getProtocol() . ':host=' . $central->getHosts()['host'] . ';port=' . $central->getHosts()['port'] . ';dbname=' . $central->getDatabase();
            } else {
                $dsn = $central->getProtocol() . ':host=' . $central->getHosts()['host'] . ';dbname=' . $central->getDatabase();
            }
            $db = new \PDO($dsn, $central->getAuthentication()['username'], $central->getAuthentication()['password']);
						$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
        #    dd($e);
            $central->except('<b>DB:</b> Can\'t connect to database ' . $central->getProtocol() . '!');
        } catch (\Exception $e) {
#            dd($e);
        }
        $this->connect = $db;
        return $db;
    }
    public function execute($connect, $query, $prepare)
    {
        try {

            $result = $connect->prepare($query);
            $rp = $result->execute($prepare);

				} catch (\Exception $e) {
#				} catch (ElegantException $e) {
#					echo $e;
						//if( DEVEL_MODE ){
							echo "<pre>";
							print_r($e->getMessage());
						//}

            exit;
        }
        return $result;
    }
    public function fetch($result, $first = false)
    {
        if ($first === false) {
            $rp = $result->fetchAll(\PDO::FETCH_CLASS);
        } else {
            $rp = $result->fetch(\PDO::FETCH_ASSOC);
            return $rp;
            if(!empty($this->central->query)) {
                $model = $this->central->query->getModel()::class;
                return new $model($rp ? $rp : []);
            }
            if (!empty($rp)) {
                $rp = (object) $rp;
            }
        }
        return $rp;
    }
    public function lastInsertId() {
        if(!empty($this->connect)) {
            return $this->connect->lastInsertId();
        }
        return null;
    }
}
