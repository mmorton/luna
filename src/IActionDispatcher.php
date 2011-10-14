<?php

interface ILunaActionDispatcher
{
	function canDispatch($context);
	function dispatch($context);
}

?>