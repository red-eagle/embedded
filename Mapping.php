<?php
/**
 * @link      https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license   [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\embedded;

use ArrayObject;
use yii\base\InvalidArgumentException;
use yii\base\BaseObject;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Represents mapping between embedded object or object list and its container.
 * It stores declaration of embedded policy and handles embedded value composition and extraction.
 *
 * @see    ContainerTrait
 *
 * @property bool $isValueInitialized whether embedded value has been already initialized or not.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since  1.0
 */
class Mapping extends BaseObject
{
    /**
     * @var string name of the container source field or property.
     */
    public $source;
    /**
     * @var string|array target class name or array object configuration.
     */
    public $target;
    /**
     * @var bool whether list of objects should match the source value.
     */
    public $multiple;
    /**
     * @var bool whether to create empty object or list of objects, if the source field is null.
     * If disabled [[getValue()]] will produce `null` value from null source.
     */
    public $createFromNull = true;
    /**
     * @var bool whether to set `null` for the owner [[source]] field, after the embedded value created.
     * While enabled this saves memory usage, but also makes it impossible to use embedded and raw value at the same time.
     */
    public $unsetSource = true;

    /**
     * @var mixed actual embedded value.
     */
    private $_value = false;


    /**
     * Sets the embedded value.
     * @param array|object|null $value actual value.
     * @throws InvalidArgumentException on invalid argument
     */
    public function setValue($value)
    {
        $targetConfig = ['class' => $this->target];
        if (!is_null($value)) {
            if ($this->multiple) {
                if (is_array($value)) {
                    $items = new ArrayObject();
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            $item = Yii::createObject(
                                array_merge(
                                    $targetConfig,
                                    ['index' => $k]
                                )
                            );

                            if ($item instanceof Model) {
                                $item->load($v, '');
                            } else {
                                $item = Yii::configure($item, $v);
                            }
                        } else {
                            $item = $v;
                        }

                        $items[$k] = $item;
                    }
                    $value = $items;
                } elseif (!($value instanceof \ArrayAccess)) {
                    throw new InvalidArgumentException("Value should either an array or a null, '" . gettype($value) . "' given.");
                }
            } else {
                if (is_array($value)) {
                    $item = Yii::createObject($targetConfig);
                    if ($item instanceof Model) {
                        $item->load($value, '');
                    } else {
                        $item = Yii::configure($item, $value);
                    }
                    $value = $item;
                }
                if (!is_object($value)) {
                    throw new InvalidArgumentException("Value should either an object or a null, '" . gettype($value) . "' given.");
                }
            }
        }

        $this->_value = $value;
    }

    /**
     * Returns actual embedded value.
     * @param object|ContainerTrait $owner owner object.
     * @param string|null $name            attribute name in owner object
     * @return object|object[]|null embedded value.
     */
    public function getValue($owner, $name = null)
    {
        $value = $this->_value;

        if ($value === false) {
            $value = $this->createValue($owner, $name);
        }

        if ($this->multiple && !empty($value)) {
            foreach ($value as $key => $item) {
                if ($item instanceof NestedInterface && ($item->getOwner() !== $owner || empty($item->getOwnerAttribute()))) {
                    $item->setOwner($owner);
                    $item->setOwnerAttribute($name);
                }
                if ($item instanceof NestedListInterface && $item->getIndex() !== $key) {
                    $item->setIndex($key);
                }
                $value[$key] = $item;
            }
        } elseif ($value instanceof NestedInterface) {
            if ($value->getOwner() !== $owner) {
                $value->setOwner($owner);
            }
            if ($value->getOwnerAttribute() !== $name) {
                $value->setOwnerAttribute($name);
            }
        }

        $this->_value = $value;

        return $value;
    }


    /**
     * @return bool whether embedded value has been already initialized or not.
     * @since 1.0.1
     */
    public function getIsValueInitialized()
    {
        return $this->_value !== false;
    }

    /**
     * @param object|ContainerTrait $owner owner object
     * @throws InvalidArgumentException on invalid source.
     * @return array|null|object value.
     */
    private function createValue($owner, $name = null)
    {
        if (is_array($this->target)) {
            $targetConfig = $this->target;
        } else {
            $targetConfig = ['class' => $this->target];
        }

        $sourceValue = $owner->{$this->source};
        if ($this->createFromNull && $sourceValue === null) {
            $sourceValue = [];
        }
        if ($sourceValue === null) {
            return null;
        }

        if ($this->multiple) {
            $result = new ArrayObject();
            foreach ($sourceValue as $key => $frame) {
                if (!is_array($frame)) {
                    throw new InvalidArgumentException("Source value for the embedded should be an array.");
                }
                $currentConfig = $targetConfig;
                $result[$key] = Yii::createObject(array_merge($currentConfig, $frame));
            }
        } else {
            if (!is_array($sourceValue)) {
                throw new InvalidArgumentException("Source value for the embedded should be an array.");
            }
            $result = Yii::createObject(array_merge($targetConfig, $sourceValue));
        }

        if ($this->unsetSource) {
            $owner->{$this->source} = null;
        }

        return $result;
    }

    /**
     * Extract embedded object(s) values as array.
     * @param object $owner owner object
     * @return array|null extracted values.
     */
    public function extractValues($owner)
    {
        $embeddedValue = $this->getValue($owner);
        if ($embeddedValue === null) {
            $value = null;
        } else {
            if ($this->multiple) {
                $value = [];
                foreach ($embeddedValue as $key => $object) {
                    $value[$key] = $this->extractObjectValues($object);
                }
            } else {
                $value = $this->extractObjectValues($embeddedValue);
            }
        }
        return $value;
    }

    /**
     * @param object $object
     * @return array
     */
    private function extractObjectValues($object)
    {
        $values = ArrayHelper::toArray($object, [], false);
        unset($values['owner']);
        unset($values['ownerAttribute']);
        unset($values['index']);
        if ($object instanceof ContainerInterface) {
            $values = array_merge($values, $object->getEmbeddedValues());
        }
        return $values;
    }
}