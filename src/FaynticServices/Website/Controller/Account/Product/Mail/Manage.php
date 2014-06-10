<?php

namespace FaynticServices\Website\Controller\Account\Product\Mail;

use FaynticServices\Website\Controller\Account\Product\Mail;
use FaynticServices\Website\Model\Base\AccountEmailQuery;

class Manage extends Mail
{
    protected function handleDefault($emailAccountId)
    {
        $this->setTemplate('Account/Product/Email/Manage.twig', [
            'email' => AccountEmailQuery::create()->findOneById($emailAccountId)
        ]);
    }
}
