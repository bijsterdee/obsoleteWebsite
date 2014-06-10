<?php

namespace FaynticServices\Website;

class Configuration
{
    /** @var \stdClass */
    private $configuration;

    public function __get($field) {
        return $this->configuration->$field;
    }

    public function loadConfiguration($configurationFile)
    {
        $this->configuration = json_decode(file_get_contents($configurationFile));
    }

    public function toArray()
    {
        return $this->configuration;
    }
}
