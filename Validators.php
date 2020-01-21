<?php

abstract class Validator {
	public static $buildInValidators = [
		'required' => RequiredValidator::class,
		'email' => EmailValidator::class,
		'phone' => PhoneValidator::class,
		'filter' => FilterValidator::class,
		'trim' => TrimValidator::class,
	];
	
	public $messageTemplate = 'Поле ":key" имело неверный формат';
	public $params = [];
	protected $key, $value;
	
	public function __construct($key, $value) {
		if (!$key)
			throw new InvalidArgumentException("key is empty");
		
		$this->key = $key;
		$this->value = $value;
	}
	
	public static function create($name, $key, $value) {
		$class = static::$buildInValidators[$name] ?? null;
		
		if (!$class)
			throw new InvalidArgumentException("unknown validator: $name");
		
		return new $class($key, $value);
	}
	
	public abstract function test();
	
	public function getMessage() {
		return strtr($this->messageTemplate, [':key' => $this->key, ':value' => $this->value]);
	}
	
	public static function validate(&$data, $rules) {
		$errors = [];
		$parsedRules = [];
		
		foreach ($rules as $rule) {
			if (count($rule) < 2)
				throw new InvalidArgumentException('rule is wrong');
			
			$ruleKeys = $rule[0];
			$ruleNameWithParams = array_slice($rule, 1);
			
			if (is_array($ruleKeys))
				foreach ($ruleKeys as $ruleKey)
					$parsedRules[] = array_merge([$ruleKey], $ruleNameWithParams);
			else
				$parsedRules[] = $rule;
		}
		
		foreach ($data as $key => &$value) {
			foreach ($parsedRules as $rule) {
				$ruleKey = $rule[0]; 
				$ruleName = $rule[1];
				
				if ($ruleKey != $key)
					continue;
				
				$validator = static::create($ruleName, $key, $value);
				$validator->params = array_merge($validator->params, count($rule) > 2 ? array_slice($rule, 2) : []);
				
				if (isset($validator->params['message'])) {
					$validator->messageTemplate = $validator->params['message'];
					unset($validator->params['message']);
				}
				
				if (!$validator->test())
					$errors[$key][] = $validator->getMessage();
				
				$value = $validator->value;
			}
		}
		
		return $errors;
	}
}

class RequiredValidator extends Validator {
	public $messageTemplate = 'Поле ":key" было не заполнено';
	
	public function test() {
		return !empty($this->value);
	}
}

class PhoneValidator extends Validator {
	public const PATTERN = '/(\+7|8)[ ]?(\(\d{3,6}\)|\d{3,6})[ ]?\d{1,10}\-?\d{1,10}/';
	
	public function test() {
		return preg_match(self::PATTERN, $this->value, $matches) > 0;
	}
}

class EmailValidator extends Validator {
	public function test() {
		return !filter_var($this->value, FILTER_VALIDATE_EMAIL);
	}
}

class FilterValidator extends Validator {
	public function test() {
		$mapper = $this->params['mapper'] ?? null;
		$this->value = is_callable($mapper) ? $mapper($this->value) : $this->value;
		return true;
	}
}

class TrimValidator extends FilterValidator {
	public $params = ['mapper' => 'trim'];
}
