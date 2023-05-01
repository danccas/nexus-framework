<?php

namespace Core;

use Core\Concerns\Collection;
use Core\DBHelpers;

class DBFuncs
{
    protected $connection = null;
    protected $log = false;
    protected $cache = false;
    protected $cacheExpire = -1;
    protected $whenConsultCache = null;
    protected $flog  = __DIR__ . '/db.log';
    protected $fileCache = __DIR__ . '/db.cache.json';
    protected $type;
    
    static function process_data($type = 'set', $fields = [], &$fieldlist = null, &$datalist = null, &$duplelist = null)
    {
        $fieldlist = array();
        $valuelist = array();
        $datalist  = array();
        $duplelist = array();
        if (!is_array($fields)) {
            $fieldlist = $fields;
            return;
        }
        array_walk($fields, function (&$value, $field) {
            $n['duple'] = strpos($field, '*') === 0;
            $n['equal'] = strpos($field, '=') === strlen($field) - 1;
            $n['field'] = str_replace('*', '', str_replace('=', '', $field));
            $n['id']    = str_replace('_', '', $n['field']);
            $n['value'] = is_null($value) ? null : $value;
            $value = $n;
        });
        if ($type == 'set') {
            foreach ($fields as $n) {
                if ($n['equal']) {
                    $fieldlist[] = $n['field'] . ' = ' . $n['value'];
                    if ($n['duple']) {
                        $duplelist[] = $n['field'] . ' = ' . $n['value'];
                    }
                } else {
                    $fieldlist[] = $n['field'] . ' = :' . $n['id'];
                    $datalist[$n['id']] = $n['value'];
                    if ($n['duple']) {
                        $duplelist[] = $n['field'] . ' = :' . $n['id'];
                    }
                }
            }
            $fieldlist = implode(', ', $fieldlist);
            $duplelist = implode(', ', $duplelist);
        } else {
            foreach ($fields as $n) {
                if ($n['equal']) {
                    $fieldlist[] = $n['field'];
                    $valuelist[] = $n['value'];
                } else {
                    $fieldlist[] = $n['field'];
                    $valuelist[] = ':' . $n['id'];
                    $datalist[$n['id']] = $n['value'];
                }
            }
            $fieldlist = '(' . implode(', ', $fieldlist) . ') VALUES (' . implode(', ', $valuelist) . ')';
        }
    }
    function  insert($table, $fields, $ignore = false)
    {
        if (empty($fields)) {
            return false;
        }
        static::process_data('values', $fields, $fieldlist, $datalist, $duplelist);
        $sql = 'INSERT ' . ($ignore ? 'IGNORE ' : '') . ' INTO ' . $table;

        $sql .= $fieldlist;

        if (!empty($duplelist)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $duplelist;
        }

        $this->exec($sql, $datalist);

        return $this->last_insert_id();
    }
    function update($table, $fields, $where = null)
    {
        if (empty($fields)) {
            return false;
        }
        if (empty($where)) {
            return false;
        }
        static::process_data('set', $fields, $fieldlist, $datalist);
        $sql = 'UPDATE ' . $table . ' SET ' . $fieldlist;

        static::process_data('set', $where, $wherelist, $datalistw);
        if (!empty($wherelist)) {
            $sql .= ' WHERE ' . $wherelist;
        }
        $this->exec($sql, array_merge($datalist, $datalistw));
        return true;
    }
    function call($sp, $fields = null)
    {
        $fls = array();
        if (!empty($fields)) {
            foreach ($fields as $field => $value) {
                $fls[] = '?';
            }
        }
        $this->exec('SELECT ' . $sp . '(' . implode(',', $fls) . ')', $fields);
        return true;
    }
    function delete($table, $where = null)
    {
        $sql = "DELETE FROM " . $table;
        if (empty($where)) {
            return false;
        }
        static::process_data('set', $where, $wherelist, $datalistw);
        $sql .= 'WHERE ' . $wherelist;
        $this->exec($sql, $datalistw);
        return true;
    }
}
