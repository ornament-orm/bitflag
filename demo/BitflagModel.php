<?php

namespace Ornament\Demo;

use Ornament\Core\Model;

class BitflagModel
{
    use Model;

    /**
     * @var Ornament\Bitflag\Property
     * @construct nice = 1, cats = 2, code = 4
     */
    public $status = 0;
}

