<?php

namespace FaynticServices\Website\Controller;

use FaynticServices\Website\Controller;
use FaynticServices\Website\Model\Language;
use FaynticServices\Website\Model\LanguageQuery;

class Maintenance extends Controller
{
    protected function dispatchStartup()
    {
        try {
            $acceptLanguage = locale_accept_from_http(filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
            /** @var Language[] $languages */
            $languages = LanguageQuery::create()->find();
            foreach ($languages as $language) {
                if (false !== strpos($language->getIdentifier(), $acceptLanguage)) {
                    $this->locale = $language->getIdentifier();
                    break;
                } elseif (!isset($this->locale) && $language->getIsDefault()) {
                    $this->locale = $language->getIdentifier();
                }
            }
        } catch (\Exception $exception) { }
    }

    protected function preDispatch()
    {
    }

    protected function postDispatch()
    {
    }


    protected function handleDefault()
    {
        if (!$this->getMaintenance()) {
            $this->redirect('/');
        }
        header('HTTP/1.1 503 Service Unavailable', true, 503);
        header('Retry-After: 300');
        $this->redirect('/maintenance/', 503, 300);
        $this->setLayout('Maintenance.twig');
    }
}
