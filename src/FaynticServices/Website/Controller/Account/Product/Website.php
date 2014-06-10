<?php

namespace FaynticServices\Website\Controller\Account\Product;

use FaynticServices\Website\Controller\Account\Product;
use FaynticServices\Website\Controller\Validator;
use FaynticServices\Website\Model\AccountWebQuery;

class Website extends Product
{
    /** @var int */
    protected $uid;

    /** @var int */
    protected $gid;

    /** @var string */
    protected $home;

    /** @var Validator */
    private $validator;

    protected function preDispatch()
    {
        $this->validator = new Validator();

        if ($systemUser = posix_getpwnam("customer-{$this->account->getId()}")) {
            $this->uid = $systemUser['uid'];
            $this->gid = $systemUser['gid'];
            $this->home = $systemUser['dir'];
        }
        parent::preDispatch();
    }

    protected function handleDefault()
    {
        $this->setTemplate('Account/Product/Website.twig', [
            'websites' => AccountWebQuery::create()->filterByAccount($this->account)->find()
        ]);
    }
}
