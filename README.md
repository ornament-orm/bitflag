# ornament/bitflag
Bitflag decorator for Ornament ORM

For a model Foo with a property 'status', we often want to define a number of
bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag decorator
makes this easy.

Create a backed enum defining the desired flags. Type hint the property to
receive the `Bitflag` as such, and add the attribute `Ornament\Bitflag\Options`
to said property. The argument to Options is the classname of your enum.

All defined cases of the enum are now magically available for getting and
setting as properties on bitflag property:

```php
<?php

```
```php
<?php

use Ornament\Core;
use Ornament\Bitflag\{ Bitflag, Options };

enum Status : int
{
    case on = 1;
    case initialized = 2;
}

class Model
{
    use Core\Model;

    #[Options(Status::class)]
    public Bitflag $status;
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
You don't need to; simply use the backed enum:

```
<?php

echo Status::on->value; // 1
```

Similarly, use `Status::cases()` if you need all possible values.

