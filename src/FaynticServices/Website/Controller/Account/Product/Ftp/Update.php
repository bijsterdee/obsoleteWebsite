<?php

namespace FaynticServices\Website\Controller\Account\Product\Ftp;

use FaynticServices\Website\Controller\Account\Product\Ftp;
use FaynticServices\Website\Model\AccountFtp;
use FaynticServices\Website\Model\AccountFtpQuery;

class Update extends Ftp
{
    /**
     * @param int $accountFtpId
     */
    public function handlePostDefault($accountFtpId = null)
    {
        $location = filter_input(INPUT_POST, 'location');
        $password = filter_input(INPUT_POST, 'password');

        $accountFtp = AccountFtpQuery::create()->filterByAccount($this->account)->findOneById($accountFtpId);
        try {
            $this->getValidator()->validateLocation($this->home.$location);
            $accountFtp->setLocation($location);

            if ($password) {
                $this->getValidator()->validatePassword($password);
                $accountFtp->setPassword("{sha1}" . base64_encode(pack("H*", sha1($password))));
            };

            $accountFtp->setUid($this->uid);
            $accountFtp->setGid($this->gid);
            $accountFtp->save();
            $this->setStatus('De wijzigingen zijn succesvol opgeslagen', self::STATUS_SUCCESS);
            $this->redirect("/account/product/ftp/update/{$accountFtp->getId()}/");
        } catch (\UnexpectedValueException $exception) {
            $accountFtp->setLocation($location);
            $this->showTemplate($accountFtp, $exception);
        }
    }

    /**
     * @param int $accountFtpId
     */
    public function handleGetDefault($accountFtpId = null)
    {
        $accountFtp = AccountFtpQuery::create()->filterByAccount($this->account)->findOneById($accountFtpId);
        try {
            $this->showTemplate($accountFtp);
        } catch (\Exception $exception) {
            $this->setStatus('Het opgegeven account bestaat niet meer of kon niet worden geladen', self::STATUS_WARNING);
            $this->redirect('/account/product/ftp/');
        }
    }

    /**
     * @param AccountFtp $accountFtp
     * @param \UnexpectedValueException $exception
     */
    private function showTemplate(AccountFtp $accountFtp, \UnexpectedValueException $exception = null)
    {
        if ($exception) {
            $this->setStatus($exception->getMessage(), self::STATUS_DANGER);
        }

        $this->setTemplate('Account/Product/Ftp/Update.twig', [
            'user' => $accountFtp
        ]);
    }
}
