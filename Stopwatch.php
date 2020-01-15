<?php

class Stopwatch {
    private $_isRunning = false;
    private $_circles = [];

    public static function time() {
        return microtime(true);
    }

    public static function startNew() {
        $sw = new self();
        $sw->start();
        return $sw;
    }

    public function isRunning() {
        return $this->_isRunning;
    }

    public function start() {
        if ($this->_isRunning)
            return false;

        $this->_isRunning = true;
        $this->_circles = [static::time()];
        return true;
    }

    public function circle() {
        if (!$this->_isRunning)
            return false;

        $this->_circles[] = static::time();
        return true;
    }

    public function stop() {
        if (!$this->_isRunning)
            return false;

        $this->_isRunning = false;
        $this->_circles[] = static::time();
        return true;
    }

    public function reset() {
        $this->_isRunning = false;
        $this->_circles = [];
    }

    public function restart() {
        $this->reset();
        $this->start();
    }

    public function elapsedFloat(int $index = 0) {
        if ($index >= count($this->_circles))
            throw new \InvalidArgumentException('index >= count(circles)');

        if ($index < 1)
            return end($this->_circles) - reset($this->_circles);

        return $this->_circles[$index] - $this->_circles[$index - 1];
    }

    public function elapsed(int $index = 0) {
        return (int)round($this->elapsedFloat($index));
    }

    public function elapsedString(string $format, int $index = 0) {
        return strftime($format, $this->elapsed($index));
    }
}
