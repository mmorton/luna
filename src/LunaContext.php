<?php

class LunaContext
{
    public $appRoot;
    /**
     * @var $view LunaViewContext
     */
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
     * @var $request LunaRequestContext
     */
    public $request;
    /**
     * @var $response LunaResponseContext
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