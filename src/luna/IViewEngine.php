<?php

interface ILunaViewEngine
{	
	function hasTemplate($context, $name);
	function renderTemplate($context, $templateName, $layoutName = false);
}

?>