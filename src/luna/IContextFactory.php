<?php

interface ILunaContextFactory
{
	function create($engine);
    function release($context);
}

?>