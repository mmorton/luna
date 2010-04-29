<?php

interface ILunaRouteMatch
{
	function getRoute();
	function getDispatcher();
	function getParameters();
}

?>