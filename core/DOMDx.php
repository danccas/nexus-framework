<?php
namespace Core;

use Core\Concerns\Collection;

class DOMDx {
    protected $elem;
    protected $root;
    protected $data;
    protected $mfooter;
    protected $mheader;

    function __construct()
    {
        $this->elem = new \DOMDocument();
    }
		public function attr($key, $value, $element = null) {
			if($element !== null) {
				$box = $this->root->getElementsByTagName($element);
				$box = $box[0];
			} else {
				$box = $this->root;
			}
        $box->setAttribute($key, $value);
        return $this;
    }
    public function cache($key) {
        $cache = cache($key)->dump();
        $this->set(json_decode(json_encode($cache->data)));
        $this->mfooter = date('d/m/Y h:i:s A', $cache->time);
        return $this;
    }
		public function map($callback) {
			$this->data = collect($this->data)->map($callback);
      return $this;
    }
		public function set($data) {
			if($data instanceof Collection) {
				$this->data = $data;
        $this->mfooter = date('d/m/Y h:i:s A', $data->execute->unix);
      } else {
				$this->data = $data;
			}
      return $this;
    }
		public function table($datax = -1) {
			if($datax instanceof Collection) {
				$this->set($datax);
				$this->mfooter = date('d/m/Y h:i:s A', $datax->execute->unix);
				$datax = -1;
      }
			if($datax == -1) {
            $datax = $this->data;
			}
				$this->buildheader();
        $div = $this->createElementRoot('div');
        $table = $this->createElement('table');
        $table->setAttribute('style', 'width: 100%;');
        $div->appendChild($table);
        if(empty($datax)) {
            return $this;
        }
        $thead = $this->createElement('thead');
        foreach($datax as $_tr) {
            $tr = $this->createElement('tr');
            foreach($_tr as $key => $val)  {
            $td = $this->createElement('th', strtoupper($key));
            $tr->appendChild($td);
            }
            $thead->appendChild($tr);
            break;
        }
        $table->appendChild($thead);
        $tbody = $this->createElement('tbody');
        foreach($datax as $_tr) {
            $tr = $this->createElement('tr');
            foreach($_tr as $key => $val)  {
            $td = $this->createElement('td', $val);
            $tr->appendChild($td);
            }
            $tbody->appendChild($tr);
        }
        $table->appendChild($tbody);
        $this->buildfooter();
        return $this;
    }
    public function footer($message) {
        $this->mfooter = $message;
        if(!empty($this->root)) {
            $this->buildfooter();
        }
        return $this;
    }
    public function header($message) {
        $this->mheader = $message;
        if(!empty($this->root)) {
            $this->buildheader();
        }
        return $this;
    }
    public function buildheader() {
        if(!empty($this->mheader)) {
            $foot = $this->elem->createElement('div', $this->mheader);
            $foot->setAttribute('style', 'text-align:center;font-weight: bold;');
            $foot->setAttribute('class', 'is_header');
            $this->root->insertBefore($foot, $this->root->firstChild);
        }
        return $this;
    }
    public function buildfooter() {
        if(!empty($this->mfooter)) {
            $foot = $this->elem->createElement('div', $this->mfooter);
            $foot->setAttribute('style', 'text-align:right;font-size:10px;');
            $foot->setAttribute('class', 'is_footer');
            $this->root->appendChild($foot);
        }
        return $this;
    }
    public function createElementRoot($a, $b = '') {
        $this->root = $this->elem->createElement($a, $b);
        return $this->root;
    }
    public function createElement($a, $b = '') {
        return $this->elem->createElement($a, $b);
    }
    public function appendChild($a) {
        return $this->elem->appendChild($a);
    }
    public function render() {
        return $this->elem->saveHTML($this->root);
    }
    public function __toString()
    {
        return $this->render();
    }
}
