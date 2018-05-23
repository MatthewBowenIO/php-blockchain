<?php

class Route
{
	
	private $_method;
	private $_pattern;
	private $_optionalFunctions;
	private $_function;
	private $_param;

	function __construct($args) {
		$this->_method = array_shift($args);
		$this->_pattern = array_shift($args);
		$this->_function = array_pop($args);
		$this->_optionalFunctions = $args;
		$this->_param = array();
	}

	public function methodMatches($method) {
		if ( $this->_method == $method )
			return true;
		else
			return false;
	}

	public function patternMatches($URI) {
	    preg_match_all('@:([\w]+)@', $this->_pattern, $paramNames, PREG_PATTERN_ORDER);
	    $paramNames = $paramNames[0];

	    $patternAsRegex = preg_replace_callback('@:[\w]+@', array($this, '_convertPatternToRegex'), $this->_pattern);
	    if ( substr($this->_pattern, -1) === '/' ) {
	        $patternAsRegex = $patternAsRegex . '?';
	    }
	    $patternAsRegex = '@^' . $patternAsRegex . '$@';

	    if ( preg_match($patternAsRegex, $URI, $paramValues) ) {
	        array_shift($paramValues);
	        foreach ( $paramNames as $index => $value ) {
	            $val = substr($value, 1);
	            if ( isset($paramValues[$val]) ) {
	                $this->_param[$val] = urldecode($paramValues[$val]);
	            }
	        }
	        return true;
	    }
	    return false;
	}

	private function _convertPatternToRegex( $matches ) {
	    $key = str_replace(':', '', $matches[0]);
		return '(?P<' . $key . '>[a-zA-Z0-9_\-\.\!\~\*\\\'\(\)\:\@\&\=\$\+,%]+)';
	}

	public function run() {
		foreach ($this->_optionalFunctions as $function) {
			if (is_callable($function))
				call_user_func($function);
		}

		if (is_callable($this->_function)) {
		    call_user_func_array($this->_function, array_values($this->_param));
		    return true;
		}
		return false;
	}
}