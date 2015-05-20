<?php

namespace Twiggy\Exception;

class MissingConfigurationKeyException extends \Exception
{
    public function __construct($key)
    {
        parent::__construct("Missing required configuration key '$key'.");
    }
}