<?php

class Dumper {
	public static $logFile = '__app_log.json';
	
	public static function pre($content, $output = false) {
		$result = '<pre>' . $content . '</pre>';
		
		if ($output)
			echo $result;
		
		return $result;
	}
	
	public static function json($obj) {
		return json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	
	public static function vars(...$objects) {
		ob_start();
		var_dump(...$objects);
		return ob_get_clean();
	}
	
	public static function export($obj) {
		return var_export($obj, true);
	}
	
	public static function print($obj) {
		return print_r($obj, true);
	}
	
	public static function dumpJson(...$objects) {
		static::dumpInternal($objects, ['Dumper', 'json']);
	}
	
	public static function dumpVar(...$objects) {
		static::dumpInternal($objects, ['Dumper', 'vars']);
	}
	
	public static function dumpExport(...$objects) {
		static::dumpInternal($objects, ['Dumper', 'export']);
	}
	
	public static function dumpPrint(...$objects) {
		static::dumpInternal($objects, ['Dumper', 'print']);
	}
	
	public static function dumpSerialize(...$objects) {
		static::dumpInternal($objects, 'serialize');
	}
	
	public static function log(...$objects) {
		$header = static::getHeader();
		$content = "# $header" . PHP_EOL . implode(PHP_EOL . PHP_EOL, array_map(['Dumper', 'json'], $objects)) . PHP_EOL;
		$path = substr(static::$logFile, 0, 1) === DIRECTORY_SEPARATOR ? static::$logFile : $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . static::$logFile;
		file_put_contents($path, $content, FILE_APPEND | LOCK_EX);
	}
	
	private static function dumpInternal($objects, $serializer) {
		$header = static::getHeader();
		echo "<table class='dump'><tr><th>DUMP ($header)</th></tr>";
		
		foreach ($objects as $obj)
			echo '<tr><td><pre>' . $serializer($obj) . '</pre></td></tr>';
		
		echo '</table>';
	}
	
	private static function getHeader() {
		$bt = debug_backtrace()[1];
		$time = (new DateTime)->format('d.m.y H:i:s:u');
		$fileWithLine = substr($bt['file'], strlen($_SERVER['DOCUMENT_ROOT']) + 1) . ':' . $bt['line'];
		return "$time in $fileWithLine";
	}
}
