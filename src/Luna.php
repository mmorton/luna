<?php

class Luna 
{
	public static function initialize()
	{
		//set_error_handler(array('Luna', 'error'), E_ERROR | E_CORE_ERROR);
		
		spl_autoload_register(array('Luna', 'load'));
	}
	
	public static function load($type)
	{	
		$file = false;
		if (strpos($type, 'ILuna') === 0 || strpos($type, 'Luna') === 0)
			$file = dirname(__FILE__).'/'.$type.'.php';
					
		if ($file && file_exists($file)) include_once $file;
	}
	
	public static function error($number, $message, $file, $line, $context)
	{
		error_log("An error occured.");
		error_log(print_r(
			array(
				"number" => $number,
				"message" => $message,
				"file" => $file,
				"line" => $line,
				"context" => $context
			),
			true
		));	
	}
}

?>