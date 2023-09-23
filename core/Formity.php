<?php

namespace Core;

use Core\FormityField;

class Formity
{
  public static $INPUT_USERNAME = 'username';
  public static $INPUT_PASSWORD = 'password';
  public static $INPUT_EMAIL = 'email';
  public static $INPUT_DOMAIN = 'domain';

  private static $instances = null;
  private $nToken = '_token';
  private $onlyFields = false;

  public $mform = null;

  
  public static function exists($cdr)
  {
    return array_key_exists($cdr, static::$instances);
  }
  public function onlyFields($tt) {
    $this->onlyFields = $tt;
    return $this;
  }

  /* se agrego  */
  public static function importRoute($route)
  {
    if (static::$instances === null) {
      static::$instances = new \stdClass();
    }
    return static::$instances;
  }
  public static function g($cdr = null)
  {
    
  }
  public static function getInstance($cdr = null)
  {
    return static::instance($cdr);
  }
  public static function instance($cdr = null)
  {
    if (static::$instances === null) {
      static::$instances = new \stdClass();
    }
    if (!is_null($cdr) && !property_exists(static::$instances, $cdr)) {
      $rp = static::$instances->$cdr = new static($cdr);
    } elseif (is_null($cdr)) {
      trigger_error('DSN no existe: ' . $cdr);
    } else {
      $rp = static::$instances->$cdr;
    }
    return $rp;
  }
  public static function init($cdr = null)
  {
    $cdr = is_null($cdr) ? count(static::$instances) : $cdr;
    return Formity::instance($cdr);
  }
  public static function delete($cdr)
  {
    static::$instances->$cdr->fields = array();
    static::$instances->$cdr = null;
    unset(static::$instances->$cdr);
  }
  public $uniqueId = null;
  public $id = null;
  public $parentForm = null;
  public $number_child = 0;
  private $method = 'POST';
  private $token = null;
  private $message = '';
  public $assigned_id = 0;
  public $repeat = false;
  public $file = false;
	public $is_valid = null;
	private $attrs = [];
  public $fields = array();
  public $_error = array();
  public $title = null;
  public $description = null;
  public $url = null;
  public $buttons = array('Guardar');
  private $byButton = null;
  public $is_ajax = false;
  public $ajax_response = array();
  private $isSession = null;
  public $obfuscate = true;

  function getClone($i = null)
  {
    $cl = clone $this;
    #    $cl->id = $cl->id . '_clone';
    $cl->fields = array_map(function ($f) use ($cl, $i) {
      $c = $f->getClone($i);
      $c->mform = $cl;
      return $c;
    }, $cl->fields);
    #    $cl->number_child = $i;
    return $cl;
  }
  function __construct($cdr = null)
  {
    $this->id = $cdr;
    $this->token = date('z'); //Formity::hash($cdr);
    $this->nToken = Formity::hash($cdr);
    $this->isSession = 'sess_' . $cdr;
  }
  public function setSecurity($x)
  {
    $this->obfuscate = $x;
    return $this;
  }
  public function setUniqueId($x)
  {
    $this->uniqueId = $x;
    $this->isSession = 'sess_' . $this->id . '_' . $x;
  }
  public static function hash($st)
  {
    return substr(md5($st ?? ''), 0, 4);
    //    return substr(dechex(intval($st, 36)), -5);
  }
  public function getButton()
  {
    return strtolower($this->byButton);
  }
  public function setTitle($t)
  {
    $this->title = $t;
  }
  public function setDescription($t)
  {
    $this->description = $t;
  }
  function setMessage($txt)
  {
    $this->message = $txt;
    return $this;
  }
  function setMethod($method)
  {
    $this->method = $method;
    return $this;
  }
  function setError($e)
  {
    if (empty($e)) {
      return $this;
    }

    $this->is_valid = false;
    return $this;
  }
  function myRequest($request)
  {
    #$data = Formity::RequestToValues($this, $request);
    Formity::myform_set_values($this, $request, false, 0, false);
    $this->is_valid = empty($this->_error);
    return true;
  }
  function byRequest($method = null)
  {
    if (!is_null($method)) {
      $this->method = $method;
    }
    if (request()->method() == $this->method || true) {
      if (!$this->obfuscate) {
        $data = Formity::RequestToValues($this, request()->inputs());
        $this->byButton = request()->input('fmt_bn');
        Formity::myform_set_values($this, $data, false, 0, false);
        $this->is_valid = empty($this->_error);
        return true;

      } elseif (request()->input($this->nToken) == $this->token) {
				if (empty($this->uniqueId) || (!empty($this->uniqueId) && request()->input($this->nToken . 'id') == $this->uniqueId)) {
					$inputs = request()->inputs();
					$data = Formity::RequestToValues($this, $inputs);
					$this->byButton = request()->input('fmt_bn');
          Formity::myform_set_values($this, $data, false, 0, false);
          $this->is_valid = empty($this->_error);
          return true;
        }
			} else {
				dd([$this->nToken, request()->input(), request()->input($this->nToken), $this->token]);
				abort(404);
			}
    }
    return false;
  }
  static function RequestToValues($form, $request)
  {
    $values = array();
    foreach ($form->fields as $key => $v1) {
      $k1 = $v1->getNameRequest();
      #echo "KEY->" . $k1 . "<br />";
      if (!empty($v1->disabled)) {
        #continue;
      }
      $values[$key] = null;
      if (!empty($v1->childstruct)) {
        if (!empty($v1->childrang)) {
          $oks = function ($f) use ($request, &$oks) {
            return array_map(function ($n) use ($request, &$oks) {
              if ($n->isForm()) {
                return $oks($n->childstruct);
              } else {
                return $n->getNameRequest();
              }
            }, $f->fields);
          };
          $ks = $oks($v1->childstruct);
          #          echo "<pre>KEYS:";print_r($ks);print_r($request);echo "</pre>";
          $values[$key] = process_join_arrays($request, $ks, $error);
          if (!empty($error)) {
            $values[$key] = null;
            echo "<pre>KS-ERROR:";
            print_r($error);
            echo "</pre>";
          }
        } else {
          $values[$key] = Formity::RequestToValues($v1->children, $request);
        }
      } elseif (isset($request[$k1])) {
        $values[$key] = $request[$k1];
      } elseif ($v1->extra == 'file' && !empty($_FILES[$k1])) {
        if (!empty($_FILES[$k1]['name'])) {
          $values[$key] = $_FILES[$k1];
        }
      } elseif ($v1->extra == 'files' && !empty($_FILES[$k1])) {
        $tic = array_filter($_FILES[$k1]['name'], function ($n) {
          return !empty($n);
        });
        if (!empty($tic)) {
          $values[$key] = process_join_arrays_vold($_FILES[$k1], ['name', 'type', 'tmp_name', 'error', 'size']);
        }
      }
    }
    return $values;
  }
  static function myform_set_values($form, $values, $force = false, $nivel = 0, $change = false)
  {
    foreach ($form->fields as $key => $field) {
      if (isset($values[$key]) || is_null($values[$key])) {
        if ($field->isForm()) {
          if ($field->required && empty($values[$key])) {
            $form->_error[] = $field->name . ': Es requerido';
          } elseif (!(!$field->required && empty($values[$key]))) {
            //          $field->seteo = true;
            if (!empty($field->childrang)) {
              //echo "viene2222>>>";
              $error = null;
              if (!$field->declareChildren(count($values[$key]), $error)) {
                $form->_error[] = $field->name . ': Debe contener desde ' . $field->getMin() . ' hasta ' . $field->getMax() . ' elementos, no ' . count($values[$key]);
              } else {
                foreach ($field->children as $k2 => $c) {
                  Formity::myform_set_values($c, $values[$key][$k2], $force, $nivel + 1, $change);
                  if (!empty($c->_error)) {
                    foreach ($c->_error as $e) {
                      $form->_error[] = $field->name . ' #' . ($k2 + 1) . ': ' . $e;
                    }
                  }
                }
              }
            } else {
              $field->children = $field->childstruct;
              Formity::myform_set_values($field->children, $values[$key], $force, $nivel + 1, $change);
            }
          }
        } else {
          if ($field->type != 'label') {
            $field->setValue($values[$key], $force);
            if ($change && $field->on_change && !is_null($field->on_change_call)) {
              ($field->on_change_call)($form, $field);
            }
          }
        }
      }
    }
  }
  function isValid(&$error = null)
	{
		if(!$this->byRequest($error)) {
			return false;
		}
    $error = $this->_error;
    return $this->is_valid;
	}
	public function valid() {
		$error = null;
		return $this->isValid($error);
	}
	function error() {
		return $this->_error;
	}
  function addField($key, $type = 'input:text', $analyze = null)
  {
    $div = ':';
    $keyz = $key;
    if (strpos($keyz, $div) === false) {
      $keyz .= $div;
    }
    list($keyz, $name) = explode($div, $keyz);
    $keyz = trim($keyz, '?');
    if (isset($this->fields[$keyz])) {
      throw new \Exception('Formity2: El campo ' . $keyz . ' ya existe');
      return false;
    }
    return $this->fields[$keyz] = new FormityField($this, $key, $type, $analyze);
  }
  function setPreData($data, $force = true)
  {
    if (empty($data) || !is_array($data)) {
      return false;
    }
    foreach ($this->fields as $k => $f) {
      if ($f instanceof FormityField) {
        if (array_key_exists($k, $data)) {
          if ($f->isForm()) {
            //echo "viene1111>>>>";
            $counnt = @count($data[$k]);
            $f->declareChildren($counnt);
            $fgetChildren = empty($f->getChildren()) ? [] : $f->getChildren();
            foreach ($fgetChildren as $k2 => $f2) {
              if (!empty($data[$k][$k2])) {
                $f2->setPreData($data[$k][$k2]);
              }
            }
          } else {
            $f->setValue($data[$k], $force);
            $f->seteo = false;
          }
        }
      }
    }
  }
  function setPreDataParams($data, $force = true)
  {
    if (empty($data) || !is_array($data)) {
      return false;
    }
    foreach ($this->fields as $k => $f) {
      if ($f instanceof FormityField) {
        if (array_key_exists($k, $data)) {
          $f->setValue($data[$k]['value'], $force);
          $f->seteo   = $data[$k]['seteo'];
          $f->confirm = $data[$k]['confirm'];
        }
      }
    }
  }
  function removeField($key)
  {
    $this->fields[$key] = null;
    unset($this->fields[$key]);
  }
  function disableField($key, $b = true)
  {
    if ($this->mform->is_ajax) {
      $this->mform->ajax_response[] = "formity_set_disable('" . $this->getNameRequest() . "', " . json_encode($b) . ");";
    }
    $this->fields[$key]->disabled = $b;
  }
  function disableFields($p)
  {
    if (!empty($p)) {
      foreach ($p as $key => $v) {
        $this->disableField($key, !empty($v));
      }
    }
  }
  #  function setConfirm($k, $c) { #Usado en BOT
  #    $this->estructura['fields']['fields'][$k]['confirm'] = $c;
  #  }
  function filterValues(&$form)
  {
    return array_filter($this->fields, function ($n) {
      if (!empty($n['children'])) {
        if (!empty($n['childrang'])) {
          return array_map(function ($n) {
            return $this->filterValues($n);
          }, $n['children']);
        } else {
          return $this->filterValues($n['children']);
        }
      }
      return !empty($n['seteo']);
    });
  }
  function getFields()
  {
    return $this->fields;
  }
  function field($n) {
    return $this->getField($n);
  }
  function getField($n)
  {
    return array_key_exists($n, $this->fields) ? $this->fields[$n] : false;
  }
  function setField($n, $v)
  {
    $this->fields[$n] = $n;
	}
	function data() {
		return (object) $this->getData();
	}
  function getData($onlySet = false)
  {
    $fields = array_filter($this->fields, function ($n) use ($onlySet) {
      if ($n->extra == 'file' && !is_array($n->value) && !empty($n->value)) {
        return false;
      } elseif ($n->extra == 'file' && $n->required && empty($n->value)) {
        return false;
      }
      return $onlySet ? $n->seteo : true;
      if ($onlySet) {
        return empty($n->disabled) && $n->seteo;
      }
      return empty($n->disabled);
    });
    $rp = array_map(function ($n) use ($onlySet) {
      if (!empty($n->children)) {
        if (!empty($n->childrang)) {
          return array_map(function ($n) use ($onlySet) {
            return $n->getData($onlySet);
          }, $n->children);
        } else {
          return $this->children->getData($onlySet);
        }
      }
      #echo $n->key . ':' . $n->extra . ($n->seteo ? 1 : 0) . ' => ' . json_encode($n->value) . "<br />";
      return $n->value;
    }, $fields);

    if (!empty($original) && is_array($original)) {
      $original = array_distinct($rp, $original);
      if (!empty($original)) {
        foreach ($original as $key => $value) {
          $modified[] = $this->fields[$key]->name;
        }
      }
    }
    return $rp;
  }
  function getDataParams($onlySet = false)
  {
    $fields = array_filter($this->fields, function ($n) use ($onlySet) {
      return $onlySet ? $n->seteo : true;
      if ($onlySet) {
        return empty($n->disabled) && $n->seteo;
      }
      return empty($n->disabled);
    });
    return array_map(function ($n) use ($onlySet) {
      if (!empty($n->children)) {
        if (!empty($n->childrang)) {
          return array_map(function ($n) use ($onlySet) {
            return $n->getDataParams($onlySet);
          }, $n->children);
        } else {
          return $this->children->getDataParams($onlySet);
        }
      }
      return array(
        'seteo'   => $n->seteo,
        'confirm' => $n->confirm,
        'value'   => $n->value,
      );
    }, $fields);
  }
  function submit($url, $params = array())
  {
    $url = route($url, $params);
    /*foreach ($params as $k => $v) {
      $url = str_replace(':' . $k, $v, $url);
      unset($params[$k]);
		}*/
    $this->url = $url;
    return $this;
  }
  function begin($attrs = [])
  {
    if(empty($this->url)) {
      $this->url = route()->current();
    }
    return $this->buildHeader('POST', $this->url, $attrs);
	}
	function attr($key, $value) {
		$this->attrs[$key] = $value;
		return $this;
	}
  function buildHeader($method = 'POST', $url = null, $attrs = [])
	{
		$attrs = array_merge($this->attrs, $attrs);
    $attrs['id'] = $this->id;
    $attrs['data-id'] = $this->id;
    $attrs['data-base'] = ''; #Route::uri(null, null, null, '');
    if (!empty($this->url)) {
      $attrs['action'] = $this->url;
    } elseif (!request()->by('modal')) {
      $attrs['action'] = '#' . $this->id;
    }
    if (!empty($url)) {
			$attrs['action'] = $url;
    }
    if (!empty($this->file)) {
      $attrs['enctype'] = 'multipart/form-data';
    }
    if (!empty($attrs)) {
      array_walk($attrs, function (&$n, $k) {
        $n = $k . '="' . $n . '"';
      });
    }
    $attrs = !empty($attrs) ? implode(' ', $attrs) : '';

    $html = '<!-- Generado Automaticamento -->';
    $html .= '<form data-formity popup-form method="' . $this->method . '" ' . $attrs . '>';
    $html .= '<input type="hidden" name="' . $this->nToken . '" value="' . $this->token . '" />';
    if (!empty($this->uniqueId)) {
      $html .= '<input type="hidden" name="' . $this->nToken . 'id" value="' . $this->uniqueId . '" />';
    }
    return $html;
  }
  //function buildHTML($key, $attrs = null) {
  //    return $this->fields[$key]->buildHTML($attrs);
  //  }
  function end()
  {
    return $this->buildFooter();
  }
  function buildFooter()
  {
    $html = '<!-- Generado Automaticamento -->';
    $html .= '</form>';
    return $html;
  }
  function buildButtons()
  {
    $html = $this->message;
    foreach ($this->buttons as $k => $b) {
      $html .= '<button type="submit" name="fmt_bn" value="' . $k . '" class="btn btn-primary waves-effect waves-light me-1">';
      $html .= $b;
      $html .= '</button>';
    }
    return $html;
  }
  private static function _renderFormity($form, $onlyFields, $nivel = 0)
  {
    $rp = '';
    $par = $nivel % 2 === 0;
    if ($nivel == 0 && !$onlyFields) {
      $rp .= $form->begin();
      /* if(ES_POPY) {
        $rp .= '<div style="margin-top: 15px;text-align: right;font-size: 12px;">';
        $rp .= $form->message;
        $rp .= '<button class="button" type="submit">' . $form->button . '</button>';
        $rp .= '</div>';
      } */
    }
    if (!empty($form->_error)) {
      $rp .= '<article class="message is-danger"><div class="message-header">Debes seguir estas indicaciones:</div>';
      $rp .= '<div class="message-body"><div class="content"><ul style="margin-top:0px;">';
      foreach ($form->_error as $e) {
        $rp .= "<li>" . $e . "</li>\n";
      }
      $rp .= "</ul></div></div></article>";
    }
    $rp .= '<div style="padding: 4px 4px;padding-bottom: 0;">';
    #    $rp .= '<div class="columns is-multiline">';
    foreach ($form->getFields() as $key => $field) {
      if (!$field->isForm()) {
        if ($field->extra == 'hidden') {
          $rp .= '<div style="display:none;">';
          $rp .= $field->render() . "\n";
          $rp .= '</div>';
        } else {
          #          $rp .= '<div class="column is-' . $field->size . '">';
          $rp .= '<div class="form-group">';
          $rp .= '<label class="form-label">' . $field->name . "</label>\n";
          $rp .= '<div>';
          $rp .= $field->render() . "\n";
          $rp .= '</div>';
        }
      } else {
        #        $rp .= '<div class="column is-' . $field->size . '">';
        $rp .= '<div class="flex flex-col sm:flex-row items-center mt-3">';
        if ($field->isForm()) {
          $rp .= '<label class="w-full sm:w-20 sm:text-right sm:mr-5">' . $field->name . "\n";
          if ($field->childrangchange) {
            #$rp .= '<span id="addMore" style="float: right;margin: 0 25px;font-size: 11px;cursor:pointer;">[NUEVO ELEMENTO]</span>';
            $rp .= '<a class="button is-small is-rounded" id="addMore_' . $field->getNameRequest() . '"><i class="material-icons">add</i></a>';
          }
          $rp .= '</label>';
          $rp .= $field->render() . "\n";
        }
      }
      #      $rp .= '</div>';
      $rp .= '</div>';
    }
    if ($nivel == 0 && !$onlyFields) {
      $rp .= '<div class="column is-12" style="margin-top: 15px;text-align: right;font-size: 12px;">';
      $rp .= $form->buildButtons();
      $rp .= '</div>';
      $rp .= $form->end();
    }
    $rp .= '</div>';
    #    $rp .= '</div>';
    return $rp;
  }
  function renderFormity()
  {
    echo static::_renderFormity($this, $this->onlyFields);
  }
  function render()
  {
    $rp = '';
    if (request()->by('modal')) {
      $rp .= ''; #Route::renderNav();
    }
    if (!empty($this->title)) {
      $rp .= '<h2>' . $this->title . '</h2>';
    }
    if (!empty($this->description)) {
      $rp .= '<p class="description">' . $this->description . '</p>';
    }
    #$rp .= Route::renderErrors();
    $rp .= '<div>';
    $rp .= static::_renderFormity($this, $this->onlyFields);
    $rp .= '</div>';
    return $rp;
  }
}
