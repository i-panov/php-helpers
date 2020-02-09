<?php

class CString implements Iterator, ArrayAccess, Countable {
	private $_value, $_length;
	
	public function __construct($value) {
		if (is_string($value))
			$this->_value = $value;
		elseif (is_int($value))
			$this->_value = chr($value);
		else
			throw new InvalidArgumentException('value has invalid type');
		
		$this->_length = strlen($this->_value);
	}
	
	public static function of($value) {
		return new static($value);
	}
	
	public function __toString() {
		return $this->_value;
	}

	//---------------------------------------------------

	private $_iteratorPosition = 0, $_iteratorCurrent = false;

	public function rewind() {
		$this->_iteratorPosition = 0;
		$this->_iteratorCurrent = false;
	}

	public function current() {
		return $this->_iteratorCurrent;
	}

	public function key() {
		return $this->_iteratorPosition;
	}

	public function next() {
		$this->_iteratorPosition++;
		$this->_iteratorCurrent = substr($this->_value, $this->_iteratorPosition, 1);
	}

	public function valid() {
		return $this->_iteratorPosition >= 0 && $this->_iteratorPosition < $this->_length;
	}

	//---------------------------------------------------

	public function offsetSet($offset, $value) {
		if (is_null($offset))
			$this->_value .= $value;
		else
			$this->_value = substr_replace($this->_value, $value, $offset, 0);
	}

	public function offsetExists($offset) {
		return $offset >= 0 && $offset < $this->_length;
	}

	public function offsetUnset($offset) {
		$this->_value = substr_replace($this->_value, '', $offset, 1);
	}

	public function offsetGet($offset) {
		return $this->offsetExists($offset) ? substr($this->_value, $offset, 1) : null;
	}

	//---------------------------------------------------
	
	public function count() {
		return $this->_length;
	}
	
	//---------------------------------------------------

	public function length() {
		return $this->_length;
	}

	public function isEmpty() {
		return $this->_value === '';
	}

	public function isWhiteSpace() {
		return trim($this->_value) === '';
	}

	public function startsWith($search) {
		return strpos($this->_value, $search) === 0;
	}

	public function endsWith($search) {
		$len = strlen($search);
		return strpos($this->_value, $search, -$len) === ($this->_length - $len);
	}
	
	public function indexOf($search, $offset = 0, $ignoreCase = false) {
		$searcher = $ignoreCase ? 'stripos' : 'strpos';
		return $searcher($this->_value, $search, $offset);
	}
	
	public function lastIndexOf($search, $offset = 0, $ignoreCase = false) {
		$searcher = $ignoreCase ? 'strripos' : 'strrpos';
		return $searcher($this->_value, $search, $offset);
	}
	
	public function code($offset = 0) {
		return ord(substr($this->_value, $offset, 1));
	}
	
	public function chunks($length = 1) {
		return array_map(['static', 'of'], str_split($this->_value, $length));
	}
	
	//---------------------------------------------------
	
	public function toLower() {
		return new self(strtolower($this->_value));
	}
	
	public function toUpper() {
		return new self(strtoupper($this->_value));
	}
	
	public function capitalize() {
		return new self(lcfirst($this->_value));
	}
	
	public function join($items) {
		return new self(implode($this->_value, $items));
	}
	
	public function split($delimiters, $limit = PHP_INT_MAX) {
		$delimitersLength = strlen($delimiters);
		
		if (is_string($delimiters))
			$result = explode($delimiters, $this->_value, $limit);
		elseif (is_array($delimiters) && !empty($delimiters))
			$result = explode($delimiters[0], str_replace($delimiters, $delimiters[0], $this->_value), $limit);
		else
			throw new InvalidArgumentException('delimiters');
		
		return new self($result);
	}
	
	public function slice($offset, $length = false) {
		$result = $length !== false ? substr($this->_value, $offset, $length) : substr($this->_value, $offset);
		return new self($result);
	}
	
	public function insert($str, $offset) {
		return new self(substr_replace($this->_value, $str, $offset, 0));
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
	
	public function replace($search, $replace, &$count = false, $ignoreCase = false) {
		$replacer = $ignoreCase ? 'str_ireplace' : 'str_replace';
		return new self($replacer($search, $replace, $this->_value, $count));
	}
	
	public function duplicate($count) {
		return new self(str_repeat($this->_value, $count));
	}
	
	public function shuffle() {
		return new self(str_shuffle($this->_value));
	}
	
	public function format(...$params) {
	    return new self(sprintf($this->_value, ...$params));
	}
	
	public function print() {
	    echo $this->_value;
	}
}

function str($value) {
	return new CString($value);
}
