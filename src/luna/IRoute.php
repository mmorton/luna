<?php

interface ILunaRoute 
{
	function match($path);
	function reverse($parameters);
}

?>