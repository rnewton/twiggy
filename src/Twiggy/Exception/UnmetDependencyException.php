<?php

namespace Twiggy\Exception;

class UnmetDependencyException extends \Exception
{
    public function __construct($id)
    {
        parent::__construct("Unmet dependency $id");
    }
}