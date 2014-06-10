<?php

namespace FaynticServices\Website\Controller\Account\Product\Domain;

use FaynticServices\Website\Controller\Account\Product\Domain;
use FaynticServices\Website\Model\AccountProductQuery;

class Lock extends Domain
{
    protected function handleDisable()
    {
        list(, $domainId) = func_get_args();
        $domain = AccountProductQuery::create()->findOneById($domainId);
        \Transip_DomainService::unsetLock($domain->getName());
        $this->setStatus('Uw domein is niet langer beveiligd en kan hierdoor (ongewenst) verhuisd worden', self::STATUS_WARNING);
        unset($_SESSION['domainInfo'][$domainId]);
        $this->redirect("/account/product/domain/manage/{$domain->getId()}/");
    }

    protected function handleEnable()
    {
        list(, $domainId) = func_get_args();
        $domain = AccountProductQuery::create()->findOneById($domainId);
        \Transip_DomainService::setLock($domain->getName());
        $this->setStatus('Uw domein is beveiligd en kan hierdoor niet verhuisd worden', self::STATUS_SUCCESS);
        unset($_SESSION['domainInfo'][$domainId]);
        $this->redirect("/account/product/domain/manage/{$domain->getId()}/");
    }
}
