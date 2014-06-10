<?php

namespace FaynticServices\Website\Controller\Account\Product\Ftp;

use FaynticServices\Website\Controller\Account\Product\Ftp;
use FaynticServices\Website\Model\AccountFtpQuery;

class Validator extends \FaynticServices\Website\Controller\Validator
{
    /**
     * @param $username
     * @throws \UnexpectedValueException
     */
    public function validateUsername($username)
    {
        parent::validateUsername($username);

        $accountFtp = AccountFtpQuery::create()->findOneByUsername($username);
        if ($accountFtp) {
            throw new \UnexpectedValueException('De opgegeven gebruikersnaam bestaat al');
        }
    }

    public function validateLocation($location)
    {
        if (!realpath($location)) {
            throw new \UnexpectedValueException('De opgegeven locatie bestaat niet op de server');
        }
    }
}
