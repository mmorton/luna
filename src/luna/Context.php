<?php

class LunaContext
{
    public $appRoot;
	public $view;
    /**
     * @var $flash LunaFlashArrayObject
     */
	public $flash;
	public $propertyBag;
	public $items;
    /**
     * @var $engine ILunaEngine
     */
	public $engine;
    /**
     * @var $container ILunaContainer
     */
	public $container;
    /**
     * @var $request ILunaRequestContext
     */
    public $request;
    /**
     * @var $response ILunaResponseContext
     */
	public $response;
    /**
     * @var $route ILunaRoute
     */
	public $route;
	
	function __construct()
	{
		
	}	
}

?>