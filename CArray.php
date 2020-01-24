<?php

class CArray implements Iterator, ArrayAccess {
	private $_data, $_count;
	
	public function __construct($data) {
		if (is_array($data))
			$this->_data = $data;
		elseif (is_iterable($data))
			$this->_data = iterator_to_array($data);
		else
			throw new InvalidArgumentException('data has invalid type');
		
		$this->_count = count($this->_data);
	}
	
	public static function of($data) {
		return new static($data);
	}
	
	public function toArray() {
		return $this->_data;
	}
	
	//---------------------------------------------------

	private $_iteratorPosition = 0;

	public function rewind() {
		$this->_iteratorPosition = 0;
	}

	public function current() {
		return $this->_data[$this->_iteratorPosition] ?? false;
	}

	public function key() {
		return $this->_iteratorPosition;
	}

	public function next() {
		$this->_iteratorPosition++;
	}

	public function valid() {
		return $this->_iteratorPosition >= 0 && $this->_iteratorPosition < $this->_count;
	}

	//---------------------------------------------------

	public function offsetSet($offset, $value) {
		if (is_null($offset))
			$this->_data[] = $value;
		else
			$this->_data[$offset] = $value;
	}

	public function offsetExists($offset) {
		return $offset >= 0 && $offset < $this->_count;
	}

	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}

	public function offsetGet($offset) {
		return $this->offsetExists($offset) ? $this->_data[$offset] : null;
	}

	//---------------------------------------------------

	public function count() {
		return $this->_count;
	}

	public function isEmpty() {
		return empty($this->_data);
	}
	
	public function contains($value, $strict = false) {
		return in_array($value, $this->_data, $strict);
	}
	
	public function containsKey($key) {
		return isset($this->_data[$key]) || array_key_exists($key, $this->_data);
	}
	
	public function sum() {
		return array_sum($this->_data);
	}
	
	public function product() {
		return array_product($this->_data);
	}
	
	//---------------------------------------------------
	
	public function removeAll($callback, $inverse = false) {
		$removedKeys = [];
		$index = 0;
		
		foreach ($this->_data as $key => $value) {
			$callbackResult = $callback($value, $key, $index++);
			
			if ($inverse)
				$callbackResult = !$callbackResult;
			
			if ($callbackResult)
				$removedKeys[] = $key;
		}
		
		return new self(array_diff($this->_data, array_flip($removedKeys)));
	}
	
	public function remove($value, $strict) {
		if (($key = array_search($value, $this->_data, $strict)) !== false) {
			unset($this->_data[$key]);
			return true;
		}
		
		return false;
	}
	
	public function removeKey($key) {
		if (!$this->containsKey($key))
			return false;
		
		unset($this->_data[$key]);
		return true;
	}
	
	public function push($items) {
		return $this->_count = array_push($this->_data);
	}
	
	public function pushFront($items) {
		return $this->_count = array_unshift($this->_data);
	}
	
	public function pop() {
		if ($this->_count <= 0)
			throw new UnderflowException('array is empty');
		
		$this->_count--;
		return array_pop($this->_data);
	}
	
	public function popFront() {
		if ($this->_count <= 0)
			throw new UnderflowException('array is empty');
		
		$this->_count--;
		return array_shift($this->_data);
	}
	
	//---------------------------------------------------
	
	public function filter($callback = null, $inverse = false) {
		$result = [];
		$index = 0;
		
		if (!$callback)
			$callback = function($value) { return !empty($value); };
		
		foreach ($this->_data as $key => $value) {
			$callbackResult = $callback($value, $key, $index++);
			
			if ($inverse)
				$callbackResult = !$callbackResult;
			
			if ($callbackResult)
				$result[] = $value;
		}
		
		return new self($result);
	}
	
	public function map($callback) {
		$result = [];
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			$result[] = $callback($value, $key, $index++);
			
		return new self($result);
	}
	
	public function reduce($callback, $init = null) {
		$acc = $init;
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			$acc = $callback($acc, $value, $key, $index++, $this->_data);
		
		return $acc;
	}
}

function arr($data) {
	return new CArray($data);
}
