<?php

class CArray {
	private $_data, $_count;
	
	public function filter($callback, $inverse = false) {
		$result = [];
		$index = 0;
		
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
	
	public function push(...$items) {
		$this->_count = array_push(...$this->_data);
		return $this->_count;
	}
	
	public function pushFront(...$items) {
		$this->_count = array_unshift(...$this->_data);
		return $this->_count;
	}
	
	public function pop() {
		$result = array_pop($this->_data);
		return ++$this->_count;
	}
	
	public function popFront() {
		$result = array_shift($this->_data);
		return ++$this->_count;
	}
}
