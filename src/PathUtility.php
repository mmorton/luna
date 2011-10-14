<?php

class LunaPathUtility
{
	static function pathCombine($p1, $p2)
	{
		//TODO: add checks for absolute paths
		
		if (!isset($p1) || strlen($p1) == 0)
			return $p2;
		if (!isset($p2) || strlen($p2) == 0)
			return $p1;
			
		if ($p1[strlen($p1) - 1] == '/' || $p1[strlen($p1) - 1] == '\\')
			$p1 = substr($p1, 0, strlen($p1) - 1);
			
		if ($p2[0] == '/' || $p2[0] == '\\')
			$p2 = substr($p2, 1);
			
		return $p1.'/'.$p2;
	}
}

?>