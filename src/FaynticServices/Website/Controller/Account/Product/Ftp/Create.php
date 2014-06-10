<?php

namespace FaynticServices\Website\Controller\Account\Product\Ftp;

use FaynticServices\Website\Controller\Account\Product\Ftp;
use FaynticServices\Website\Model\AccountFtp;

class Create extends Ftp
{
    public function handlePostDefault()
    {
        $username = filter_input(INPUT_POST, 'username');
        $password = filter_input(INPUT_POST, 'password');
        $location = filter_input(INPUT_POST, 'location');
        $accountFtp = new AccountFtp();
        try {
            $accountFtp->setAccount($this->account);

            $this->getValidator()->validateUsername($username);
            $accountFtp->setUsername($username);

            $this->getValidator()->validatePassword($password);
            $accountFtp->setPassword("{sha1}" . base64_encode(pack("H*", sha1($password))));

            $this->getValidator()->validateLocation($this->home.$location);
            $accountFtp->setLocation($location);

            $accountFtp->setUid($this->uid);
            $accountFtp->setGid($this->gid);
            $accountFtp->save();
            $this->setStatus('Het account is succesvol toegevoegd', self::STATUS_SUCCESS);
            $this->redirect("/account/product/ftp/update/{$accountFtp->getId()}/");
        } catch (\UnexpectedValueException $exception) {
            $accountFtp->setUsername($username);
            $accountFtp->setLocation($location);
            $this->showTemplate($accountFtp, $exception);
        }
    }

    public function handleGetDefault()
    {
        $accountFtp = new AccountFtp();
        $accountFtp->setAccount($this->account);
        $this->showTemplate($accountFtp);
    }

    private function showTemplate(AccountFtp $accountFtp, \UnexpectedValueException $exception = null)
    {
        if ($exception) {
            $this->setStatus($exception->getMessage(), self::STATUS_DANGER);
        }

        $this->setTemplate('Account/Product/Ftp/Create.twig', [
            'user' => $accountFtp
        ]);
    }
}
