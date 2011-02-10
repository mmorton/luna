<?php

class HomeController extends LunaController
{
    public function __construct($context)
	{
		parent::__construct($context);
	}

    function index()
    {
    }

    function hello($id, $name)
    {
        $this->propertyBag['id'] = $id;
        $this->propertyBag['name'] = $name;
    }
}

?>