<?php

namespace FaynticServices\Website\Controller\Account\Product\Domain;

use FaynticServices\Website\Controller\Account\Product\Domain;
use FaynticServices\Website\Model\AccountProductQuery;

class Manage extends Domain
{
    protected function handlePostDefault()
    {
        list($domainId) = func_get_args();
        $domain = AccountProductQuery::create()->findOneById($domainId);

        $entries = array();
        foreach ($_POST['record'] as $entry) {
            $entries[] = new \Transip_DnsEntry($entry['name'], $entry['ttl'], $entry['type'], $entry['content']);
        }

        try {
            \Transip_DomainService::setDnsEntries(strtolower($domain->getName()), $entries);
            $this->setStatus('De gemaakte wijzigingen zijn succesvol opgeslagen.', self::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $this->setStatus($exception->getMessage(), self::STATUS_WARNING);
        }
        unset($_SESSION['domainInfo'][$domainId]);
        $this->redirect($_SERVER['REQUEST_URI']);
    }

    protected function handleDefault()
    {
        list($domainId) = func_get_args();
        $domain = AccountProductQuery::create()->filterByAccount($this->account)->findOneById($domainId);
        if (!$domain) {
            $this->redirect('/');
        }
        if (!isset($_SESSION['domainInfo'][$domainId])) {
            $_SESSION['domainInfo'][$domainId] = \Transip_DomainService::getInfo(strtolower($domain->getName()));
        }
        $domainInfo = $_SESSION['domainInfo'][$domainId];


        $this->setTemplate('Account/Product/Domain/Manage.twig', [
            'domain' => $domain,
            'transip' => $domainInfo
        ]);
    }
}
