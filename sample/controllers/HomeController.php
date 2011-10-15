<?php

class HomeController extends LunaController
{
    function index()
    {
    }

    /* parameters mapped in from route/query */
    function hello($id, $name)
    {
        $this->propertyBag['id'] = $id;
        $this->propertyBag['name'] = $name;
    }

    /* parameter mapped in from container */
    function sample(LunaRequestContext $requestContext)
    {
        echo $requestContext->path;
    }
}

?>