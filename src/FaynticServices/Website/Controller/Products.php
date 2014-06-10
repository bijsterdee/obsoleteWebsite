<?php

namespace FaynticServices\Website\Controller;

use FaynticServices\Website\Controller;

class Products extends Controller
{
    protected function handleDefault()
    {
        $this->setTemplate('Products.twig');
    }
}
