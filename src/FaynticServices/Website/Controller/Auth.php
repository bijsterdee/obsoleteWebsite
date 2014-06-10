<?php

namespace FaynticServices\Website\Controller;

class Auth extends Account
{
    protected function preDispatch()
    {
        parent::preDispatch();
        if (!$this->account) {
            $this->setStatus('U dient ingelogd te zijn om deze pagina te bekijken');
            $hostname = filter_input(INPUT_SERVER, 'HTTP_HOST');
            $this->redirect("https://{$hostname}");
        }
    }
}
