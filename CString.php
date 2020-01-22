<?php

class CString {
	private $_value, $_length;
	
	public function __construct($value) {
		$this->_value = $value;
		$this->_length = strlen($this->_value);
	}
	
	public static function of($value) {
		return new static($value);
	}
	
	public function __toString() {
		return $this->_value;
	}
	
	public function length() {
		return $this->_length;
	}
	
	public function chunks($length = 1) {
		return str_split($this->_value, $length);
	}
	
	public function indexOf($search, $offset = 0) {
		return strpos($this->_value, $search, $offset);
	}
	
	public function startsWith($search) {
		return substr($this->_value, strlen($search)) == $search;
	}
	
	public function endsWith($search) {
		return substr($this->_value, $this->_length - strlen($search)) == $search;
	}
	
	public function lower() {
		return new self(strtolower($this->_value));
	}
	
	public function upper() {
		return new self(strtoupper($this->_value));
	}	
	
	public function join($items) {
		return new self(implode($this->_value, $items));
	}
	
	public function split($delimiters, $limit = PHP_INT_MAX) {
		$delimitersLength = strlen($delimiters);
		
		if ($delimitersLength === 1)
			$result = explode($delimiters, $this->_value, $limit);
		elseif ($delimitersLength > 1) {
			$delimiters = is_array($delimiters) ? $delimiters : str_split($delimiters);
			$result = str_replace($delimiters, $delimiters[0], $this->_value);
			$result = explode($delimiters[0], $result, $limit);
		} else
			throw new InvalidArgumentException('delimiters is empty'):
		
		return new self($result);
	}
	
	public function slice($start, $length = false) {
		$result = $length !== false ? substr($this->_value, $start, $length) : substr($this->_value, $start);
		return new self($result);
	}
	
	public function trim($chars = " \t\n\r\0\x0B") {
		return new self(trim($this->_value, $chars));
	}
	
	public function trimStart($chars = " \t\n\r\0\x0B") {
		return new self(ltrim($this->_value, $chars));
	}
	
	public function trimEnd($chars = " \t\n\r\0\x0B") {
		return new self(rtrim($this->_value, $chars));
	}
	
	public function replace($search, $replace, &$count = false) {
		return new self(str_replace($search, $replace, $this->_value, $count));
	}
}

function str($value) {
	return new CString($value);
}
