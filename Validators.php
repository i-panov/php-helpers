<?php

abstract class Validator {
	public static $buildInValidators = [
		'required' => RequiredValidator::class,
		'boolean' => BooleanValidator::class,
		'string' => StringValidator::class,
		'number' => NumberValidator::class,
		'integer' => IntegerValidator::class,
		'float' => FloatValidator::class,
		'match' => MatchValidator::class,
		'phone' => PhoneValidator::class,	
		'filter_var' => FilterVarValidator::class,
		'email' => EmailValidator::class,			
		'ip' => IPValidator::class,			
		'url' => UrlValidator::class,			
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
		
		$data = array_merge(array_flip(array_unique(array_column($parsedRules, 0))), $data);
		
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

class BooleanValidator extends Validator {
	public function test() {
		$trueValue = $this->params['trueValue'] ?? true;
		$falseValue = $this->params['falseValue'] ?? false;
		return $this->value == $trueValue || $this->value == $falseValue;
	}
}

class StringValidator extends Validator {
	public $messageTemplate = 'Поле ":key" имело не верную длину';
	
	public function test() {
		$min = $this->params['min'] ?? 0;
		$max = $this->params['max'] ?? $min;
		$length = $this->params['length'] ?? null;
		
		if (!is_string($this->value))
			return false;
		
		$valueLength = strlen($this->value);
		
		if (!is_null($length))
			return $valueLength == $length;
		
		return $valueLength >= $min && $valueLength <= $max;
	}
}

class NumberValidator extends Validator {
	public $messageTemplate = 'Поле ":key" имело неверное значение';
	protected $typeTester = 'is_numeric';
	
	public function test() {
		$min = $this->params['min'] ?? 0;
		$max = $this->params['max'] ?? $min;		
		return $this->typeTester($this->value) && $this->value >= $min && $this->value <= $max;
	}
}

class IntegerValidator extends NumberValidator {
	protected $typeTester = 'is_int';
}

class FloatValidator extends NumberValidator {
	protected $typeTester = 'is_float';
}

class MatchValidator extends Validator {
	protected $defaultPattern;
	
	public function test() {
		$pattern = $this->params['pattern'] ?? $this->defaultPattern ?? null;
		$not = $this->params['not'] ?? false;
		
		if (!$pattern)
			throw new InvalidArgumentException('pattern is empty');
		
		return preg_match($pattern, $this->value, $matches) > 0;
	}
}

class PhoneValidator extends MatchValidator {
	protected $defaultPattern = '/(\+7|8)[ ]?(\(\d{3,6}\)|\d{3,6})[ ]?\d{1,10}\-?\d{1,10}/';
}

class FilterVarValidator extends Validator {
	public function test() {
		$flags = $this->params['flags'] ?? FILTER_DEFAULT;
		return filter_var($this->value, $flags) !== false;
	}
}

class EmailValidator extends FilterVarValidator {
	public $params = ['flags' => FILTER_VALIDATE_EMAIL];
}

class IPValidator extends FilterVarValidator {
	public $params = ['flags' => FILTER_VALIDATE_IP];
}

class UrlValidator extends FilterVarValidator {
	public $params = ['flags' => FILTER_VALIDATE_URL];
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
