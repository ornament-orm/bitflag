<?php

use Ornament\Bitflag\{ Bitflag, Options, FlagNotDefinedException, OptionsInvalidException };
use Ornament\Core\Model;

enum Status : int
{
    case nice = 1;
    case cats = 2;
    case code = 4;
}

return function () : Generator {
    $this->beforeEach(function () use (&$model) {
        $model = new class(['status' => 0]) {
            use Model;

            #[Options(Status::class)]
            public Bitflag $status;
        };
    });

    /** On a type hinted model, a property is turned into a bitflag */
    yield function () use (&$model) {
        assert($model->status instanceof Bitflag);
        $model->status->code = true;
        $model->status->cats = true;
        assert("{$model->status}" === "6");
    };

    /** After changing some flags, they are correctly persisted and again after re-changing */
    yield function () use (&$model) {
        $model->status->cats = true;
        $model->status->nice = true;
        assert("{$model->status}" === "3");
        $model->status->nice = false;
        assert("{$model->status}" === "2");
    };

    /** Using an illegal option throws an exception */
    yield function () use (&$model) {
        $e = null;
        try {
            $model->status->foo = true;
        } catch (FlagNotDefinedException $e) {
        }
        assert($e instanceof FlagNotDefinedException);
    };

    /** The model can be serialized after which it is a stdClass that contains the correct settings */
    yield function () use (&$model) {
        $model->status->cats = true;
        $model->status->nice = true;
        $exported = $model->status->jsonSerialize();
        assert($exported instanceof stdClass);
        assert($exported->code == false);
        assert($exported->cats == true);
        assert($exported->nice == true);
    };

    /** Using a non-backed enum for options throws an exception */
    yield function () {
        enum NonBacked
        {
            case whatever;
        }

        $e = null;
        try {
            $model = new class(['status' => 0]) {
                use Model;

                #[Options(NonBacked::class)]
                public Bitflag $status;
            };
        } catch (OptionsInvalidException $e) {
        }
        assert($e instanceof OptionsInvalidException);
    };

    /** An enum with the wrong backing type throws an exception */
    yield function () {
        enum WrongBackingType : string
        {
            case whatever = 'talk to the hand';
        }

        $e = null;
        try {
            $model = new class(['status' => 0]) {
                use Model;

                #[Options(WrongBackingType::class)]
                public Bitflag $status;
            };
        } catch (OptionsInvalidException $e) {
        }
        assert($e instanceof OptionsInvalidException);
    };
};

