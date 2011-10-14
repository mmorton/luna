<?php

interface ILunaRouteMatch
{
	function getRoute();
	function getDispatcherType();
	function getParameters();
}

?>