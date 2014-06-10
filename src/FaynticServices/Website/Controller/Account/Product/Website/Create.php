<?php

namespace FaynticServices\Website\Controller\Account\Product\Website;

use FaynticServices\Website\Controller\Account\Product\Website;
use FaynticServices\Website\Model\AccountWeb;

class Create extends Website
{
    public function handleGetDefault()
    {
        $accountWebsite = new AccountWeb();
        $accountWebsite->setAccount($this->account);
        $this->showTemplate($accountWebsite);
    }

    private function showTemplate(AccountWeb $accountWebsite, \UnexpectedValueException $exception = null)
    {
        if ($exception) {
            $this->setStatus($exception->getMessage(), self::STATUS_DANGER);
        }

        $this->setTemplate('Account/Product/Website/Create.twig', [
            'website' => $accountWebsite
        ]);
    }
}
