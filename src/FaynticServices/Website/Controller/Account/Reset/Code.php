<?php

namespace FaynticServices\Website\Controller\Account\Reset;

use FaynticServices\Website\Controller\Account as AccountController;
use FaynticServices\Website\Model\Account;
use FaynticServices\Website\Model\AccountQuery;

class Code extends AccountController
{
    public function handleDefault($code = null)
    {
        $account = AccountQuery::create()->findOneByResetCode($code);
        if ($code && $account) {
            $this->handleReset($account);
        } else {
            if ($code && !$account) {
                $this->setStatus('De opgegeven code is ongeldig');
            }
            $this->setTemplate('Account/reset/form.twig', []);
        }
    }

    public function handlePostDefault()
    {
        $code = urlencode(filter_input(INPUT_POST, 'code'));
        $this->redirect("/account/password/{$code}/");
    }

    public function handleReset(Account $account)
    {
        $_SESSION['account'] = $account->getId();
        $this->setTemplate('Account/reset/code.twig', [
            'account' => $account
        ]);
    }
}