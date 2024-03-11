<?php
namespace Core;
use Core\JSON;

class FormityField
{

  public $name = null;
  public $key  = null;
  #  public $nameRequest = null;
  public $value   = null;
  public $label   = null;
  public $seteo   = false;
  public $confirm = false;
  public $disabled   = false;
  public $type  = null;
  public $extra = null;
  public $size = 12;
  private $icon = null;
  private $regex = null;
  private $__attrs = [];
  private $length_min = 0;
  private $length_step = '0.01';
  private $length_max = 100;
  private $options    = null;
  private $options_cb = false;
  public $required   = true;
  private $is_valid   = null;
  private $callback   = null;
  private $depend = array();
  public $_error      = array();
  private $html       = '';
  public $childrang = null;
  public $childrangchange = true;
  public $childstruct = null;
  public $children   = null;

  public $on_change = false;
  public $on_change_call = null;
  public $parent = null;
  public $mform = null;

  public $index = null;

  function getClone($i = 0)
  {
    $r = clone $this;
    if (!empty($r->children)) {
      $i = 0;
      $r->children = array_map(function ($f) use (&$i) {
        #        $f->number_child = ++$i;
        return $f;
      }, $r->children);
    }
    #    if(!is_null($r->childstruct)) {
    #      $r->childstruct->number_child = 12;
    #    }
    return $r;
  }
  function __construct($form, $key, $type, $analyze)
  {
    $this->mform = $form;
    $this->parent = $form;
    $div = ':';
    if (strpos($key, $div) === false) {
      $key .= $div;
    }
    list($key, $name) = explode($div, $key);
    $this->required = substr($key, -1) != '?';
    $key = trim($key, '?');
    $name = !empty($name) ? $name : ucfirst(str_replace('_', ' ', strtolower($key)));
    if (isset($form->fields[$key])) {
      #      return false;
    }
    $this->key  = $key;
    $this->name = $name;
    #    $this->nameRequest = $form->id . $form->number_child . $key;

    $div = ':';
    $cr = null;
    $extra = '';
    if($type == 'string') {
      $type = 'text';
    }
    if ($type instanceof Formity) {
      $this->childstruct = $type;
      $type->repeat = true;
      $type->parentForm = $form;
      #      $this->childstruct->parentForm = $form;
      $type = 'form';
      $cr = $analyze;
      blade()->asset('/js/formity.add.js', 'formity.add');
      blade()->asset('/css/formity.add.css');
    } else {
      if (strpos($type, $div) === false) {
        $type .= $div;
      }
      list($type, $extra) = explode($div, $type);
    }
    $indice = ++$form->assigned_id;
    $this->type  = $type;
    $this->extra = $extra;

    if ($this->type === 'label') {
      $this->disabled = true;
      $this->setValue($analyze, true);

      #    } elseif($this->type == 'autocomplete') {
      #      $this->type = 'text';
      #      $this->extra = 'autocomplete';

		} elseif ($this->extra == 'autocomplete') {
      blade()->asset('/assets/libs/jquery-ui/jquery-ui.min.js');
      blade()->asset('/assets/libs/jquery-ui/jquery-ui.min.css');
    } elseif ($this->type == 'panel') {
      blade()->asset('/js/formity.panel.js', 'formity.panel');
    } elseif ($this->type == 'tree') {
      blade()->asset('/js/formity.tree.js', 'formity.tree');
      blade()->asset('/css/formity.tree.css');
      require_once(ABS_LIBRERIAS . 'formity.tree.php');
    } elseif ($this->type == 'word') {
      blade()->asset('/assets/libs/trumbowyg/trumbowyg.min.js');
      blade()->asset('/assets/libs/trumbowyg/trumbowyg.table.js');
      #Route::JS('/js/trumbowyg.pasteembed.js');
      blade()->asset('/assets/libs/trumbowyg/trumbowyg.min.css');
      blade()->asset('/assets/libs/trumbowyg/trumbowyg.table.css');
    } elseif ($this->type == 'textarea' && $this->extra == 'tags') {
      blade()->asset('/js/jquery.tagsinput.js');
      blade()->asset('/css/jquery.tagsinput.css');
    }
    if (!empty($cr)) {
      if (is_numeric($cr)) {
        $cr = $cr . '-' . $cr . ':' . $cr;
        $this->childrangchange = false;
      } else {
        blade()->asset('/js/formity.add.js', 'formity.add');
      }
      $pcr = explode(':', $cr);
      $this->childrang = $pcr[0];
      $rang = explode('-', $pcr[0]);
      $this->length_min = $rang[0];
      $this->length_max = $rang[1];
      //echo "viene55555>>>>" . json_encode($pcr);
      $this->declareChildren(isset($pcr[1]) ? $pcr[1] : $rang[0]);
    }
    if (in_array($extra, array('file', 'files'))) {
      $form->file = true;
    }
    $this->index = count($form->fields) + 1;
    return $this;
  }
  function setIcon($x)
  {
    $this->icon = $x;
    return $this;
  }
  function getIcon()
  {
    return $this->icon;
  }
  function setDepend($f)
  {
    if ($f instanceof FormityField) {
      $this->depend[] = $f;
    } elseif (($f = $this->mform->getField($f)) !== false) {
      $this->depend[] = $f;
    } else {
      _404('depend-no-existe');
    }
    return $this;
  }
  function getNameRequest()
  {
    if (!$this->mform->obfuscate) {
      return $this->key;
    } else {
      return Formity::hash($this->mform->id . $this->key);
    }
  }
  function declareChildren($cantidad, &$error = '')
  {
    //echo "declareChildren " . ($this->name) . ": " . $cantidad . "<br>\n";
    $ce = $this;
    if ($this->getMin() > $cantidad || $this->getMax() < $cantidad) {
      $error = 'La cantidad es inválida';
      return false;
    }
    if ($cantidad > 0) {
      $this->children = range(1, $cantidad);
      $i = 0;
      $this->children = array_map(function ($n) use ($ce, &$i) {
        #      $ce->childstruct->number_child = ++$i;
        $r = $ce->childstruct->getClone();
        #      $r->parentForm = $ce->parentForm;
        $r->number_child = ++$i;
        return $r;
      }, $this->children);
    }
    return true;
  }
  function setAttr($attr)
  {
    $this->__attrs = $attr;
    return $this;
  }
  function setSize($n)
  {
    $this->size = $n;
    return $this;
  }
  function setRegex($r)
  {
    $this->regex = $r;
    return $this;
  }
  function setStep($n)
  {
    $this->length_step = $n;
    return $this;
  }
  function min($n) {
    return $this->setMin($n);
  }
  function max($n) {
    return $this->setMax($n);
  }
  function setMin($n)
  {
    $this->length_min = $n;
    if ($this->mform->is_ajax) {
      $this->mform->ajax_response[] = "formity_set_min('" . $this->getNameRequest() . "', " . $n . ");";
    }
    return $this;
  }
  function setMax($n)
  {
    $this->length_max = $n;
    if ($this->mform->is_ajax) {
      $this->mform->ajax_response[] = "formity_set_max('" . $this->getNameRequest() . "', " . $n . ");";
    }
    return $this;
  }

  function setLength($n)
  {
    $this->setMin($n);
    $this->setMax($n);
  }

  function setSizeLength($n)
  {
    $k = $n - 1;
    $min = 10 ** ($k);
    $max = 0;
    for ($i = 0; $i <= $k; $i++) {
      $m = 10 ** $i;
      $max += $m;
    }
    $max = 9 * $max;
    $this->setMin($min);
    $this->setMax($max);
  }

  function getMin()
  {
    return $this->length_min;
  }
  function getMax()
  {
    return $this->length_max;
  }
  function countFields()
  {
    return count($this->fields);
  }
  function clear()
  {
    $this->_error = null;
    $this->seteo = false;
    $this->value = null;
  }

  function setTextLabel($value)
  {
    if ($this->mform->is_ajax) {
      $this->mform->ajax_response[] = "formity_setTextLabel('" . $this->getNameRequest() . "', '" . $value . "');";
    }
  }

  function setValue($value, $force = false, $main_form = null)
  {
    $main_form = !is_null($main_form) ? $main_form : $this->mform;
    /*if(!empty($_GET['_ft']) && $_GET['_ft'] == $this->getNameRequest()) {
      if(is_callable($value)) {
        //_404('Value de ' . $this->key . ' debe ser callback');
        if($this->mform->byRequest('POST')) {
          $value = $value($this->mform, $this);
          echo "formity_set_value('" . $this->getNameRequest() . "', ";
          echo json_encode($value);
          echo ");";
          exit;
        }
      }
  } */
    if ($this->isForm()) {
      if (!empty($value) && is_array($value)) {
        //echo "viene44>>>>>";
        $this->declareChildren(count($value));
        $trp = array();
        foreach ($this->getChildren() as $k => $f) {
          if (isset($value[$k])) {
            $rp = array();
            foreach ($f->getFields() as $k2 => $f2) {
              if ($f2 instanceof FormityField) {
                if (array_key_exists($k2, $value[$k])) {
                  $rp[$f2->getNameRequest()] = $value[$k][$k2];
                  #$f2->setValue($value[$k][$k2], true, $main_form);
                }
              }
            }
            $trp[] = $rp;
          }
        }
        if ($main_form->is_ajax) {
          $main_form->ajax_response[] = "formity_set_value('" . $this->getNameRequest() . "', " . JSON::encode($trp) . ");";
        }
      }
      return $this;
    }
    if (is_callable($value) && !is_string($value)) {
      $value = $value($this->mform, $this);
    }

    $value = is_array($value) ? $value : trim((string)$value);
    if (is_null($value) || $value == '' ||  (is_array($value) && count($value) == 0)) {
      $value = null;
		}
//		echo "vv: " . $value . '/' . $this->name;
    $rp = false;
    if ($force || $this->validarValue($value)) {
      if ($main_form->is_ajax) {
        $main_form->ajax_response[] = "formity_set_value('" . $this->getNameRequest() . "', " . JSON::encode($value) . ");";
      }
      if (!($this->extra == 'file' && !empty($this->value) && !$this->seteo)) {
        $this->seteo = true;
      }
      $this->value = $value;
      $rp = true;
    } else {
      if (!empty($this->_error)) {
        foreach ($this->_error as $e) {
          $this->mform->_error[] = $e;
        }
      }
    }
    return $this;
  }
  function setLabel($n)
  {
    $this->label = $n;
    return $n;
  }
  function getLabel()
  {
    return is_null($this->label) ? $this->value : $this->label;
  }
  function getValue()
  {
    return $this->value;
  }
  private function checkOnline()
  {
    if (!empty($_GET['_ft']) && $_GET['_ft'] == $this->getNameRequest()) {
      if (!is_callable($options)) {
        _404('setOptions de ' . $this->key . ' debe ser callback');
      }
      if ($this->type == 'tree') {
        if (in_array($_POST['_mtree'], array('ls', 'top', 'bottom'))) {
          if (!is_numeric($_POST['_mnivel'])) {
            _404('no-es-numeric2');
          }
          $id = !empty($_POST['_mid']) ? $_POST['_mid'] : null;
          return_json($options($this->mform, $this, $_POST['_mtree'], $_POST['_mnivel'], $id));
        }
      } elseif (is_callable($value)) {
        //_404('Value de ' . $this->key . ' debe ser callback');
        if ($this->mform->byRequest('POST')) {
          $value = $value($this->mform, $this);
          echo "formity_set_value('" . $this->getNameRequest() . "', ";
          echo JSON::encode($value);
          echo ");";
          exit;
        }
      } elseif ($this->mform->byRequest('POST')) {
        if (true) {
          $options = $options($this->mform, $this);
          echo "formity_fill_select('" . $this->getNameRequest() . "', ";
          if (!empty($options)) {
            array_walk($options, function (&$v, $k) {
              $v = array(
                'id'  => $k,
                'val' => $v
              );
            });
            $options = array_values($options);
          }
          echo JSON::encode($options);
          echo ");";
          exit;
        }
      }
    }
  }
  function onChange($cb = null)
  {
    if (is_null($cb)) {
      if ($this->mform->is_ajax) {
        $this->mform->ajax_response[] = "formity_on_change('" . $this->getNameRequest() . "');";
      }
      return $this;
    }
    $this->on_change = true;
    $this->on_change_call = $cb;
    if (!empty($_GET['_ft']) && $_GET['_ft'] == $this->getNameRequest()) {
      if (is_callable($cb)) {
        if ($this->mform->byRequest('POST')) {
          $this->mform->is_ajax = true;
          $cb($this->mform, $this);
          echo implode("\n", $this->mform->ajax_response);
          exit;
        }
      }
    }
  }
  function range($a, $b)
  {
    $ls = range($a, $b);
    $ls = array_combine($ls, $ls);
    return $this->setOptions($ls);
  }
  function setPanelEdit($cb)
  {
    if (!is_callable($cb)) {
      return false;
    }
    if (!empty($_GET['_fp']) && $_GET['_fp'] == $this->getNameRequest()) {
      Route::data('submenu', null);
      $cb($_GET['_fpi']);
      exit;
    }
  }
  function setOptions($options, $group = null)
  {
    if ($this->type == 'panel') {
      if (is_null($group)) {
        return false;
      }
      $op = $this->getOptions();
      $op[$group] = $options;
      $options = $op;
      unset($op);
    }
    if (is_callable($options)) {
      $this->options_cb = true;
    }
    if (!empty($_GET['aip']) && $_GET['aip'] == $this->getNameRequest()) {
      if ($this->mform->byRequest('POST') && $this->extra == 'autocomplete') {
        $term = !empty($_GET['term']) ? $_GET['term'] : null;
        return_json($options($this->mform, $this, $term));
      }
    }
    if (!empty($_GET['_ft']) && $_GET['_ft'] == $this->getNameRequest()) {
      if (!is_callable($options)) {
        #_404('setOptions de ' . $this->key . ' debe ser callback');
      }
      if ($this->mform->byRequest('POST') && $this->type == 'tree') {
        if (isset($_POST['_mtree'])) {
          if (in_array($_POST['_mtree'], array('ls', 'top', 'bottom'))) {
            if (!is_numeric($_POST['_mnivel'])) {
              _404('no-es-numeric');
            }
            $id = !empty($_POST['_mid']) ? $_POST['_mid'] : null;
            return_json($options($this->mform, $this, $_POST['_mtree'], $_POST['_mnivel'], $id));
          }
        }
      } elseif ($this->mform->byRequest('POST')) {
        if (is_callable($options)) {
          $options = $options($this->mform, $this);
          echo "formity_fill_select('" . $this->getNameRequest() . "', ";
          if (!empty($options)) {
            array_walk($options, function (&$v, $k) {
              $v = array(
                'id'  => $k,
                'val' => $v
              );
            });
            $options = array_values($options);
          }
          echo JSON::encode($options);
          echo ");";
          exit;
        }
      }
      /* elseif($this->mform->byRequest('POST')) {
        if(true) {
          $options = $options($this->mform, $this);
          echo "formity_fill_select('" . $this->getNameRequest() . "', ";
          if(!empty($options)) {
            array_walk($options, function(&$v, $k) {
              $v = array(
                'id'  => $k,
                'val' => $v
              );
            });
            $options = array_values($options);
          }
          echo json_encode($options);
          echo ");";
          exit;
        }
      } */
    }
    if (!in_array($this->type, array('tree')) && !in_array($this->extra, array('autocomplete'))) {
      $this->options = is_callable($options) ? $options($this->mform, $this) : $options;
    }
    if ($this->mform->is_ajax) {
      $this->mform->ajax_response[] = "formity_set_options('" . $this->getNameRequest() . "', " . JSON::encode($this->options) . ");";
    }
    return $this;
  }
  function disabled($t = -1)
  {
    if ($t === -1) {
      return $this->disabled;
    }
    $this->disabled = !!$t;
    if ($this->mform->is_ajax) {
      $this->mform->ajax_response[] = "formity_set_disabled('" . $this->getNameRequest() . "', " . (!!$t ? 1 : 0) . ");";
    }
    return $this;
  }
  function getOptions()
  {
    return $this->options;
  }
  function setCallBack($cb)
  {
    $this->callback = $cb;
  }
  function getCallBack()
  {
    return $this->callback;
  }
  function isForm()
  {
    return $this->childstruct instanceof Formity;
  }
  function getChildren()
  {
    return $this->children;
  }
  function validarValue($value)
  {
    $this->_error = array();
    if (!empty($this->childstruct) &&  !empty($this->required) && empty($this->children)) {
      $this->_error[] = $this->name . ' es requerido.';
      goto saltar;
    }
    if ($this->type == 'input' && $this->extra == 'file') {
      if (!empty($this->required)) {
        if (empty($value['size']) || !empty($value['error'])) {
          if (!(!empty($this->value) && !$this->seteo)) {
            $this->_error[] = $this->name . ' es inválido. (#1)';
          }
        }
      } elseif (!empty($value)) {
        if (empty($value['size'])) {
          $this->_error[] = $this->name . ' es un fichero vacío';
        } elseif (!empty($value['error'])) {
          $this->_error[] = $this->name . ': ' . $value['error'];
        }
      }
    } elseif ($this->type == 'input' && $this->extra == 'files') {
      if (!empty($this->required)) {
        foreach ($value as $k => $v) {
          if (empty($v['size']) || !empty($v['error'])) {
            $this->_error[] = $this->name . '#' . ($k + 1) . ' es inválido. (#1)';
          }
        }
      } elseif (!empty($value)) {
        foreach ($value as $k => $v) {
          if (empty($v['size'])) {
            $this->_error[] = $this->name . '#' . ($k + 1) . ' es un fichero vacío';
          } elseif (!empty($v['error'])) {
            $this->_error[] = $this->name . '#' . ($k + 1) . ': ' . $v['error'];
          }
        }
      }
    } elseif ((is_null($value) || $value == '') && !empty($this->required)) {
      $this->_error[] = $this->name . ' es requerido. (v: ' . $value .')';
    }
    if ($this->type == 'input' && $this->extra == 'date') {
      if (!empty($value) || $this->required) {
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value)) { //YYYY-MM-DD
          /*if(!(strtotime($value) > 0)) {
            $this->_error[] = 'Fecha Inválido. Debe ser YYYY-MM-DD, Ej. ' . date('Y-m-d');
        }*/
        } else {
          $this->_error[] = 'Fecha con formato Inválido. Debe ser YYYY-MM-DD, Ej. ' . date('Y-m-d');
        }
      }
    } elseif ($this->type == 'input' && $this->extra == 'text') {
      if (!is_null($value) && $value != '') {
        if (strlen($value) > $this->length_max) {
          $this->_error[] = 'Se ha excedido el límite de caracteres (' . strlen($value) . '/' . $this->length_max . ') ingresados en ' . $this->name;
        }
        if (!is_null($this->regex)) {
          if (!preg_match("/^" . $this->regex . "$/", $value)) {
            $this->_error[] = 'El campo ' . $this->name . ' no cumple el formato requerido';
          }
        }
      }
      if (!empty($this->required) || (empty($this->required) && (!is_null($value) || $value != ''))) {
        if (strlen(strval($value)) < $this->length_min) {
          $this->_error[] = 'El mínimo de caracteres ingresados en ' . $this->name . ' debe ser ' . $this->length_min;
        }
      }
    } elseif ($this->type == 'input' && $this->extra == 'email') {
      if (!empty($this->required) || !is_null($value)) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $this->_error[] = $this->name . ' es inválido. (#2)';
        }
      }
    } elseif ($this->type == 'select') {
      if (!empty($this->options) && !$this->options_cb) {
        if (!is_callable($this->options)) {
          #          echo "console.log('caso5', " . json_encode($this->options) . "," . $value . ");";
          $ops = array_filter($this->options, function ($n, $k) {
            return $k !== '';
          }, ARRAY_FILTER_USE_BOTH);
          if (!empty($ops) && (!empty($this->required) || !is_null($value))) {
            if (!isset($ops[$value])) {
              $this->_error[] = $this->name . ' es inválido.(#3)';
            }
          }
        }
      }
    }
    if (!is_null($this->callback) && is_callable($this->callback)) {
      $rp = $this->callback($value, $form['fields'], $error);
      if (empty($rp)) {
        $this->_error[] = $error;
      }
    }
    saltar:
    if (!empty($this->_error)) {
      $this->is_valid = true;
    }
    return empty($this->_error);
  }
  function attr($key, $value) {
    $this->__attrs[$key] = $value;
    return $this;
  }
  function attrs($ds) {
    $this->__attrs = $ds;
    return $this;
  }
  function render($input_attrs = null)
  {
    $ce = &$this;
    $attrs = !empty($input_attrs) && is_array($input_attrs) ? $input_attrs : array();

    $attrs = array_merge($this->__attrs, $attrs);

    $attrs['data-fnn-id'] = $this->mform->id . '.' . $this->index;
    if (!empty($this->replace_to)) {
      $attrs['data-fnn-replace'] = $this->mform['id'] . '.' . $this->replace_to;
      $this->required = true;
    }

    if (!empty($this->depend)) {
      $attrs['data-fnn-depend'] = implode(',', array_map(function ($n) use ($ce) {
        return $ce->mform->id . '.' . $n->getNameRequest();
      }, $this->depend));
    }
    $class = array();
    //$attrs['extra'] = $this->extra;
    if (!empty($this->disabled)) {
      $attrs['data-disables'] = 'formity';
      $attrs['disabled'] = 'true'; //TODO
    }
    if (in_array($this->extra, array('number', 'int', 'range'))) {
      if (!is_null($this->length_min)) {
        $attrs['min'] = $this->length_min;
      }
      if (!is_null($this->length_step)) {
        $attrs['step'] = $this->length_step;
      }
      if (!is_null($this->length_max)) {
        $attrs['max'] = $this->length_max;
      }
    } elseif (in_array($this->extra, array('text', 'textarea','password'))) {
      if (!is_null($this->length_min)) {
        $attrs['minlength'] = $this->length_min;
      }
      if (!is_null($this->length_max)) {
        $attrs['maxlength'] = $this->length_max;
      }
    }
    if ($this->on_change) {
      $attrs['data-on-change'] = 'true';
    }
    if ($this->extra == 'file') {
      $val = $this->value;
      $this->value = '';
    } elseif ($this->extra == 'files') {
      $val = $this->value;
      $this->value = '';
      $attrs['multiple'] = '';
    } elseif ($this->type == 'multimedia') {
      $class[] = 'input';
    }

    if (in_array($this->type, ['input', 'textarea', 'select', 'boolean','number','decimal'])) {
      $class[] = 'form-control';
    } else {
      $class[] = $this->type;
    }

    # elseif($this->extra == 'autocomplete') {
    #   $attrs['data-autocomplete'] = 'true';
    # }
    $class = implode(' ', $class);
    $attrs['class'] = !empty($attrs['class']) ? $attrs['class'] . ' ' . $class : $class;


    $omitir_required = $this->type == 'input' && in_array($this->extra, array('file', 'files')) && !empty($val) && !is_array($val);
    //$attrs['data-fnn-required'] = !empty($this->required) && !$omitir_required ? 1 : 0;
    //$attrs['required'] = !empty($this->required) && !$omitir_required ? 1 : 0;
    if (!empty($this->required) && !$omitir_required) {
      $attrs['required'] = 'true';
    }

    array_walk($attrs, function (&$n, $k) {
      $n = $k . '="' . $n . '"';
    });

    $attrs = !empty($attrs) ? implode(' ', $attrs) : '';
    $keyw = $this->getNameRequest() . (!empty($this->mform->repeat) || $this->type == 'checkbox' || $this->extra == 'files' ? '[]' : '');
    $h = '';
    $extra = $this->extra;

    if (is_callable($this->options)) {
      //var_dump($this->options);exit;
      //$this->options = $this->options($this->mform, $this);
    }
    $extra = $this->extra;

    if ($this->extra == 'autocomplete') {
      $extra = 'text';
      if ($this->type == 'input') {
        $h .= '<input type="hidden" name="' . $keyw . '" id="val_' . $this->getNameRequest() . '" value="' . htmlentities(strval($this->getValue())) . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '" />';
        $h .= '<input type="' . $this->type . '" id="ip_' . $this->getNameRequest() . '" value="' . htmlentities(strval($this->getLabel())) . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '" />';
      } else {
        $h .= '<textarea name="' . $keyw . '" id="ip_' . $this->getNameRequest() . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '">' . @htmlentities($this->value) . '</textarea>';
      }
			$h .= "<script> require('/assets/libs/jquery-ui/jquery-ui.min.js', function() { $('#ip_" . $this->getNameRequest() . "').autocomplete({ source: function (request, response) { \n";
			$uri = $this->mform->url->query('aip', $this->getNameRequest())->link();
      $h .= "$.ajax({ type: \"POST\", url: \"" . $uri . "&term=\" + request.term,";
      $h .= "data: new FormData($(\"[data-id='" . $this->mform->id . "']\")[0]), contentType: false, processData: false, success: response, dataType: 'json' }); }, minLength: 2, ";
      $h .= "select: function( event, ui ) {\n";
      $h .= "event.preventDefault();\n";
      $h .= "if(typeof ui.item.label !== 'undefined') { $('#ip_" . $this->getNameRequest() . "').val(ui.item.label); }\n";
      $h .= "$('#val_" . $this->getNameRequest() . "').val(ui.item.id);\n";
      #      $h .= "$(\"[name='" . $this->getNameRequest() . "']\").val(ui.item.id);\n";
      $h .= "console.log( \"Selected: \", ui.item);";
      foreach ($this->mform->fields as $f) {
        $h .= "if(typeof ui.item.{$f->key} !== 'undefined') { $(\"[name='{$f->getNameRequest()}']\").val(ui.item.{$f->key}); }\n";
      }
      $h .= "console.log( \"Selected: \" + ui.item.value + \" aka \" + ui.item.id );";
      $h .= "}, \n";
      $h .= "focus: function( event, ui ) {\n";
      $h .= "event.preventDefault();\n";
      $h .= "$(\"textarea[name='" . $this->getNameRequest() . "']\").val(ui.item.id);\n";
      $h .= "}, \n";
      $h .= " }); }); </script>";
    } elseif (in_array($this->type, ['input', 'integer', 'decimal'])) {
      if (in_array($this->type, ['integer', 'decimal'])) {
        $extra = 'number';
        $attrs .= ' step="0.00001" autocomplete="off"';
      } elseif ($extra == 'date') {
        $this->value = !empty($this->value) ? date('Y-m-d', strtotime($this->value)) : '';
      } elseif ($extra == 'datetime-local') {
        $this->value = str_replace(' ', 'T', $this->value);
      } elseif ($extra == 'files') {
        $extra = 'file';
      } elseif ($extra == 'range') {
        $attrs .= " oninput=\"$('#val_" . $this->getNameRequest() . "').text(fmt_duration(this.value));\"";
      }
      if ($this->regex !== null) {
        $attrs .= " pattern=\"" . $this->regex . "\"";
      }
      $value = is_string($this->value) ? htmlentities($this->value) : '';
      $h .= '<input type="' . $extra . '" name="' . $keyw . '" id="ip_' . $this->getNameRequest() . '" value="' . $value . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '" />';
      if ($extra == 'range') {
        $h .= '<div id="val_' . $this->getNameRequest() . '" style="font-size: .7rem;text-align: right;position: relative;top: -15px;right: 5px;"></div>';
        $h .= '<script>document.getElementById("val_' . $this->getNameRequest() . '").innerHTML = fmt_duration(\'' . $this->value . '\');</script>';
      }
      if (in_array($this->extra, array('file', 'files')) && !empty($val)) {
        $val = is_array($val) ? $val : array($val);
        foreach ($val as $r) {
          $h .= '<div style="text-align: center;background: #429eff;color: #fff;"><a href="' . $r . '"  target="_blank">Descargar ' . strtoupper(pathinfo($r, PATHINFO_EXTENSION)) . '</a></div>';
        }
      }
    } elseif ($this->type == 'textarea') {
      $h .= '<textarea id="tags_' . $keyw . '" name="' . $keyw . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '">' . htmlentities($this->value ?? '') . '</textarea>';
      if ($this->extra == 'tags') {
        $h .= '<script>';
        $h .= '$(function() { ';
        $h .= '$("#tags_' . $keyw . '").tagsInput({width:\'auto\',  \'onAddTag\': function(input, value) { console.log(\'tag added\'); $("#tags_' . $keyw . '").change(); } });';
        $h .= ' })';
        $h .= '</script>';
      }
    } elseif ($this->type == 'word') {
      $h .= '<div>';
      $this->value = strval($this->value);
      $h .= '<textarea id="wysiwyg_' . $keyw . '" name="' . $keyw . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '">' . htmlentities($this->value) . '</textarea>';
      $h .= "<script> require('/assets/js/jquery-ui.min.js', function() { $('#wysiwyg_" . $keyw . "').trumbowyg({ closable: true, ";
      $h .= 'btns:[["viewHTML"],["undo","redo"],["formatting"],["strong","em","del"],["superscript","subscript"],["link"],["insertImage"],["justifyLeft","justifyCenter","justifyRight","justifyFull"],["unorderedList","orderedList"],["horizontalRule"],["removeformat"],["fullscreen"],["table"]],';
      $h .= "plugins: { table: { } } }); $('#wysiwyg_" . $keyw . "').on('tbwchange', (e) => { $(e.target).change(); }) })</script>";
      $h .= '</div>';
    } elseif ($this->type == 'panel') {
      $h .= '<nav class="panel">';
      $h .= '<p class="panel-heading">' . $this->name . '</p>';
      $h .= '<p class="panel-tabs">';
      $h .= '<a class="tab is-active etiqueta-seleccionados" onclick="openTab(event,\'tab01\')">Seleccionados</a>';
      if (!empty($this->options)) {
        $j = 1;
        foreach ($this->options as $k => $o) {
          $j++;
          $h .= '<a class="tab" onclick="openTab(event,\'tab0' . $j . '\')">' . $k . ' (' . count($o) . ')</a>';
        }
      }
      $h .= '</p>';
      $h .= '<div id="ft_panel_' . $keyw . '" class="panel-pages">';
      $h .= '<div class="content-tab library-selected" id="tab01">';
      $h .= '</div>';
      if (!empty($this->options)) {
        $j = 1;
        foreach ($this->options as $k => $o) {
          $j++;
          $h .= '<div class="content-tab library" id="tab0' . $j . '" style="display:none;">';
          if (!empty($o)) {
            foreach ($o as $v) {
              $linkOp = Route::uri(null, DOMINIO_ACTUAL, SUBDOMINIO_ACTUAL, '_fp=' . $this->getNameRequest() . '&_fpi=' . $v['id']);
              $s = is_null($this->value) ? '' : (!in_array($v['id'], (array) $this->value) ? '' : ' checked="checked"');
              $h .= '<label class="panel-block" data-return="tab0' . $j . '">';
              $h .= '<div class="columns" style="width: 100%;">';
              $h .= '<div class="column">';
              $h .= '<input type="checkbox" name="' . $keyw . '[' . $v['id'] . ']" value="' . $v['id'] . '"' . $s . '>';
              $h .= $v['rotulo'];
              $h .= '</div>';
              if (!empty($v['info']) || !empty($v['link'])) {
                $h .= '<div class="buttons has-addons is-right are-small">';
                if (!empty($v['info'])) {
                  $h .= '<span class="button">' . $v['info'] . '</span>';
                }
                if (!empty($v['link'])) {
                  $h .= '<a class="button is-info" href="' . $linkOp . '" data-Popup>Editar</a>';
                }
                $h .= '</div>';
              }
              $h .= '</div>';
              $h .= '</label>';
            }
          }
          $h .= '</div>';
        }
      }
      $h .= '</div>';
      $h .= '<div class="panel-block">';
      $h .= '<button type="button" class="button is-link is-outlined is-fullwidth" onclick="javascript:$(\'.panel-pages input:checkbox\').prop(\'checked\', false);actualizar_seleccionados();">Desmarcar todo</button>';
      $h .= '</div>';
      $h .= '</nav>';

      $h .= <<<EOF
<script>
require('formity.panel', function() {
  $('#ft_panel_{$keyw}').on('click', ft_panel_refresh);
  $('#ft_panel_{$keyw}').on('click', '.buttons', function(e) {
    e.preventDefault();
  });
  ft_panel_refresh();
});
</script>
EOF;
    } elseif ($this->type == 'select') {
      $h .= '<div class="select is-fullwidth">';
      $h .= '<select name="' . $keyw . '" ' . $attrs . ' data-value="' . $this->value . '" data-name="' . $this->name . '">';
      $h .= FormityField::buildSelect($this->options, $this->value);
      $h .= '</select>';
      $h .= '</div>';
    } elseif ($this->type == 'checkbox') {
      $h .= '<ul>';
      if (!empty($this->options)) {
        foreach ($this->options as $k => $o) {
          $s = is_null($this->value) ? '' : (!in_array($k, (array) $this->value) ? '' : ' checked="checked"');
          $h .= '<li><input type="checkbox" name="' . $keyw . '" value="' . $k . '" data-name="' . $this->name . '" ' . $attrs . ' ' . $s . '> <span>' . $o . '</span></li>';
        }
      }
      $h .= '</ul>';
    } elseif ($this->type == 'radio') {
      $h .= '<ul class="columns is-multiline">';
      foreach ($this->options as $k => $o) {
        $is_image = strpos(".jpg", $o) !== false;
        $s = is_null($this->value) ? '' : (!in_array($k, (array) $this->value) ? '' : ' checked="checked"');
        $h .= '<li class="column is-12"><label><input type="radio" name="' . $keyw . '" value="' . $k . '" data-name="' . $this->name . '"' . $s . '> ';
        if (!$is_image) {
          $h .= ' <span> ' . strtoupper($o) . '</span>';
        } else {
          $h .= '<img src="' . $o . '" style="max-width:100px;max-height:100px;" />';
        }
        $h .= '</label></li>';
      }
      $h .= '</ul>';
    } elseif ($this->type == 'multimedia') {
      $u = time() . rand();
      $h .= '<input id="' . $u . '" type="hidden" name="' . $keyw . '" value="' . $this->value . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '" />';
      $h .= '<div class="columns" style="display: flex;flex-wrap: wrap;align-content: center;align-items: center;text-align: center;">';
      $h .= '<div class="column"><button class="button" data-id="' . $u . '" onclick="selectMultimedia($(this), \'codigo\');" style="margin: 0 auto;">Buscar o Subir</button></div>';
      $h .= '<div class="column"><img id="tb' . $u . '" ' . (!empty($this->value) ? 'src="' . HOSTIMG_IMAGENES . $this->value . '"' : '') . ' style="max-width:100px;max-height:100px;" /></div>';
      $h .= '</div>';
    } elseif ($this->type == 'tree') {
      $u = time() . rand();
      $h .= '<input type="hidden" id="val_' . $u . '" name="' . $keyw . '" value="' . $this->value . '" ' . $attrs . ' data-name="' . $this->name . '" placeholder="' . $this->name . '" />';
      $h .= '<div class="treeOptions" id="' . $u . '" ' . $attrs . '></div>';
      $extra = '';
      if (!is_null($this->length_min)) {
        $extra .= 'min: ' . $this->length_min . ',';
      }
      if (!is_null($this->length_max)) {
        $extra .= 'max: ' . $this->length_max . ',';
      }
			$linkOp = $this->mform->url->query('_ft', $this->getNameRequest())->link();
      $h .= <<<EOF
        <script> FormityTree($("#{$u}"), {url: '{$linkOp}', value: 'val_{$u}', {$extra} }).init(); </script>
EOF;
    } elseif ($this->type == 'boolean') {
      $h .= '<select name="' . $keyw . '" ' . $attrs . ' data-value="' . $this->value . '" data-name="' . $this->name . '">';
      $h .= FormityField::buildSelect(array('0' => 'No', '1' => 'Si'), $this->value);
      $h .= '</select>';
    } elseif ($this->type == 'label') {
      $h .= '<div data-name="' . $this->name . '" ' . $attrs . '>' . $this->value . '</div>';
    } elseif ($this->type == 'form') {
      ###############################
      $keid = ''; #uniqid();
      $h .= '<div>';
      $h .= '<table class="table" id="contenedorMore_' . $keid . $this->getNameRequest() . '" data-id="' . $this->getNameRequest() . '">';
      if (!empty($this->getChildren())) {
        foreach ($this->getChildren() as $k => $f) {
          $h .= '<tbody class="joinMore_' . $keid . $this->getNameRequest() . '">';
          $h .= '<tr>';
          $h .= '<th rowspan="2" class="border indice text-center">';
          $h .= '<span class="key">' . ($k + 1) . '</span>';
          foreach ($f->getFields() as $_k => $_f) {
            if (!$_f->isForm() && $_f->extra == 'hidden') {
              $h .= "<div style='display:none;'>" . $_f->render($input_attrs) . "</div>";
            }
          }
          $h .= '</th>';
          foreach ($f->getFields() as $_k => $_f) {
            if (!$_f->isForm() && $_f->extra != 'hidden') {
              $h .= "<th class=\"border\">" . $_f->name . "</th>";
            }
          }
          $h .= '</tr>';
          $h .= '<tr>';
          foreach ($f->getFields() as $_k => $_f) {
            if (!$_f->isForm() && $_f->extra != 'hidden') {
              $h .= "<td class=\"border\">" . $_f->render($input_attrs) . "</td>";
            }
          }
          $h .= '</tr>';
          $h .= '</tbody>';
        }
      }
      if ($this->childrangchange) {
        $h .= '<tbody class="hide" id="clonar_' . $keid . $this->getNameRequest() . '">';
        $h .= '<tr>';
        $h .= '<th rowspan="2" class="border indice text-center">';
        $h .= '<span class="key">#</span>';
        foreach ($this->childstruct->getFields() as $_k => $_f) {
          if (!$_f->isForm() && $_f->extra == 'hidden') {
            $h .= "<div style='display:none;'>" . $_f->render() . "</div>";
          }
        }
        $h .= '</th>';
        foreach ($this->childstruct->getFields() as $_k => $_f) {
          if (!$_f->isForm() && $_f->extra != 'hidden') {
            $h .= "<th class=\"border\">" . $_f->name . "</th>";
          }
        }
        $h .= '</tr>';
        $h .= '<tr>';
        foreach ($this->childstruct->getFields() as $_k => $_f) {
          if (!$_f->isForm() && $_f->extra != 'hidden') {
            $h .= "<td class=\"border\">" . $_f->render() . "</td>";
          }
        }
        $h .= '</tr>';
        $h .= '</tbody>';
      }
      $h .= '</table>';
      $h .= '</div>';

      if ($this->childrangchange) {
        $h .= '<script>require(\'formity.add\', function() { ElementsAdd({ clone: "#clonar_' . $keid . $this->getNameRequest() . '", contain: "#contenedorMore_' . $keid . $this->getNameRequest() . '", plus: "#addMore_' . $keid . $this->getNameRequest() . '", min: ' . $this->length_min . ',max: ' . $this->length_max . ', join: ".joinMore_' . $this->getNameRequest() . '" }).init(); });</script>';
      }

      ###############################

    } else {
      $h .= '<!-- Error: ' . $this->type . ':' . $this->extra . ':' . $extra . ' -->';
    }
    if (!is_null($this->icon)) {
      $h .= '<div class="input-icon"><i data-feather="' . $this->icon . '"></i></div>';
    }
    $h .= '<p class="text-warning mt-2" data-fnn-message>' . implode(',', $this->_error) . '</p>';
    return $h;
  }
  function __toString()
  {
    return $this->render();
  }
  static function buildSelect($op, $selected = null)
  {
    $rp = '';
    if (empty($op) || !is_array($op)) {
      return $rp;
    }
    foreach ($op as $k => $o) {
      $s = !is_null($selected) && ((is_array($selected) && in_array($k, $selected)) || ($selected  == $k)) ? ' selected' : '';
      if (is_array($o)) {
        $rp .= '<optgroup label="' . $o['name'] . '">';
        $rp .= FormityField::buildSelect($o['children'], $selected);
        $rp .= '</optgroup>';
      } else {
        $rp .= '<option value="' . $k . '"' . $s . '>' . $o . '</option>';
      }
    }
    return $rp;
  }
  ##
}
