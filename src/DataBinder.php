<?php

class LunaDataBinder implements ILunaDataBinder
{
	/**
	 * A collection of cached paths.  These are not type specific and only contain parsed path info.
	 */
	protected $pathCache = array();
	protected $separator;
	
	public function __construct($separator = ".")
	{
		$this->separator = $separator;
	}
	
	public function bindObject(&$object, $values, $typeHints = array()) 
	{	
		if (is_array($object) == false && is_object($object) == false)
			$object = array();
	
		foreach ($values as $name => $values)
			$this->setObjectValue($object, $name, $values, $typeHints);		
	}
	
	protected function setObjectValue(&$object, $name, $value, $typeHints)
	{
		$path = $this->nameToPath($name);
				
		$currentName = "";
		$current =& $object;		
		for ($i = 0; $i < (count($path) - 1);)
		{
			$currentName .= ($i > 0 ? ".".$path[$i] : $path[$i]);
			$key = $path[$i++];			
						
			if ($i < count($path))
			{
				if (is_object($current))
				{
					if (property_exists($current, $key) == false)
						return;
					
					if (isset($current->$key) == false)
					{											
						if (isset($typeHints[$currentName]))
						{
							$currentClass = $typeHints[$currentName];
							$current->$key = new $currentClass();
						}
						else
						{
							$current->$key = array();
						}
					}	
					
					$current =& $current->$key;					
				}
				elseif (is_array($current))
				{
					if (isset($current[$key]) == false)
					{						
						if (isset($typeHints[$currentName]))
						{
							$currentClass = $typeHints[$currentName];
							$current[$key] = new $currentClass();
						}
						else
						{
							$current[$key] = array();
						}
					}
					
					$current =& $current[$key];
				}		
			}
		}
			
		$key = $path[count($path) - 1];
		if (is_object($current) && property_exists($current, $key))
			$current->$key = $value;
		elseif (is_array($current))
			$current[$key] = $value;			
	}
		
	protected function nameToPath($name)
	{
		if (isset($this->pathCache[$name]) == false)
		{
			$parts = preg_split('/\\'.$this->separator.'/', $name);
			$path = array();
			
			foreach ($parts as $part)
			{
				if (preg_match('/([a-zA-Z0-9_]+)\[([^\]]+)\]/', $part, $match))
				{
					$path []= $part;
					if (is_numeric($match[2]))
						$path []= intval($match[2]);
					else
						$path []= $match[2];
				}
				else
				{
					$path []= $part;
				}
			}
			
			$this->pathCache[$name] = $path;
		}
		
		return $this->pathCache[$name];
	}
}

?>