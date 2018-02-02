<?php

namespace Ornament\Bitflag;

use JsonSerializable;
use ArrayObject;
use stdClass;
use Ornament\Core\Decorator;
use ArrayObject;

/**
 * Object to emulate a bitflag in Ornament models.
 *
 * For a model Foo with a property 'status', we often want to define a number of
 * bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag trait
 * makes this easy.
 *
 * Annotate the bitflag property with @var Ornament\Bitflag\Property constructed
 * with a map of names/flags, e.g. @construct on = 1, valid = 2
 *
 * <code>
 * // Now this works, assuming `$model` is the instance:
 * var_dump($model->status->on); // true or false, depending
 * $model->status->on = true; // bit 1 is now on (status |= 1)
 * $model->status->on = false; // bit 1 is now off (status &= ~1)
 * </code>
 */
class Property extends Decorator implements JsonSerializable
{
    /**
     * @var array
     * Private storage of the name/bitvalue map for this object.
     */
    private $map;

    /**
     * Constructor. Normally called by models based on the @Bitflag annotation,
     * but you can also construct manually.
     *
     * @param int $source The initial value of the byte storing the bitflags.
     * @param array $valueMap Key/value pair of bit names/values, e.g. "on" =>
     *  1, "female" => 2 etc.
     */
    public function __construct(stdClass $model, string $property, array $valueMap = [])
    {
        if (is_array($model->$property) || $model->$property instanceof ArrayObject) {
            $bit = 0;
            foreach ($model->$property as $flag) {
                if (isset($valueMap[$flag])) {
                    $bit |= $valueMap[$flag];
                }
            }
            $model->$property = $bit;
        }
        parent::__construct($model, $property);
        $this->map = $valueMap;
    }

    public function append($value)
    {
        $this->__set($value, true);
    }

    public function offsetExists($index)
    {
        return $this->__isset($index);
    }

    public function offsetSet($index, $value)
    {
        $this->__set($index, true);
    }

    public function offsetGet($index)
    {
        return $this->__get($index);
    }

    public function offsetUnset($index)
    {
        $this->__set($index, false);
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
        if (!isset($this->map[$prop])) {
            return;
        }
        if ($value) {
            $this->source |= $this->map[$prop];
        } else {
            $this->source &= ~$this->map[$prop];
        }
    }

    /**
     * Magic getter to retrieve the status of a bit.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is on, false if off or unknown.
     */
    public function __get(string $prop) : bool
    {
        if (!isset($this->map[$prop])) {
            return false;
        }
        return (bool)($this->source & $this->map[$prop]);
    }

    /**
     * Check if a bit exists in this bitflag.
     *
     * @param string $prop Name of the bit to check.
     * @return bool True if the bit is known in this bitflag, false otherwise.
     */
    public function __isset(string $prop) : bool
    {
        return isset($this->map[$prop]);
    }

    /**
     * Return the original source byte as a string.
     *
     * @return string Integer casted to string containing the current value.
     */
    public function __toString() : string
    {
        if ($this->source instanceof ArrayObject || is_array($this->source)) {
            $src = $this->source;
            $this->source = 0;
            foreach ($src as $map) {
                $this->source |= $this->map[$map];
            }
        }
        return (string)$this->source;
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
        foreach ($this->map as $key => $value) {
            $ret->$key = (bool)($this->source & $value);
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
        $this->source = 0;
    }
}

