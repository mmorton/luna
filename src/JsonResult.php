<?php

class LunaJsonResult implements ILunaActionResult
{
	public $data;
	
	public function __construct($data)
	{
		$this->data =& $data;
	}
	
	public function execute(LunaContext $context) 
	{
		$context->response->contentType = "application/json";
		$context->response->content []= json_encode($this->data);		
	}
}

?>