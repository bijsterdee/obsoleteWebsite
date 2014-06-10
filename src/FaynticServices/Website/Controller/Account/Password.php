<?php

namespace FaynticServices\Website\Controller\Account;

use FaynticServices\Website\Controller;
use FaynticServices\Website\Model\AccountQuery;

class Password extends Controller
{
    protected function preDispatch()
    {
        if (!isset($_SERVER['HTTPS'])) {
            $hostname = filter_input(INPUT_SERVER, 'HTTP_HOST');
            $requestUrl = filter_input(INPUT_SERVER, 'REQUEST_URI');
            $this->redirect("https://{$hostname}{$requestUrl}");
        }
        parent::preDispatch();
    }

    public function handlePostDefault()
    {
        $code = filter_input(INPUT_POST, 'code');
        $passwordNew = filter_input(INPUT_POST, 'new-password');
        $passwordNew2 = filter_input(INPUT_POST, 'new-password2');

        try {
            if ($passwordNew !== $passwordNew2) {
                throw new \Exception('De opgegeven nieuwe wachtwoorden komen niet overeen');
            }

            if ($this->account) {
                $passwordOld = filter_input(INPUT_POST, 'old-password');
                if (!password_verify($passwordOld, $this->account->getPassword())) {
                    throw new \Exception('Het huidige wachtwoord komt niet overeen.');
                }
            } else {
                $this->account = AccountQuery::create()->findOneByResetCode($code);
                if (!$this->account) {
                    throw new \Exception('De opgegeven code bestaat niet of is niet langer geldig.', 1);
                }
            }
        } catch (\Exception $exception) {
            $this->setStatus($exception->getMessage());
            switch ($exception->getCode()) {
                case 1:
                    $this->redirect('/account/reset/code/');
                    return;
                default:
                    $this->redirect('/account/password/');
            }
        }

        $this->account->setResetCode(null);
        $this->account->setPassword(password_hash($passwordNew, PASSWORD_BCRYPT, ['cost' => $this->container->get('configuration')->security->password->cost]));
        $this->account->save();
        $_SESSION['account'] = $this->account->getId();
        $this->setStatus('Het wachtwoord is succesvol gewijzigd', self::STATUS_SUCCESS);
        $this->redirect('/account/');
    }

    public function handleDefault($code = null)
    {
        try {
            if ($code) {
                $account = AccountQuery::create()->findOneByResetCode($code);
                if (!$account) {
                    throw new \Exception('De opgegeven code bestaat niet of is niet langer geldig.', 1);
                }
            } elseif (!$this->account) {
                throw new \Exception('Je dient ingelogd te zijn alvorens deze pagina te kunnen bezoeken.', 2);
            }
        } catch (\Exception $exception) {
            $this->setStatus($exception->getMessage());
            switch ($exception->getCode()) {
                case 1:
                    $this->redirect('/account/reset/code/');
                    return;
                default:
                    $this->redirect('/');
            }
        }
        $this->setTemplate('Account/password.twig', [
            'accountData' => isset($account) ? $account : $this->account,
            'code' => $code
        ]);
    }
}