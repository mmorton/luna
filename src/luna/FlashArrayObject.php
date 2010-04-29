<?php

class LunaFlashArrayObject extends ArrayObject
{
	private static $sessionKey = "__flash";
	
	public $forward = array();
	
	public function read()
	{
		if (isset($_SESSION[self::$sessionKey]))
		{	
			foreach ($_SESSION[self::$sessionKey] as $key => $value)
			{
				parent::offsetset($key, $value);
			}
		}
	}
	
	public function write()
	{
		$_SESSION[self::$sessionKey] = $this->forward;
	}
			
	public function offsetset($index, $value)
	{
		if (isset($index) == false)
			$this->forward[$this->count()] = $value;
		else
			$this->forward[$index] = $value;
		
		parent::offsetset($index, $value);
	}
}

?>