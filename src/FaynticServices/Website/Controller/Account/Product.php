<?php

namespace FaynticServices\Website\Controller\Account;

use FaynticServices\Website\Controller\Account as AccountController;
use FaynticServices\Website\Controller\Auth;
use FaynticServices\Website\Model\AccountProduct;
use FaynticServices\Website\Model\Base\AccountProductQuery;

class Product extends Auth
{
    protected function handleDefault()
    {
        $accountProductQuery = AccountProductQuery::create();
        $accountProductQuery->filterByAccount($this->account);
        $accountProductQuery->joinWith('Product');
        $accountProductQuery->useProductQuery()->joinWithI18n($this->locale)->endUse();

        /** @var AccountProduct[] $products */
        $products = $accountProductQuery->find();
        foreach ($products as $product) {
            /** @var \DateTime $createdAt */
            $createdAt = $product->getCreatedAt();
            $dateInterval = $createdAt->diff(new \DateTime());
            $createdAt->modify($dateInterval->format('-1 day +%m months'));
            $createdAt->modify("+{$product->getProduct()->getPeriod()} {$product->getProduct()->getPeriodUnit()}");
            $product->setVirtualColumn('terminationDate', $createdAt);
        }

        $this->setTemplate('Account/Product.twig', [
            'products' => $products
        ]);
    }
}
