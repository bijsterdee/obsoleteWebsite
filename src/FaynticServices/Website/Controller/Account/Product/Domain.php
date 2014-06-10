<?php

namespace FaynticServices\Website\Controller\Account\Product;

use FaynticServices\Website\Configuration;
use FaynticServices\Website\Controller\Account\Product;
use FaynticServices\Website\Model\Base\AccountProductQuery;

class Domain extends Product
{
    protected function preDispatch()
    {
        /** @var Configuration $configuration */
        $configuration = $this->container->get('configuration');

        \Transip_ApiSettings::$mode = $configuration->vendor->transip->mode;
        \Transip_ApiSettings::$login = $configuration->vendor->transip->login;
        \Transip_ApiSettings::$privateKey = $configuration->vendor->transip->privatekey;
        parent::preDispatch();
    }

    protected function handleDefault()
    {
        $this->setTemplate('Account/Product/Domain.twig', [
            'transip' => new \Transip_DomainService(),
            'domains' => AccountProductQuery::create()->filterByAccount($this->account)->useProductQuery()->filterByCategory('domain')->endUse()->find()
        ]);
    }
}
