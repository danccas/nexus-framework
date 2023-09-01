<?php
namespace Core\Database;

class Raw {
	public $value;
	function __construct($value) {
		$this->value = $value;
	}
	function __toString() {
		return $this->value;
	}
}


