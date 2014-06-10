<?php

namespace FaynticServices\Website\Controller\Account\Product\Mail;

use FaynticServices\Website\Controller\Account\Product\Mail;
use FaynticServices\Website\Model\AccountEmail;
use FaynticServices\Website\Model\Base\AccountEmailQuery;

class Account extends Mail
{
    protected function handleAjaxValidateQuota()
    {
    }

    protected function handleGetCreate()
    {

    }

    protected function handleGetUpdate($unused, $accountEmailId)
    {
        $accountEmail = AccountEmailQuery::create()->findOneById($accountEmailId);
        $this->actionShow($accountEmail);
    }

    protected function handleGetDelete()
    {

    }

    private function actionShow(AccountEmail $accountEmail, \Exception $exception = null)
    {
        if ($exception) {
            $this->setStatus($exception, self::STATUS_DANGER);
        }

        $this->setTemplate('Account/Product/Email/Account.twig', [
            'accountEmail' => $accountEmail
        ]);
    }

    private function actionManage(AccountEmail $accountEmail)
    {

    }

    private function actionDelete(AccountEmail $accountEmail)
    {

    }
}
