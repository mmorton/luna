<?php

interface ILunaContainer
{
	function addComponent($name, $definition, $instance = null);
	function removeComponent($name);
	function getComponent($name, $localOnly = false, $optionalParameters = null);
	function getComponentFor($service, $localOnly = false, $optionalParameters = null);
	function getComponentNames();
	function hasComponent($name, $localOnly = false);
	function hasComponentFor($service, $localOnly = false);
}

?>