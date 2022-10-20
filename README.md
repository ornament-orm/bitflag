# ornament/bitflag
Bitflag decorator for Ornament ORM

For a model Foo with a property 'status', we often want to define a number of
bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag decorator
makes this easy.

Create a class extending `Ornament\Bitflag\Property` defining the desired flags.
Annotate the bitflag property with `@var Your\Implementing\Class` OR (as of
PHP 7.4) type hint it as such.

To define allowed bitflags and their aliases, the `protected` const OPTIONS is
used. It should contain a hash of name/bit pairs.

All defined flags are now magically available for getting and setting as
properties on the class instance:

```php
<?php

```
```php
<?php

use Ornament\Core;
use Ornament\Bitflag\Property;

class Status extends Property
{
    protected const OPTIONS = ['on' => 1, 'initialized' => 2];
}

class Model
{
    use Core\Model;

    public Status $status;
}

$model = Model::fromIterable(['status' => 3]);

// Now this works, assuming `$model` is the instance:
var_dump($model->status->on); // true in this example, since 3 & 1 = 1
$model->status->on = true; // bit 1 is now on (status |= 1)
$model->status->on = false; // bit 1 is now off (status &= ~1)
var_dump($model->status->initialized); // true, since 2 & 2 = 2
```

Bitflag properties also support JSON serialization (via
`Ornament\Bitflag\Property::jsonSerialize()`). A map of `true`/`false` values
will be exported. Similarly, the `getArrayCopy` method will return a hash of
flags/bits where the bit was set to true.

## Accessing the underlying bit value
One may use the `getBit($property)` method to access the underlying bit value of
a property. In the above example, `$model->status->getBit('on')` would yield
`1`.

An alternative strategy would be to define all bitflags as `public const`ants on
the bitflag property class, and reference those in the `OPTIONS` definition:

```php
<?php

class Status extends Ornament\Bitflag\Property
{
    public const ON = 1;

    public const INITIALIZED = 2;

    protected const OPTIONS = [
        'on' => Status::ON,
        'initialized' => Status::INITIALIZED,
    ];
}

```

