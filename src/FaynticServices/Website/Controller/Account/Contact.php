<?php

namespace FaynticServices\Website\Controller\Account;

use FaynticServices\Website\Controller\Account as AccountController;
use FaynticServices\Website\Controller\Auth;
use FaynticServices\Website\Model\CountryQuery;
use FaynticServices\Website\Model\LanguageQuery;

class Contact extends Auth
{
    public function handleUpdate()
    {
        $accountContact = $this->account->getAccountContact();
        $accountContact->setGender(filter_input(INPUT_POST, 'gender'));
        $accountContact->setName(filter_input(INPUT_POST, 'name'));
        $accountContact->setAddress(filter_input(INPUT_POST, 'address'));
        $accountContact->setEmail(filter_input(INPUT_POST, 'email'));
        $accountContact->setPhone(filter_input(INPUT_POST, 'phone'));
        $accountContact->setCountryId(filter_input(INPUT_POST, 'country_id'));

        $this->account->setAccountContact($accountContact);
        $this->account->setLanguageId(filter_input(INPUT_POST, 'language_id'));

        $this->account->save();
        $this->setStatus('De gemaakte wijzigingen zijn succesvol opgeslagen.', self::STATUS_SUCCESS);

        $this->redirect('/account/dashboard/');
    }

    public function handleDefault()
    {
        $this->setTemplate('Account/Contact.twig', [
            'countries' => CountryQuery::create()->useI18nQuery($this->locale)->orderByName()->endUse()->joinWithI18n($this->locale)->find(),
            'languages' => LanguageQuery::create()->useI18nQuery($this->locale)->orderByName()->endUse()->joinWithI18n($this->locale)->find()
        ]);
    }
}
