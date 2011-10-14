<?php

class LunaTypeUtility
{
	static function resolveIncludePath($path) 
	{
		if (file_exists($path))
			return $path;
		
		$currentPath = dirname(__FILE__);
		if (file_exists($currentPath."/".$path))
			return $currentPath."/".$path;		
			
		$currentPath = dirname($_SERVER["SCRIPT_FILENAME"]);
		if (file_exists($currentPath."/".$path))
			return $currentPath."/".$path;	
			
		return false;
	}
	
	static function loadType($type, $throw = false)
	{
		if (is_array($type))
			$parts =& $type;
		else
			$parts = explode(",", $type);
		
		if (count($parts) <= 0)
			if ($throw)
				throw new Exception("Could not load requested type.");
			else
				return false;
		
		$className = trim($parts[0]);
		$fileName = count($parts) > 1 ? trim($parts[1]) : false;						
		
		if (class_exists($className) || interface_exists($className))
			return $className;
		
		if ($fileName !== false)
		{			
			$resolvedPath = LunaTypeUtility::resolveIncludePath($fileName);
			if ($resolvedPath === false)
				if ($throw)
					throw new Exception("Could not load requested type.");		
				else
					return false;
			
			include_once $resolvedPath;
			
			if (class_exists($className) == false && interface_exists($className) == false)
				if ($throw)
					throw new Exception("'$class_name' could not be found.");
				else
					return false;
			
			return $className;
		}
		else
		{
			if ($throw)
				throw new Exception("Could not load requested type.");		
			else
				return false;
		}
	}
	
	public static function includePath($path, $filter = false)
	{
		if (file_exists($path))
		{
			include_once $path;
			return;
		}
		
		$path = dirname($path);
		$dir = dir($path);
		while (false !== ($item = $dir->read()))
		{	
			if ($filter !== false && preg_match($filter, $item))
				include_once $item;
		}
		$dir->close();
	}
}

?>