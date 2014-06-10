<?php

namespace FaynticServices\Website\Controller\Account;

use FaynticServices\Website\Controller\Account as AccountController;
use FaynticServices\Website\Model\AccountContactQuery;
use RandomLib\Factory;

class Reset extends AccountController
{
    public function handlePostDefault()
    {
        try {
            $emailAddress = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            if (!$emailAddress) {
                throw new \Exception('Het opgegeven e-mail adres is ongeldig');
            }

            $accountContact = AccountContactQuery::create()->findOneByEmail($emailAddress);
            if ($accountContact) {

                $factory = new Factory();
                $generator = $factory->getLowStrengthGenerator();

                $account = $accountContact->getAccount();
                $account->setResetCode($generator->generateString(40, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
                $account->save();

                $twig = $this->getTwigTemplate();
                $message = $twig->render('mail/account/reset.twig', [
                    'ip' => filter_input(INPUT_SERVER, 'REMOTE_ADDR'),
                    'contact' => $accountContact,
                    'server' => array_change_key_case($_SERVER, CASE_LOWER),
                    'code' => $account->getResetCode()
                ]);

                $message = new \Swift_Message('Wachtwoord vergeten', $message, 'text/html');
                $message->addFrom('no-reply@fayntic.com', 'Fayntic Services');
                $message->addTo($accountContact->getEmail(), $accountContact->getName());

                $transport = new \Swift_MailTransport();
                $transport->send($message);
            }
            $this->setStatus('Indien het opgegeven e-mail adres bekend is, is er een e-mail verstuurd');
            $this->redirect('/account/');
        } catch (\Exception $exception) {
            $this->setStatus($exception->getMessage());
            $this->redirect('/account/reset/');
        }
    }

    public function handleDefault()
    {
        $this->setTemplate('Account/Reset.twig', []);
    }
}