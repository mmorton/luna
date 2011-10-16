<?php

class LunaRouteExpression
{
	private static $pathSegmentSeparator = "/";
	
	public $pathSegments = array();
	
	private static function escape($fragment) 
	{
		$special_chars = array('[' => true, ']' => true, '(' => true, ')' => true, '{' => true, '}' => true, '/' => true, '\\' => true);
		$escaped = array();
		
		for ($i = 0; $i < strlen($fragment); $i++)
		{
			if (array_key_exists($fragment[$i], $special_chars))
				$escaped[] = '\\'.$fragment[$i]; 
			else
				$escaped[] = $fragment[$i];						
		}		
		
		return implode("", $escaped);
	}
	
	public static function parse($expression)
	{
		$parsed = new LunaRouteExpression();
		
		foreach (explode(self::$pathSegmentSeparator, $expression) as $segment)
		{
			$fragments = preg_split('/\{(.+?)\}/', $segment, -1, PREG_SPLIT_DELIM_CAPTURE);
			$format = $fragments; /* copy fragments */
			$names = array();					
			
			for ($i = 1; $i < count($fragments); $i += 2)
			{
				$name = $fragments[$i];
				$next = false;
				if (($i + 1) < count($fragments))
				{
					if (strlen($fragments[$i + 1]) > 0)
						$next = $fragments[$i + 1][0];											
				}
								
				$group = null;
				if ($next !== false)
					$group = sprintf('(?<%s>[^%s]+)?', $name, self::escape($next));
				else
					$group = sprintf('(?<%s>.+)?', $name);
					
				$fragments[$i] = $group;
				$names []= $name; /* save index => name */
			}
			
			for ($i = 0; $i < count($fragments); $i += 2)	
				$fragments[$i] = self::escape($fragments[$i]);	
				
			for ($i = 1; $i < count($format); $i += 2)
				$format[$i] = "%s";	
				
			$pathSegment = new LunaRouteExpressionPathSegment();
			$pathSegment->format = implode("", $format);
			$pathSegment->regex = '/'.implode("", $fragments).'/i';			
			$pathSegment->names = $names;
			
			$parsed->pathSegments[] = $pathSegment;
		}
		
		return $parsed;
	}
	
	public function match($path, &$defaults)
	{
		if (strlen($path) > 0 && $path[0] == self::$pathSegmentSeparator)
			$path = substr($path, 1);
		
		$inSegments = explode(self::$pathSegmentSeparator, $path);
		$outParameters = array();						
		
		for ($i = 0; $i < count($inSegments); $i++)
		{			
			if ($i >= count($this->pathSegments))
				return false; /* TODO: support capture remaining */
		
			$inSegment = $inSegments[$i];		
			$testSegment = $this->pathSegments[$i];
			
			if (preg_match($testSegment->regex, $inSegment, $match))
			{
				foreach ($testSegment->names as $name)
					if (isset($match[$name]))
						$outParameters[$name] = $match[$name];
					elseif (is_array($defaults) && isset($defaults[$name]))
						$outParameters[$name] = $defaults[$name];
					else
						return false;
			}
			else
			{
				foreach ($testSegment->names as $name)
					if (is_array($defaults) && isset($defaults[$name]))
						$outParameters[$name] = $defaults[$name];
					else
						return false;
			}
		}
		
		/* ensure any remaining segments have default values */
		for ($i = $i; $i < count($this->pathSegments); $i++)
		{
			$testSegment = $this->pathSegments[$i];
			foreach ($testSegment->names as $name)
				if (is_array($defaults) && isset($defaults[$name]))
					$outParameters[$name] = $defaults[$name];
				else
					return false;
		}

		return $outParameters;
	}
	
	public function reverse(&$parameters, &$defaults)
	{
		if (is_array($parameters) === false)
			return false;
		
		/* two pass, no build, early out */		
		foreach ($this->pathSegments as $pathSegment)
			foreach ($pathSegment->names as $name)
				if (isset($parameters[$name]) === false && isset($defaults[$name]) === false)
					return false;
		
		$parametersMatched = array();
		$result = array();
		foreach ($this->pathSegments as $pathSegment)
		{
			$values = array();
			foreach ($pathSegment->names as $name)
			{
				if (isset($parameters[$name]))
				{
					$parametersMatched[$name] = true;
					
					$values[] = $parameters[$name];										
				}
				else
				{
					$parametersMatched[$name] = true;
					
					$values[] = $defaults[$name];
				}
			}
			
			$result[] = vsprintf($pathSegment->format, $values);
		}
				
		$queryParameters = array();
		foreach (array_keys($parameters) as $name)
		{
			if ($parametersMatched[$name])
				continue;
			
			$queryParameters[] = urlencode($name)."=".urlencode($parameters[$name]);
		}
		
		$url = implode(self::$pathSegmentSeparator, $result);
		if (count($queryParameters) > 0)
			$url = $url."?".implode("&", $queryParameters);
		
		return $url;
	}
}

?>