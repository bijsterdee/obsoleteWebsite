<?php

namespace FaynticServices\Website\Controller\Account\Product;

use FaynticServices\Website\Controller\Account\Product;

class Database extends Product
{
    public function handleDefault()
    {
        $this->setTemplate('Account/Product/Database.twig');
    }
}
