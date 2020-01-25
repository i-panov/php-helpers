<?php

class CArray implements Iterator, ArrayAccess, Countable {
	private $_data, $_count;
	
	public function __construct($data) {
		if (is_array($data))
			$this->_data = $data;
		elseif (is_iterable($data))
			$this->_data = iterator_to_array($data);
		elseif (is_object($data))
			$this->_data = get_object_vars($data);
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
	
	public function get($path) {
		$pathParts = $this->parsePath($path);
		$result = $this->_data;
		
		foreach ($pathParts as $key)
			$result = $result[$key];
			
		return $result;
	}
	
	public function set($path, $value) {
		return $this->setMany([$path => $value]);
	}
	
	public function setMany($map) {
		foreach ($map as $path => $value) {
			$pathParts = $this->parsePath($path);
			$valueToSet = &$this->_data;
			
			foreach ($pathParts as $key)
				if (!(isset($valueToSet[$key]) && array_key_exists($key, $valueToSet)))
					throw new InvalidArgumentException("unknown key \"$key\" in path \"$path\"");
				else
					$valueToSet = &$valueToSet[$key];
			
			$valueToSet = $value;
		}
		
		return $this;
	}

	//---------------------------------------------------

	public function count($callback = null, $inverse = false) {
		if (!$callback)
			return $this->_count;
		
		$result = 0; $index = 0;
		
		foreach ($this->_data as $key => $value)
			if ($this->inverseIfNeed($callback($value, $key, $index++), $inverse))
				$result++;
		
		return $result;
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
	
	public function firstKey() {
		if (function_exists('array_key_first'))		
			return array_key_first($this->_data);
		
		foreach ($this->_data as $key => $value)
			return $key;
		
		return null;
	}
	
	public function lastKey() {
		if (function_exists('array_key_last'))		
			return array_key_last($this->_data);
		
		if (empty($this->_data))
			return null;
		
		end($this->_data);
		$result = key($this->_data);
		reset($this->_data);
		return $result;
	}
	
	public function first() {
		foreach ($this->_data as $key => $value)
			return $key;
		
		return null;
	}
	
	public function last() {
		$result = end($this->_data);
		reset($this->_data);
		return $result;
	}
	
	public function search($callbackOrValue, $inverseOrStrict = false) {
		if (!is_callable($callbackOrValue))
			return array_search($callbackOrValue, $this->_data, $inverseOrStrict);
		
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			if ($this->inverseIfNeed($callbackOrValue($value, $key, $index++), $inverseOrStrict))
				return $key;
		
		return false;
	}
	
	public function searchAll($callbackOrValue, $inverseOrStrict = false) {
		if (!is_callable($callbackOrValue))
			return array_keys($this->_data, $callbackOrValue, $inverseOrStrict);
		
		$result = []; $index = 0;
		
		foreach ($this->_data as $key => $value)
			if ($this->inverseIfNeed($callbackOrValue($value, $key, $index++), $inverseOrStrict))
				$result[] = $key;
		
		return new self($result);
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
	
	public function each($callback, $recursive = false, $userData = null) {
        if ($recursive)
            array_walk_recursive($this->_data, $callback, $userData);
        else
            array_walk($this->_data, $callback, $userData);
    }
	
	//---------------------------------------------------
	
	public function min() {
		return min($this->_data);
	}
	
	public function max() {
		return max($this->_data);
	}
	
	public function sum($strict = false) {
		$data = $strict ? array_filter($this->_data, 'is_numeric') : $this->_data;
		return array_sum($data);
	}
	
	public function product($strict = false) {
		$data = $strict ? array_filter($this->_data, 'is_numeric') : $this->_data;
		return array_product($data);
	}
	
	public function average($strict = false) {
		$data = $strict ? array_filter($this->_data, 'is_numeric') : $this->_data;
		return array_sum($data) / count($data);
	}
	
	public function reduce($callback, $init = null) {
		$acc = $init;
		$index = 0;
		
		foreach ($this->_data as $key => $value)
			$acc = $callback($acc, $value, $key, $index++, $this->_data);
		
		return $acc;
	}
	
	//---------------------------------------------------
	
	public function keys() {
		return new self(array_keys($this->_data));
	}
	
	public function values() {
		return new self(array_values($this->_data));
	}
	
	public function flatten() {
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->_data));
        return new self($it);
    }
	
	public function reverse($preserveKeys = false) {
		return new self(array_reverse($this->_data, $preserveKeys));
	}
	
	public function flip() {
		return new self(array_flip($this->_data));
	}
	
	public function slice($offset = 0, $length = null, $preserveKeys = true) {
		return new self(array_slice($this->_data, $offset, $length, $preserveKeys));
	}
	
	public function merge(...$arrays) {
		return new self(array_merge($this->_data, ...$arrays));
	}
	
	public function mergeRecursive(...$arrays) {
		return new self(array_merge_recursive($this->_data, ...$arrays));
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
