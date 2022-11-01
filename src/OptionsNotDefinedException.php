<?php

namespace Ornament\Bitflag;

use DomainException;

class OptionsNotDefinedException
{
    public function __construct(string $class, string $property)
    {
        parent::__construct("The property $class::$property was type hinted as a Bitflag, but no Options were specified.");
    }
}

