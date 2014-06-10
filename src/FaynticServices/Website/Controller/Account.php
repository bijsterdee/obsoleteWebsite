<?php

namespace FaynticServices\Website\Controller;

use FaynticServices\Website\Controller;
use FaynticServices\Website\Model\AccountContact;
use FaynticServices\Website\Model\AccountProduct;
use FaynticServices\Website\Model\AccountProductQuery;
use FaynticServices\Website\Model\AccountQuery;
use FaynticServices\Website\Model\CountryQuery;
use FaynticServices\Website\Model\LanguageQuery;

class Account extends Controller
{
    protected function preDispatch()
    {
        $hostname = filter_input(INPUT_SERVER, 'HTTP_HOST');
        if (!isset($_SERVER['HTTPS'])) {
            $requestUrl = filter_input(INPUT_SERVER, 'REQUEST_URI');
            $this->redirect("https://{$hostname}{$requestUrl}");
        }
        parent::preDispatch();
    }

    protected function handleAjaxValidateName()
    {
        $this->validateName(filter_input(INPUT_POST, 'value'));
    }

    protected function handleAjaxValidateAddress()
    {
        $this->validateAddress(filter_input(INPUT_POST, 'value'));
    }

    protected function handleAjaxValidateCountry()
    {
        $this->validateCountry(filter_input(INPUT_POST, 'value'));
    }

    protected function handleAjaxValidateEmail()
    {
        $this->validateEmail(filter_input(INPUT_POST, 'value'));
    }

    protected function handleAjaxValidateUsername()
    {
        $this->validateUsername(filter_input(INPUT_POST, 'value'));
    }

    protected function handleAjaxValidatePassword()
    {
        if (filter_input(INPUT_POST, 'comparison')) {
            if (filter_input(INPUT_POST, 'comparison') !== filter_input(INPUT_POST, 'value')) {
                throw new \UnexpectedValueException('Het wachtwoord komt niet overeen');
            }
        } else {
            $this->validatePassword(filter_input(INPUT_POST, 'value'));
        }
    }

    protected function handleAuthentication()
    {
        if (filter_input(INPUT_POST, 'username') && filter_input(INPUT_POST, 'password')) {
            /** @var \FaynticServices\Website\Model\Account $account */
            $account = AccountQuery::create()->findOneByLogin(filter_input(INPUT_POST, 'username'));
            if ($account && password_verify(filter_input(INPUT_POST, 'password'), $account->getPassword())) {
                $account->setPassword(password_hash(filter_input(INPUT_POST, 'password'), PASSWORD_BCRYPT, ['cost' => $this->container->get('configuration')->security->password->cost]));
                $_SESSION['account'] = $account->getId();
                $this->setStatus('Je bent succesvol ingelogd', self::STATUS_SUCCESS);
                $account->save();
            } else {
                $this->setStatus('De opgegeven gebruikersnaam en/of wachtwoord is ongeldig.', self::STATUS_DANGER);
            }
        } else {
            $this->setStatus('Er is geen gebruikersnaam en/of wachtwoord opgegeven.', self::STATUS_INFO);
        }
        $this->redirect('/account/');
    }

    protected function handleRegister()
    {
        $password = trim(filter_input(INPUT_POST, 'new-password'));
        $password2 = trim(filter_input(INPUT_POST, 'new-password2'));

        if ($password !== $password2) {
            $this->redirect('/account/login/');
        }

        $accountContact = new AccountContact();
        $accountContact->setGender(trim(filter_input(INPUT_POST, 'gender')));
        $accountContact->setName(trim(filter_input(INPUT_POST, 'name')));
        $accountContact->setAddress(trim(filter_input(INPUT_POST, 'address')));
        $accountContact->setCountryId(trim(filter_input(INPUT_POST, 'country_id', FILTER_VALIDATE_INT)));
        $accountContact->setEmail(trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)));
        $accountContact->setCountryId(1);

        $account = new \FaynticServices\Website\Model\Account();
        $account->setLogin(trim(filter_input(INPUT_POST, 'username')));
        $account->setLanguage(LanguageQuery::create()->findOneByIdentifier($this->locale));
        $account->setPassword(password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->container->get('configuration')->security->password->cost]));
        $account->setAccountContact($accountContact);
        $account->save();

        $this->redirect('/account/login/');
    }

    protected function handleDefault()
    {
        if ($this->account) {
            $this->handleDashboard();
        } else {
            $this->handleLogin();
        }
    }

    protected function handleDashboard()
    {
        $accountProductQuery = AccountProductQuery::create();
        $accountProductQuery->filterByAccount($this->account);
        $accountProductQuery->joinWith('Product');
        $accountProductQuery->useProductQuery()->joinWithI18n($this->locale)->endUse();

        /** @var AccountProduct[] $products */
        $products = $accountProductQuery->find();
        foreach ($products as $product) {
            /** @var \DateTime $createdAt */
            $createdAt = $product->getCreatedAt();
            $dateInterval = $createdAt->diff(new \DateTime());
            $createdAt->modify($dateInterval->format('-1 day +%m months'));
            $createdAt->modify("+{$product->getProduct()->getPeriod()} {$product->getProduct()->getPeriodUnit()}");
            $product->setVirtualColumn('terminationDate', $createdAt);
        }

        $this->setTemplate('Account/Dashboard.twig', [
            'countries' => CountryQuery::create()->useI18nQuery($this->locale)->orderByName()->endUse()->joinWithI18n($this->locale)->find(),
            'products' => $products
        ]);
    }

    protected function handleLogin()
    {
        $this->setTemplate('Account.twig', ['countries' => CountryQuery::create()->useI18nQuery($this->locale)->orderByName()->endUse()->joinWithI18n($this->locale)->find()]);
    }

    protected function handleLogout()
    {
        session_unset();
        $this->redirect('/');
    }

    private function validateName($name)
    {
        if (!preg_match('/^(.){3,}\s(.){3,}$/', $name)) {
            throw new \UnexpectedValueException('Een volledige naam (voor- en achternaam) is vereist');
        }
    }

    private function validateAddress($address)
    {
        if (empty($address)) {
            throw new \UnexpectedValueException('Het adres mag niet leeg zijn');
        }
    }

    private function validateCountry($countryId)
    {
        if (!CountryQuery::create()->findOneById($countryId)) {
            throw new \UnexpectedValueException('Het opgegeven land is ongeldig');
        }
    }

    private function validateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \UnexpectedValueException('Het e-mail adres is ongeldig');
        }
    }

    public function validateUsername($username)
    {
        if (!preg_match('/[a-z0-9]{3,}/i', $username)) {
            throw new \UnexpectedValueException('Gebruikersnaam moet tenminste 3 tekens lang zijn (met een maximum van 32)');
        } elseif (AccountQuery::create()->findOneByLogin($username)) {
            throw new \UnexpectedValueException('De gekozen gebruikersnaam is al bezet');
        }
    }

    protected function validatePassword($password)
    {
        if (strlen($password) <= 6) {
            throw new \UnexpectedValueException('Het wachtwoord moet tenminste 6 tekens lang zijn');
        }
    }
}
