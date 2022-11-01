<?php

namespace Ornament\Bitflag;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Options
{
    public function __construct(private string $enum) {}

    public function getEnum()
    {
        return $this->enum;
    }
}

