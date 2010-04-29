<?php

class LunaTemplate 
{	
	private static $compilations = array();
	private static $source = array();
	private static $lookup = array();
	public static $debug = false;
	
	private $function = false;
	
	protected function __construct($function) 
	{
		$this->function = $function;
	}
	
	public function render($variables)
	{
		if (function_exists($this->function) === false)
			return "";
		
		$result = "";
		
		$previousErrorLevel = error_reporting(0);
		
		try
		{
			ob_start();
			call_user_func($this->function, $variables);
			$result = ob_get_contents();
			ob_end_clean();
		} 
		catch (Exception $e) 
		{
			error_log($exception->getMessage());
		}
		
		error_reporting($previousErrorLevel);
		
		return $result;
	}
	
	public static function load($path)
	{
	}
	
	public static function parse($markup)
	{		
		$name = "__compiledTemplate_".count(self::$compilations);
		$source = array();
		$fragments = preg_split('/<%(.*?)%>/u', $markup, -1, PREG_SPLIT_DELIM_CAPTURE);		
				
		/* first pass - expression fragments */
		for ($i = 1; $i < count($fragments); $i += 2)
		{
			$control = false;			
			if (strlen($fragments[$i]) > 0) /* ok for utf-8 as we only care that it is > 0 */
			{
				/*  ok for utf-8 as any template markup chars should be in the first byte */
				$control = $fragments[$i][0];
				
				/* replace the control char, if php ever disallows string modification, use substr */				
				$fragments[$i][0] = " "; 
				/* $fragments[$i] = substr($fragments[$i], 1); */
			}
			
			switch ($control)
			{
				case "#":
					$fragments[$i] = "/* ".$fragments[$i]." */";
					break;
				case "=":
					$fragments[$i] = "echo ".$fragments[$i].";";
					break;
				default:								
					/* no action */
					break;
			}
		}		
		
		/* second pass - literal fragments */
		for ($i = 0; $i < count($fragments); $i += 2)
		{			
			$fragments[$i] = str_replace("\\", "\\\\", $fragments[$i]);
			$fragments[$i] = str_replace("'", "\\'", $fragments[$i]);
			$fragments[$i] = "echo '".$fragments[$i]."';";
		}
					
		$source[] = self::createStartFragment($name);
		$source[] = self::createExpansionFragment();
		$source[] = implode("", $fragments);
		$source[] = self::createEndFragment();
		$complete = implode("", $source);
		
		if (self::$debug)
			self::$source[$name] = $complete;
		
		$previousErrorLevel = error_reporting(0);
		try
		{
			$result = eval($complete);				
		} 
		catch (Exception $e) {	}
		error_reporting($previousErrorLevel);
		
		if ($result === false)
			throw new Exception("Unable to parse template.");
		
		self::$compilations[] = $name;
		
		return new LunaTemplate($name);
	}
	
	public static function getCompilationSources()
	{
		return self::$source;
	}
	
	public static function createStartFragment($name)
	{		
		return "function ${name}(\$__variables) {";
	}
	
	public static function createEndFragment()
	{
		return "}";
	}
	
	public static function createExpansionFragment()
	{
		return "extract(\$__variables);";
	}
}

?>