<?php

namespace FaynticServices\Website\Controller\Account\Product\Ftp;

use FaynticServices\Website\Controller\Account\Product\Ftp;
use FaynticServices\Website\Model\Base\AccountFtpQuery;

class Delete extends Ftp
{
    public function handleGetDefault($accountFtpId = null)
    {
        try {
            if ($accountFtp = AccountFtpQuery::create()->filterByAccount($this->account)->findOneById($accountFtpId)) {
                $accountFtp->delete();
                $this->setStatus('Het account is succesvol verwijderd', self::STATUS_INFO);
            } else {
                throw new \Exception('Het opgegeven account bestaat niet meer of kon niet worden verwijderd');
            }
        } catch (\Exception $exception) {
            $this->setStatus($exception->getMessage(), self::STATUS_WARNING);
        } finally {
            $this->redirect('/account/product/ftp/');
        }
    }
}
