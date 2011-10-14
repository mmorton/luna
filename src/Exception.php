<?php

class LunaException extends Exception
{	
	private $statusCode;
	
	public function __construct($message = "", $statusCode = 500, $code = 0, Exception $previous = null)
	{
		if ($previous)
			parent::__construct($message, $code, $previous);
		else
			parent::__construct($message, $code);
		
		$this->statusCode = $statusCode;
	}
	
	public function getStatusCode()
	{
		return $this->statusCode;
	}
}

?>