<?php

class Lambda {
    private $function, $args = [], $count = 0, $freeKeys = [];

    /**
     * @param callable $function
     * @param array $args
     * @throws \ReflectionException
     */
    private function __construct($function, $args) {
        $this->function = $function;
        $this->count = static::getNumberOfRequiredParameters($function);
        $this->freeKeys = range(0, max(count($args) - 1, 0));
        $this->bindArray($args);
    }

    /**
     * @param callable $function
     * @param array $args
     * @return self
     * @throws \ReflectionException
     */
    public static function of($function, $args = []) {
        return new self($function, $args);
    }

    /**
     * @param callable $callback
     * @return int
     * @throws \ReflectionException
     */
    public static function getNumberOfRequiredParameters($callback) {
        if (is_array($callback) && count($callback) > 1) {
            list($class, $method) = $callback;
            return (new \ReflectionMethod($class, $method))->getNumberOfRequiredParameters();
        } elseif (is_callable($callback)) {
            return (new \ReflectionFunction($callback))->getNumberOfRequiredParameters();
        }

        throw new \TypeError('callback is not a function');
    }

    /**
     * @param int $key
     * @param mixed $value
     * @return self
     */
    public function bind($key, $value) {
        if (!is_int($key))
            throw new \InvalidArgumentException('only integer keys');

        $this->freeKeys = array_diff($this->freeKeys, [$key]);
        $this->args[$key] = $value;
        return $this;
    }

    /**
     * @param array $args
     * @return self
     */
    public function bindArray($args) {
        foreach ($args as $key => $value) {
            if (!is_int($key))
                continue;

            $this->bind($key, $value);
        }

        return $this;
    }

    /**
     * @return self|mixed
     * @throws \ReflectionException
     */
    public function __invoke() {
        return static::of($this->function, $this->args)->mutableInvoke(func_get_args());
    }

    /**
     * @param array $args
     * @return self|mixed
     */
    private function mutableInvoke($args) {
        foreach ($args as $value) {
            $key = array_shift($this->freeKeys);

            if (!is_null($key))
                $this->args[$key] = $value;
            else
                $this->args[] = $value;
        }

        if (count($this->args) < $this->count)
            return $this;

        ksort($this->args);
        return call_user_func_array($this->function, $this->args);
    }
}
