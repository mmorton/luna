<?php

interface ILunaRoutingEngine
{
	function load($routeDefinitions);
    function add($route);
	function find($request);
	function reverse($parameters);
}

?>