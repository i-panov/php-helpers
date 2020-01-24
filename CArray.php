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
	
	public function all($callback, $inverse = false) {
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			if (!$this->inverseIfNeed($callback($value, $key, $index++), $inverse))
				return false;
		
		return true;
	}
	
	public function any($callback, $inverse = false) {
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			if ($this->inverseIfNeed($callback($value, $key, $index++), $inverse))
				return true;
		
		return false;
	}
	
	//---------------------------------------------------
	
	public function sum() {
		return array_sum($this->_data);
	}
	
	public function product() {
		return array_product($this->_data);
	}
	
	public function reduce($callback, $init = null) {
		$acc = $init;
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			$acc = $callback($acc, $value, $key, $index++, $this->_data);
		
		return $acc;
	}
	
	public function get($path) {
		$pathParts = $this->parsePath($path);
		$result = $this->_data;
		
		foreach ($pathParts as $key)
			$result = $result[$key];
			
		return $result;
	}
	
	public function set($path, $value) {
		$pathParts = $this->parsePath($path);
		$valueToSet = &$this->_data;
		
		foreach ($pathParts as $key)
			if (!(isset($valueToSet[$key]) && array_key_exists($key, $valueToSet)))
				throw new InvalidArgumentException("unknown key \"$key\" in path \"$path\"");
			else
				$valueToSet = &$valueToSet[$key];
		
		$valueToSet = $value;
		return $this;
	}
	
	//---------------------------------------------------
	
	public function slice($offset = 0, $length = null, $preserveKeys = true) {
		return new self(array_slice($this->_data, $offset, $length, $preserveKeys));
	}
	
	public function filter($callback = null, $inverse = false) {
		$result = [];
		$index = 0;
		
		if (!$callback)
			$callback = function($value) { return !empty($value); };
		
		foreach ($this->_data as $key => $value)
			if ($this->inverseIfNeed($callback($value, $key, $index++), $inverse))
				$result[] = $value;
		
		return new self($result);
	}
	
	public function where($conditions) {
		$result = [];
		
		foreach ($conditions as $key => $value) {
			list($value, $strict) = is_array($value) ? $value : [$value, false];
			
			if ($strict) {
				if ($this->_data[$key] === $value)
					$result[$key] = $this->_data[$key];
			} else {
				if ($this->_data[$key] == $value)
					$result[$key] = $this->_data[$key];
			}
		}
		
		return new self($result);
	}
	
	public function map($callback) {
		$result = [];
		$index = 0;
		
		foreach ($this->_data as $key => $value) {
			$callbackResult = $callback($value, $key, $index++, $newKey); // $newKey by ref &
			
			if (empty($newKey))
				$result[] = $callbackResult;
			else
				$result[$newKey] = $callbackResult;
		}
		
		return new self($result);
	}
	
	//---------------------------------------------------
	
	public function removeAll($callback, $inverse = false) {
		$removedKeys = [];
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			if ($this->inverseIfNeed($callback($value, $key, $index++), $inverse))
				$removedKeys[] = $key;
		
		foreach ($removedKeys as $key) {
			unset($this->_data[$key]);
			$this->_count--;
		}			
		
		return $this;
	}
	
	public function remove($value, $strict) {
		if (($key = array_search($value, $this->_data, $strict)) !== false) {
			unset($this->_data[$key]);
			$this->_count--;
			return true;
		}
		
		return false;
	}
	
	public function removeKey($key) {
		if (!$this->containsKey($key))
			return false;
		
		unset($this->_data[$key]);
		$this->_count--;
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
	
	private function inverseIfNeed($value, $needInverse) {
		return $needInverse ? !$value : $value;
	}
	
	private function parsePath($path) {
		$result = is_string($path) ? explode('.', $path) : [];
		
		if (empty($result))
			throw new InvalidArgumentException('path');
		
		return $result;
	}
}

function arr($data) {
	return new CArray($data);
}
