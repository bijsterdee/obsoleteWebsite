<?php

namespace FaynticServices\Website\Controller\Account\Product;

use FaynticServices\Website\Controller\Account\Product;

class Mail extends Product
{
    protected function handleDefault()
    {
        $this->setTemplate('Account/Product/mail.twig');
    }
}
