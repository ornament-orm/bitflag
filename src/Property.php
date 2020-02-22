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
 * The supported flags should be defined as `protected static [int]` properties
 * on the implementing class. This should extend this abstract base class. These
 * are now automagically exposed as `$property->name_of_property [true/false]`.
 *
 * <code>
 * use Ornament\Core;
 * use Ornament\Bitflag\Property;
 *
 * class Status extends Property
 * {
 *     protected int $on = 1;
 *
 *     protected int $initialized = 2;
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
    /** @var int */
    protected $_source = 0;

    /** @var ReflectionProperty[] */
    private $_properties;

    /**
     * Constructor.
     *
     * @paran int $source
     * @return void
     */
    public function __construct(int $source)
    {
        parent::__construct($source);
        $this->_properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_STATIC & ReflectionProperty::IS_PROTECTED);
    }

    /**
     * Magic setter. Silently fails if the specified property was not available
     * in the $valueMap used during construction.
     *
     * @param string $prop Name of the bit to set.
     * @param bool $value True to turn on, false to turn off.
     */
    public function __set(string $prop, bool $value)
    {
        $modifier = 0;
        foreach ($this->_properties as $property) {
            if ($property->getName() == $prop) {
                $modifier = $property->getValue();
                break;
            }
        }
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
     * @return bool|null True if the bit is on, false if off or null if unknown.
     */
    public function __get(string $prop) :? bool
    {
        $modifier = 0;
        foreach ($this->_properties as $property) {
            if ($property->getName() == $prop) {
                $modifier = $property->getValue();
                break;
            }
        }
        if (!$modifier) {
            return null;
        }
        return (bool)((int)"$this" & $modifier);
    }

    /**
     * Check if a bit exists in this bitflag.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is known in this bitflag, false otherwise.
     */
    public function __isset(string $prop) : bool
    {
        foreach ($this->_properties as $property) {
            if ($property->getName() == $prop) {
                return true;
            }
        }
        return false;
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
        foreach ($this->_properties as $property) {
            $key = $property->getName();
            $value = $property->getValue();
            $ret->$key = (bool)((int)"$this" & $value);
        }
        return $ret;
    }

    /**
     * Like jsonSerialize, only returns a filtered array of names/values where
     * the bit resolved to true.
     *
     * @return array
     */
    public function getArrayCopy() : array
    {
        $ret = [];
        $source = (int)"$this";
        foreach ($this->_properties as $property) {
            $key = $property->getName();
            $value = $property->getValue();
            if ($source & $value) {
                $ret[$key] = $value;
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
}

