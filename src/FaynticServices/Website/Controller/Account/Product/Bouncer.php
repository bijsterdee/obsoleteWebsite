<?php

namespace FaynticServices\Website\Controller\Account\Product;

use FaynticServices\Website\Configuration;
use FaynticServices\Website\Controller\Account\Product;
use FaynticServices\Website\Model\AccountProduct;
use FaynticServices\Website\Model\AccountProductQuery;

// todo remove duplicate code + optimize IRC connection (incl. seperate class)
class Bouncer extends Product
{
    protected function preDispatch()
    {
        /** @var Configuration $configuration */
        $configuration = $this->container->get('configuration');

        \Transip_ApiSettings::$mode = $configuration->vendor->transip->mode;
        \Transip_ApiSettings::$login = $configuration->vendor->transip->login;
        \Transip_ApiSettings::$privateKey = $configuration->vendor->transip->privatekey;
        parent::preDispatch();
    }

    protected function handleAjaxValidateHostname($username)
    {
        $this->validateHostname(filter_input(INPUT_POST, 'value'), filter_input(INPUT_POST, 'Address', FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
    }

    protected function handleModify()
    {
        /** @var AccountProduct $accountProductBouncer */
        $accountProductBouncer = AccountProductQuery::create()->filterByAccount($this->account)->useProductQuery()->filterByCategory('bouncer')->endUse()->findOne();
        $bouncer = $this->getBouncerData($accountProductBouncer->getName());
//        $this->validateHostname(filter_input(INPUT_POST, 'hostname'), $bouncer['address']);
        if ($password = filter_input(INPUT_POST, 'password')) {
            $this->setBouncerPassword($bouncer['login'], $password);
        }
        $this->setStatus('Wijzigingen zijn succesvol opgeslagen', self::STATUS_SUCCESS);
        $this->redirect('/account/product/bouncer/');
    }

    protected function handleDefault()
    {
        /** @var AccountProduct $accountProductBouncer */
        $accountProductBouncer = AccountProductQuery::create()->filterByAccount($this->account)->useProductQuery()->filterByCategory('bouncer')->endUse()->findOne();
        if ($accountProductBouncer) {

            $bouncer = $this->getBouncerData($accountProductBouncer->getName());
            $addressParts = explode(':', $bouncer['address']);
            foreach ($addressParts as &$addressPart) {
                $addressPart = str_pad($addressPart, 4, 0, STR_PAD_LEFT);
            }
            $defaultHostnamePart = implode('-', $addressParts);
        }

        $this->setTemplate('Account/Product/Bouncer.twig', [
            'bouncer' => isset($bouncer) ? $bouncer : null,
            'hostname' => isset($defaultHostnamePart) ? "ip{$defaultHostnamePart}.fayntic.com" : null
        ]);
    }

    private function validateHostname($hostname, $address)
    {
        $lookupAddresses = dns_get_record($hostname, DNS_AAAA);
        foreach ($lookupAddresses as $lookupAddress) {
            if (isset($lookupAddress['ipv6']) && $lookupAddress['ipv6'] === $address) {
                return;
            }
        }
        throw new \UnexpectedValueException("De opgegeven hostnaam verwijst niet naar {$address}");
    }

    protected function setBouncerPassword ($username, $password)
    {
        /** @var Configuration $configuration */
        $configuration = $this->container->get('configuration');

        $socket = @stream_socket_client($configuration->bouncer->server->address, $errorCode, $errorMessage, 5, STREAM_CLIENT_CONNECT, stream_context_create());
        if ($errorMessage) {
            throw new \Exception($errorMessage, $errorCode);
        }

        fwrite($socket, "NICK {$configuration->bouncer->server->nickname}\n");
        fwrite($socket, "USER {$configuration->bouncer->server->username} \"\" \"\" :{$configuration->bouncer->server->realname}\n");
        fwrite($socket, "PASS {$configuration->bouncer->server->password}\n");
        fwrite($socket, "PRIVMSG *controlpanel :Set Password {$username} {$password}\n");
        fwrite($socket, "PRIVMSG *status :Version\n");

        while (!feof($socket)) {
            $data = trim(fgets($socket, 1024));

            if (preg_match('/^:\*status!znc@znc.in PRIVMSG .+ :ZNC \d+\.\d+ - http:\/\/znc\.in$/', $data)) {
                fclose($socket);
                break;
            }
        }
    }

    protected function getBouncerData($username)
    {
        /** @var Configuration $configuration */
        $configuration = $this->container->get('configuration');

        $socket = @stream_socket_client($configuration->bouncer->server->address, $errorCode, $errorMessage, 5, STREAM_CLIENT_CONNECT, stream_context_create());
        if ($errorMessage) {
            throw new \Exception($errorMessage, $errorCode);
        }

        fwrite($socket, "NICK {$configuration->bouncer->server->nickname}\n");
        fwrite($socket, "USER {$configuration->bouncer->server->username} \"\" \"\" :{$configuration->bouncer->server->realname}\n");
        fwrite($socket, "PASS {$configuration->bouncer->server->password}\n");
        fwrite($socket, "PRIVMSG *controlpanel :ListUsers\n");
        fwrite($socket, "PRIVMSG *controlpanel :ListNetworks {$username}\n");
        fwrite($socket, "PRIVMSG *lastseen :Show\n");
        fwrite($socket, "PRIVMSG *status :Version\n");

        while (!feof($socket)) {
            $data = trim(fgets($socket, 1024));

            if (preg_match('/^:\*controlpanel!znc@znc\.in PRIVMSG .+ :\| (?<login>.+)\s+\| (?<realname>.*)\s+\| (?<admin>No|Yes)\s+ \| (?<nickname>.+)\s+\| (?<altnick>.+)\s+\| (?<username>.+)\s+\| (?<address>.+)\s+\|$/', $data, $userData)) {
                foreach ($userData as $type => $value) {
                    if (is_int($type)) {
                        unset($userData[$type]);
                    } else {
                        $userData[$type] = trim($value);
                    }
                }
                if ($username === $userData['login']) {
                    $userData['hostname'] = gethostbyaddr($userData['address']);
                    $resultSet = $userData;
                }
            } elseif (preg_match('/^:\*lastseen!znc@znc\.in PRIVMSG .+ :\| (?<login>.+)\s+\| (?<date>.+)\s+\|$/', $data, $lastseenData)) {
                foreach ($lastseenData as $type => $value) {
                    if (is_int($type)) {
                        unset($lastseenData[$type]);
                    } else {
                        $lastseenData[$type] = trim($value);
                    }
                }
                if ($username === $lastseenData['login']) {
                    try {
                        $resultSet['lastseen'] = new \DateTime($lastseenData['date']);
                    } catch (\Exception $exception) {
                        $resultSet['lastseen'] = false;
                    }
                }
            } elseif (preg_match('/^:\*controlpanel!znc@znc.in PRIVMSG .+ :\| (?<network>.+)\s+\| (?<connected>No|Yes)\s+\| (?<server>.+)?\s+\| ((?<nickname>.+)!(?<username>.+)@(?<hostname>.*))?\s+\| (?<channels>\d+)?\s+\|$/', $data, $networkConnection)) {
                foreach ($networkConnection as $type => $value) {
                    if (is_int($type)) {
                        unset($networkConnection[$type]);
                    } else {
                        $networkConnection[$type] = trim($value);
                    }
                }
                if (isset($resultSet)) {
                    if (!isset($resultSet['networks'])) {
                        $resultSet['networks'] = array();
                    };
                    $resultSet['networks'][] = $networkConnection;
                }
            } elseif (preg_match('/^:\*status!znc@znc.in PRIVMSG .+ :ZNC \d+\.\d+ - http:\/\/znc\.in$/', $data)) {
                fclose($socket);
                break;
            }
        }

        if (isset($resultSet)) {
            return $resultSet;
        }
        return false;
    }

    /**
     * @param $username
     * @param $password
     * @return bool|string
     * @throws \Exception
     */
    protected function addBouncer($username, $password)
    {
        $resultSet = array();

        /** @var Configuration $configuration */
        $configuration = $this->container->get('configuration');

        $socket = @stream_socket_client($configuration->bouncer->server->address, $errorCode, $errorMessage, 5, STREAM_CLIENT_CONNECT, stream_context_create());
        if ($errorMessage) {
            throw new \Exception($errorMessage, $errorCode);
        }

        fwrite($socket, "NICK {$configuration->bouncer->server->nickname}\n");
        fwrite($socket, "USER {$configuration->bouncer->server->username} \"\" \"\" :{$configuration->bouncer->server->realname}\n");
        fwrite($socket, "PASS {$configuration->bouncer->server->password}\n");
        fwrite($socket, "PRIVMSG *status :ListBindHosts\n");
        fwrite($socket, "PRIVMSG *controlpanel :ListUsers\n");
        fwrite($socket, "PRIVMSG *status :Uptime\n");

        while (!feof($socket)) {
            $data = trim(fgets($socket, 1024));

            if (false !== strpos($data, ":*status!znc@znc.in PRIVMSG Fayntic :| {$configuration->bouncer->ip_prefix}")) {
                $ipAddress = array_map('trim', (explode('|', $data)))[1];
                $resultSet[$ipAddress] = [
                    'address' => $ipAddress,
                    'available' => true,
                    'username' => null
                ];
            }

            if (false !== strpos($data, ':*controlpanel!znc@znc.in PRIVMSG Fayntic :|') && false !== strpos($data, $configuration->bouncer->ip_prefix)) {
                $userData = array_map('trim', (explode('|', $data)));
                if (isset($resultSet[$userData[7]])) {
                    $resultSet[$userData[7]]['available'] = false;
                    $resultSet[$userData[7]]['username'] = $userData[1];
                }

                // todo remove testusers
                if (false !== strpos($userData[1], 'test')) {
                    fwrite($socket, "PRIVMSG *controlpanel :DelUser {$userData[1]}\n");
                }
            }

            if (false !== strpos($data, ':*status!znc@znc.in PRIVMSG Fayntic :Running for')) {
                $filteredAddresses = array_column($resultSet, 'available', 'address');
                if ($availableAddress = array_search(true, $filteredAddresses, true)) {
                    fwrite($socket, "PRIVMSG *controlpanel :AddUser {$username} {$password}\n");
                    foreach (['buffextras', 'chansaver', 'controlpanel', 'perform'] as $module) {
                        fwrite($socket, "PRIVMSG *controlpanel :LoadModule {$username} {$module}\n");
                    }
                    fwrite($socket, "PRIVMSG *controlpanel :Set DenySetBindHost true\n");
                    fwrite($socket, "PRIVMSG *controlpanel :Set BindHost {$username} {$availableAddress}\n");
                    fwrite($socket, "PRIVMSG *controlpanel :Set DCCBindHost {$username} {$availableAddress}\n");
                }
                fwrite($socket, "PRIVMSG *status :Version\n");
            }

            if (false !== strpos($data, ':*status!znc@znc.in PRIVMSG Fayntic :ZNC')) {
                fclose($socket);
                break;
            }
        }

        if (isset($availableAddress)) {
            return $availableAddress;
        }
        return false;
    }
}
