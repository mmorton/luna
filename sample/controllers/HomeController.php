<?php

class HomeController extends LunaController
{
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