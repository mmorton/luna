<?php

interface ILunaRoutingEngine
{
	function load($routeDefinitions);	
	function find($urlInfo);
	function reverse($parameters);
}

?>