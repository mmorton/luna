<?php

interface ILunaDataBinder
{
	function bindObject(&$object, $values, $typeHints = array());
}

?>