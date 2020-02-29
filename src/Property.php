<?php

namespace Ornament\Bitflag;

use JsonSerializable;
use ArrayObject;
use stdClass;
use Ornament\Core\Decorator;
use ReflectionClass;
use ReflectionProperty;

/**
 * Object to emulate a bitflag in Ornament models.
 *
 * For a model Foo with a property 'status', we often want to define a number of
 * bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag trait
 * makes this easy.
 *
 * Annotate the bitflag property with @var Ornament\Bitflag\Property OR (as of
 * PHP 7.4) type hint it as such.
 *
 * The supported flags should be defined as `protected const OPTIONS = [MAP] on
 * the implementing class. This should extend this abstract base class. These
 * are now automagically exposed as `$property->name_of_property [true/false]`.
 *
 * <code>
 * use Ornament\Core;
 * use Ornament\Bitflag\Property;
 *
 * class Status extends Property
 * {
 *     protected const OPTIONS = ['on' => 1, 'initialized' => 2];
 * }
 *
 * class Model
 * {
 *     use Core\Model;
 *
 *     public Status $status;
 * }
 *
 * $model = new Model(['status' => 3]);
 *
 * // Now this works, assuming `$model` is the instance:
 * var_dump($model->status->on); // true in this example, since 3 & 1 = 1
 * $model->status->on = true; // bit 1 is now on (status |= 1)
 * $model->status->on = false; // bit 1 is now off (status &= ~1)
 * var_dump($model->status->initialized); // true, since 2 & 2 = 2
 * </code>
 */
abstract class Property extends Decorator implements JsonSerializable
{
    /** @var int [] */
    protected const OPTIONS = [];

    /**
     * Magic setter. Silently fails if the specified property was not available
     * in the $valueMap used during construction.
     *
     * @param string $prop Name of the bit to set.
     * @param bool $value True to turn on, false to turn off.
     * @throws Ornament\Bitflag\FlagNotDefinedException
     */
    public function __set(string $prop, bool $value)
    {
        $modifier = 0;
        if (!isset(static::OPTIONS[$prop])) {
            throw new FlagNotDefinedException($prop);
        }
        $this->_source = (int)"$this";
        if ($value) {
            $this->_source |= static::OPTIONS[$prop];
        } else {
            $this->_source &= ~static::OPTIONS[$prop];
        }
    }

    /**
     * Magic getter to retrieve the status of a bit.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is on, false if off.
     * @throws Ornament\Bitflag\FlagNotDefinedException
     */
    public function __get(string $prop) :? bool
    {
        if (!isset(static::OPTIONS[$prop])) {
            throw new FlagNotDefinedException($prop);
        }
        return (bool)((int)"$this" & static::OPTIONS[$prop]);
    }

    /**
     * Check if a bit exists in this bitflag.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is known in this bitflag, false otherwise.
     */
    public function __isset(string $prop) : bool
    {
        return isset(static::OPTIONS[$prop]);
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
        foreach (static::OPTIONS as $name => $bit) {
            $ret->$name = (bool)($this->_source & $bit);
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
        foreach (static::OPTIONS as $name => $bit) {
            if ($this->_source & $bit) {
                $ret[$name] = $bit;
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
    public function getBit(string $flag) :? int
    {
        return $this->getArrayCopy()[$flag] ?? null;
    }
}

