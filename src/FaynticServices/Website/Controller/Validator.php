<?php

namespace FaynticServices\Website\Controller;

class Validator
{
    /**
     * @param $username
     * @throws \UnexpectedValueException
     */
    public function validateUsername($username)
    {
        if (mb_strlen($username) < 3) {
            throw new \UnexpectedValueException('De gebruikersnaam mag niet korter dan 3 tekens zijn');
        } elseif (mb_strlen($username) > 32) {
            throw new \UnexpectedValueException('De gebruikersnaam mag niet langer dan 32 tekens zijn');
        }
    }

    /**
     * @param $location
     * @throws \UnexpectedValueException
     */
    public function validateLocation($location)
    {
        if (!realpath($location)) {
            throw new \UnexpectedValueException('De opgegeven locatie bestaat niet op de server');
        }
    }

    public function validatePassword($password)
    {
        if (mb_strlen($password) < 6) {
            throw new \UnexpectedValueException('Het wachtwoord moet tenminste 6 tekens lang zijn');
        } elseif (!preg_match('/[a-z]/', $password)) {
            throw new \UnexpectedValueException('Het wachtwoord moet tenminste één kleine letter bevatten');
        } elseif (!preg_match('/[A-Z]/', $password)) {
            throw new \UnexpectedValueException('Het wachtwoord moet tenminste één hoofdletter bevatten');
        } elseif (!preg_match('/[0-9]/', $password)) {
            throw new \UnexpectedValueException('Het wachtwoord moet tenminste één nummer bevatten');
        }
    }
}
