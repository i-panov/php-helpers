<?php

class Html {
	//--------------------------------------
	
	public static $output = true;
	
	public static function enableOutput() {
		static::$output = true;
	}
	
	public static function disableOutput() {
		static::$output = false;
	}
	
	public static function toggleOutput() {
		static::$output = !static::$output;
	}
	
	//--------------------------------------
	
	public static function beginBuffering() {
		return ob_start();
	}
	
	public static function endBuffering() {
		return ob_get_clean();
	}
	
	//--------------------------------------
	
	public const BOOLEAN_ATTRIBUTES = ['async', 'autofocus', 'autoplay', 'checked', 'compact', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 
		'formNoValidate', 'frameborder', 'hidden', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nohref', 'nomodule', 'noresize', 'noshade', 
		'novalidate', 'nowrap', 'open', 'readonly', 'required', 'reversed', 'scoped', 'scrolling', 'seamless', 'selected', 'sortable', 'typemustmatch',
	];
	
	public static function isBooleanAttribute($name) {
		return in_array($name, static::BOOLEAN_ATTRIBUTES);
	}
	
	//--------------------------------------
	
	public const NOT_CONTENTABLE_TAGS = ['area', 'base', 'basefont', 'bgsound', 'br', 'col', 'command', 'embed', 
		'hr', 'img', 'input', 'isindex', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
	];
	
	public static function isContentableTag($name) {
		return !in_array($name, static::NOT_CONTENTABLE_TAGS);
	}
	
	//--------------------------------------
	
	public static function booleanString($value) {
		return $value ? 'true' : 'false';
	}
	
	public static function attribute($name, $value, $singleQuotes = false) {
		if (static::isBooleanAttribute($name))
			return $value ? $name : '';
		
		$quote = $singleQuotes ? "'" : '"';
		return $name . '=' . $quote . $value . $quote;
	}

	public static function renderTagOptions($list) {
		if (empty($list))
			return '';
		
		if (isset($list['__singleQuotes'])) {
			if ($list['__singleQuotes'])
				$singleQuotes = true;
			
			unset($list['__singleQuotes']);
		} else
			$singleQuotes = false;
		
		$tagList = [];
		
		foreach ($list as $key => $value)
			$tagList[] = static::attribute($key, $value, $singleQuotes);

		return implode(' ', $tagList);
	}
	
	//--------------------------------------

	public static function beginTag($name, $options = []) {
		$htmlTagOptions = static::renderTagOptions($options);
		$result = '<' . $name . ($htmlTagOptions ? (' ' . $htmlTagOptions) : '') . '>';
		
		if (static::$output)
			echo $result;
		
		return $result;
	}

	public static function endTag($name) {
		$result = static::isContentableTag($name) ? "</${name}>" : '';
		
		if (static::$output)
			echo $result;
		
		return $result;
	}

	public static function tag($name, $content = '', $options = []) {
		$result = static::beginTag($name, $options);
		
		if (static::isContentableTag($name)) {
			if (static::$output)
				echo $content;
			
			$result .= $content . static::endTag($name);
		}
		
		return $result;
	}
	
	//--------------------------------------
}
