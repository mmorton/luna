<?php
/**
 * @package Luna
 */

class LunaRequestContext
{
	public $rootPath;
	public $basePath;
	public $path;
	public $method;
	public $query = array();
	public $host;
	public $port;
	public $protocol;
	public $secure;
	public $raw;
	public $rewrite;
	
	private $data = array();
	
	function __get($name) 
	{
		if (isset($this->data[$name]))
			return $this->data[$name];
		else
			return "";
	}
	
	function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	function __isset($name)
	{		
		return isset($this->data[$name]);
	}
	
	function __unset($name)
	{
		unset($this->data[$name]);
	}
	
	function &getCustomProperties()
	{
		return $this->data; 
	}
	
	function toArray()
	{
		return array(
			"path" => $this->path,
			"method" => $this->method,
			"query" => $this->query,
			"host" => $this->host,
			"port" => $this->port,
			"protocol" => $this->protocol,
			"secure" => $this->secure,
			"raw" => $this->raw,
			"parameters" => $this->data						
		);
	}
}

?>