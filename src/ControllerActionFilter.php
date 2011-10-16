<?php

class LunaControllerActionFilter
{
	public $target;	
	
	public $onlyForActions = array();
	public $exceptForActions = array();
	
	public function __construct($target)
	{
		$this->target = $target;
	}
	
	public function only($action)
	{
		if (is_array($action))
			$this->onlyForActions = array_merge($this->onlyForActions, $action);
		else
			$this->onlyForActions []= $action;
	}
	
	public function except($action)
	{
		if (is_array($action))
			$this->exceptForActions = array_merge($this->exceptForActions, $action);
		else
			$this->exceptForActions []= $action;
	}
	
	public function appliesTo($action)
	{
		if (count($this->onlyForActions) == 0 && count($this->exceptForActions) == 0)
			return true;
					
		foreach ($this->exceptForActions as $exceptAction)
			if (strcasecmp($action, $exceptAction) === 0)
				return false;
			
		foreach ($this->onlyForActions as $onlyAction)
			if (strcasecmp($action, $onlyAction) === 0)
				return true;			
				
		return false;
	}
}

?>