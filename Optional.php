<?php

// implementation of https://docs.oracle.com/javase/8/docs/api/java/util/Optional.html

class Optional {
	private $_value;

	private function __construct($value) {
		$this->_value = $value;
	}

	public static function empty() {
		return new self(null);
	}

	public static function of($value) {
		if (is_null($value))
			throw new InvalidArgumentException('value is null');

		return new self($value);
	}

	public static function ofNullable($value) {
		return new self($value);
	}

	public function equals($obj) {
		return $obj instanceof self && $this->_value === $obj->_value;
	}

	public function filter($callback) {
        $this->requireCallback($callback);
        
        if (!$this->isPresent())
            return $this;
        
        return $callback($this->_value) ? $this : static::empty();
	}

	public function flatMap($callback) {
        $this->requireCallback($callback);

        if (!$this->isPresent())
            return $this;

        $result = $callback($this->_value);

        if (!($result instanceof self))
            throw new InvalidArgumentException('callback returns not Optional: ' . var_export($result, true));
        
        return $result;
	}

	public function get() {
        $this->requirePresent();
		return $this->_value;
	}

	public function hashCode() {
		return crc32(serialize($this->_value));
	}

	public function ifPresent($callback) {
		$this->requireCallback($callback);

		if ($this->isPresent())
			$callback($this->_value);
	}

	public function isPresent() {
		return !is_null($this->_value);
	}

	public function map($callback) {
		$this->requireCallback($callback);
		return $this->isPresent() ? static::ofNullable($callback($this->_value)) : $this;
	}

	public function orElse($value) {
		return $this->isPresent() ? $this->_value : $value;
	}

	public function orElseGet($callback) {
		return $this->isPresent() ? $this->_value : $callback();
	}

	public function orElseThrow() {
        $this->requirePresent();
        return $this->_value;
	}

	public function __toString() {
		return (string)$this->_value;
	}

	private function requireCallback($callback) {
		if (!is_callable($callback))
			throw new InvalidArgumentException('callback is not callable: ' . var_export($callback, true));
    }
    
    private function requirePresent() {
        if (!$this->isPresent())
            throw new BadMethodCallException('value is not present');
    }
}
