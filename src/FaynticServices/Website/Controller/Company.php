<?php

namespace FaynticServices\Website\Controller;

use FaynticServices\Website\Controller;

class Company extends Controller
{
    protected function handleDefault()
    {
        $this->setTemplate('Company.twig');
    }
}
