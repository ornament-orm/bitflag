<?php

namespace Ornament\Bitflag;

use JsonSerializable;
use ArrayObject;
use stdClass;
use Ornament\Core\Decorator;
use ReflectionClass;
use ReflectionProperty;
use ReflectionEnum;

/**
 * Object to emulate a bitflag in Ornament models.
 *
 * For a model Foo with a property 'status', we often want to define a number of
 * bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag decorator
 * makes this easy.
 *
 * Type hint the bitflag property with `Ornament\Bitflag\Bitflag`. Also, add an
 * attribute of type `Ornament\Bitflag\Options` to the property. Its argument is
 * the class name of the enum to be used specifying what the various bit mean.
 *
 * The cases of the enum are now automatically exposed as properties on the
 * bitflag, with a value of either true (set) or false (unset).
 *
 * <code>
 * use Ornament\Core;
 * use Ornament\Bitflag\{ Property, Options };
 *
 * enum Status : int
 * {
 *     case on = 1;
 *     case initialized = 2;
 * }
 *
 * class Model
 * {
 *     use Core\Model;
 *
 *     #[Options(Status::class)]
 *     public Property $status;
 * }
 *
 * $model = Model::fromIterable(['status' => 3]);
 *
 * // Now this works, assuming `$model` is the instance:
 * var_dump($model->status->on); // true in this example, since 3 & 1 = 1
 * $model->status->on = true; // bit 1 is now on (status |= 1)
 * $model->status->on = false; // bit 1 is now off (status &= ~1)
 * var_dump($model->status->initialized); // true, since 2 & 2 = 2
 * </code>
 */
class Bitflag extends Decorator implements JsonSerializable
{
    protected array $_options;

    public function __construct(protected mixed $_source, protected ReflectionProperty $_target)
    {
        settype($this->_source, 'int');
        $_options = $_target->getAttributes(Options::class);
        if (!$_options) {
            throw new OptionsNotDefinedException($_target->class, $_target->name);
        }
        $_options = $_options[0]->newInstance();
        $enum = $_options->getEnum();
        $reflection = new ReflectionEnum($enum);
        if (!$reflection->isBacked() || $reflection->getBackingType()->__toString() !== 'int') {
            throw new OptionsInvalidException($enum);
        }
        $this->_options = $enum::cases();
    }

    /**
     * Cfg. Ornament models, allow instantiation from an iterable containing all
     * flags that should be set to `true`.
     *
     * @param iterable $iterable
     * @return self
     */
    public static function fromIterable(iterable $iterable) : self
    {
        $class = get_called_class();
        $property = new $class(0);
        foreach ($iterable as $flag) {
            $property->$flag = true;
        }
        return $property;
    }

    /**
     * Magic setter.
     *
     * @param string $prop Name of the bit to set.
     * @param bool $value True to turn on, false to turn off.
     * @throws Ornament\Bitflag\FlagNotDefinedException
     */
    public function __set(string $prop, bool $value)
    {
        $modifier = $this->getModifier($prop);
        $this->_source = (int)"$this";
        if ($value) {
            $this->_source |= $modifier;
        } else {
            $this->_source &= ~$modifier;
        }
    }

    /**
     * Magic getter to retrieve the status of a bit.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is on, false if off.
     * @throws Ornament\Bitflag\FlagNotDefinedException
     */
    public function __get(string $prop) : bool
    {
        $modifier = $this->getModifier($prop);
        return ($this->_source & $modifier) === $modifier;
    }

    /**
     * Check if a bit exists in this bitflag.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is known in this bitflag, false otherwise.
     */
    public function __isset(string $prop) : bool
    {
        try {
            $this->getModifier($prop);
            return true;
        } catch (FlagNotDefinedException $e) {
            return false;
        }
    }

    /**
     * Export this bitflag as a Json object. All known bits are exported as
     * properties with true or false depending on their status.
     *
     * @return stdClass A standard class suitable for json_encode.
     */
    public function jsonSerialize() : stdClass
    {
        $ret = new stdClass;
        foreach ($this->_options as $enum) {
            $ret->{$enum->name} = (bool)($this->_source & $enum->value);
        }
        return $ret;
    }

    /**
     * Like jsonSerialize, only returns a filtered array of names/values where
     * the bit resolved to true.
     *
     * @return int[]
     */
    public function getArrayCopy() : array
    {
        $ret = [];
        foreach ($this->_options as $enum) {
            if ($this->_source & $enum->value) {
                $ret[$enum->name] = $enum->value;
            }
        }
        return $ret;
    }

    /**
     * Set all flags to "off". Useful for reinitialization.
     *
     * @return void
     */
    public function allOff()
    {
        $this->_source = 0;
    }

    /**
     * Gets the bit associated with a particular flag, if defined.
     *
     * @param string $flag
     * @return int|null
     */
    protected function getModifier(string $prop) : int
    {
        foreach ($this->_options as $enum) {
            if ($enum->name === $prop) {
                return $enum->value;
            }
        }
        throw new FlagNotDefinedException($this->_target->class, $this->_target->name, $prop);
    }
}

