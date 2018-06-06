<?php
/**
 * @link      https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license   [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\embedded;

use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\base\Model;
use yii\base\InvalidArgumentException;

/**
 * ContainerTrait can be used to satisfy [[ContainerInterface]].
 *
 * For each embedded entity a mapping declaration should be provided.
 * In order to do so you need to declare method, which name is prefixed with 'embedded', which
 * should return the [[Mapping]] instance. You may use [[hasEmbedded()]] and [[hasEmbeddedList()]] for this.
 *
 * Per each of source field or property a new virtual property will declared, which name will be composed
 * by removing 'embedded' prefix from the declaration method name.
 *
 * Note: watch for the naming collisions: if you have a source property named 'profile' the mapping declaration
 * for it should have different name, like 'profileModel'.
 *
 * Example:
 *
 * ```php
 * use yii\base\Model;
 * use yii2tech\embedded\ContainerInterface;
 * use yii2tech\embedded\ContainerTrait;
 *
 * class User extends Model implements ContainerInterface
 * {
 *     use ContainerTrait;
 *
 *     public $profileData = [];
 *     public $commentsData = [];
 *
 *     public function embedProfile()
 *     {
 *         return $this->mapEmbedded('profileData', Profile::className());
 *     }
 *
 *     public function embedComments()
 *     {
 *         return $this->mapEmbeddedList('commentsData', Comment::className());
 *     }
 * }
 *
 * $user = new User();
 * $user->profile->firstName = 'John';
 * $user->profile->lastName = 'Doe';
 *
 * $comment = new Comment();
 * $user->comments[] = $comment;
 * ```
 *
 * In order to synchronize values between embedded entities and container use [[refreshFromEmbedded()]] method.
 *
 * @see    ContainerInterface
 * @see    Mapping
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since  1.0
 * @mixin Model
 * @mixin \yii2tech\embedded\mongodb\ActiveRecord
 */
trait ContainerTrait
{
    /**
     * @var Mapping[]
     */
    private $_embedded = [];

    private $_attributesEmbed = null;

    /**
     * PHP getter magic method.
     * This method is overridden so that embedded objects can be accessed like properties.
     *
     * @param string $name property name
     * @throws \yii\base\InvalidParamException if relation name is wrong
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {
        if ($this->hasEmbedded($name)) {
            return $this->getEmbedded($name);
        }
        return parent::__get($name);
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that embedded objects can be accessed like properties.
     * @param string $name property name
     * @param mixed $value property value
     */
    public function __set($name, $value)
    {
        if ($this->hasEmbedded($name)) {
            $this->setEmbedded($name, $value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the embedded object is null or not.
     * @param string $name the property name or the event name
     * @return bool whether the property value is null
     */
    public function __isset($name)
    {
        if (isset($this->_embedded[$name])) {
            return ($this->_embedded[$name]->getValue($this, $name) !== null);
        }
        return parent::__isset($name);
    }

    /**
     * Sets a component property to be null.
     * This method overrides the parent implementation by clearing
     * the specified embedded object.
     * @param string $name the property name or the event name
     */
    public function __unset($name)
    {
        if (isset($this->_embedded[$name])) {
            ($this->_embedded[$name]->setValue(null));
        } else {
            parent::__unset($name);
        }
    }

    /**
     * Sets embedded object or list of objects.
     * @param string $name                embedded name
     * @param object|object[]|null $value embedded value.
     */
    public function setEmbedded($name, $value)
    {
        $this->getEmbeddedMapping($name)->setValue($value);
    }

    /**
     * Returns embedded object or list of objects.
     * @param string $name embedded name.
     * @return object|object[]|null embedded value.
     */
    public function getEmbedded($name)
    {
        return $this->getEmbeddedMapping($name)->getValue($this, $name);
    }

    /**
     * Returns mapping information about specified embedded entity.
     * @param string $name embedded name.
     * @throws \yii\base\InvalidParamException if specified embedded does not exists.
     * @throws \yii\base\InvalidConfigException on invalid mapping declaration.
     * @return Mapping embedded mapping.
     */
    public function getEmbeddedMapping($name)
    {
        if (!isset($this->_embedded[$name])) {
            $method = $this->composeEmbeddedDeclarationMethodName($name);
            if (method_exists($this, $method)) {
                $mapping = call_user_func([$this, $method]);
                if (!$mapping instanceof Mapping) {
                    throw new InvalidArgumentException("Mapping declaration '" . get_class($this) . "::{$method}()' should return instance of '" . Mapping::class . "'");
                }
            } elseif (array_key_exists($name, $this->attributesEmbedMap())) {
                $mapping = Yii::createObject(
                    array_merge(
                        [
                            'class' => Mapping::class,
                            'multiple' => false
                        ],
                        $this->attributesEmbedMap()[$name]
                    )
                );
            } else {
                throw new InvalidArgumentException("'" . get_class($this) . "' has no declaration ('{$method}()') or map config in attributesEmbedMap() for the embedded '{$name}'");

            }
            $this->_embedded[$name] = $mapping;
        }
        return $this->_embedded[$name];
    }

    /**
     * Checks if asked embedded declaration exists.
     * @param string $name embedded name
     * @return bool whether embedded declaration exists.
     */
    public function hasEmbedded($name)
    {
        return (isset($this->_embedded[$name])) || method_exists($this, $this->composeEmbeddedDeclarationMethodName($name)) || array_key_exists($name, $this->attributesEmbedMap());
    }

    /**
     * Declares embedded object.
     * @param string $source       source field name
     * @param string|array $target target class or array configuration.
     * @param array $config        mapping extra configuration.
     * @return Mapping|object embedding mapping instance.
     */
    public function mapEmbedded($source, $target, array $config = [])
    {
        return Yii::createObject(array_merge(
            [
                'class' => Mapping::class,
                'source' => $source,
                'target' => $target,
                'multiple' => false,
            ],
            $config
        ));
    }

    /**
     * Declares embedded list of objects.
     * @param string $source       source field name
     * @param string|array $target target class or array configuration.
     * @param array $config        mapping extra configuration.
     * @return Mapping|object embedding mapping instance.
     */
    public function mapEmbeddedList($source, $target, array $config = [])
    {
        return Yii::createObject(array_merge(
            [
                'class' => Mapping::class,
                'source' => $source,
                'target' => $target,
                'multiple' => true,
            ],
            $config
        ));
    }

    /**
     * @param string $name embedded name.
     * @return string declaration method name.
     */
    private function composeEmbeddedDeclarationMethodName($name)
    {
        return 'embed' . $name;
    }

    /**
     * Returns list of values from embedded objects named by source fields.
     * @return array embedded values.
     */
    public function getEmbeddedValues()
    {
        $values = [];
        foreach ($this->_embedded as $key => $embedded) {
            if (!$embedded->getIsValueInitialized()) {
                continue;
            }
            $values[$embedded->source] = $embedded->extractValues($this);
        }
        return $values;
    }

    /**
     * Fills up own fields by values fetched from embedded objects.
     */
    public function refreshFromEmbedded()
    {
        foreach ($this->getEmbeddedValues() as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * @param $attribute
     * @return string
     */
    public function generateAttributeLabel($attribute)
    {
        if (preg_match('/(?<attribute>[a-zA-Z0-9]+)((\[(?<subAttribute>[a-zA-Z0-9]+)\])(?<subSubAttributes>.+)?)?/', $attribute, $match)) {
            if (array_key_exists('subAttribute', $match) && $this->hasEmbedded($match['attribute'])) {
                /** @var Model $embedModel */
                $embedModel = $this->{$match['attribute']};
                return $this->getAttributeLabel($match['attribute'])
                    . ' '
                    . $embedModel->getAttributeLabel($match['subAttribute'] . (array_key_exists('subSubAttributes', $match) ? $match['subSubAttributes'] : ''));
            }
        }
        return parent::generateAttributeLabel($attribute);

    }

    /**
     * Array for configuring Mapping object
     *
     * 'attributeName': [
     *      'source': string, source attribute name
     *      'target': string, target class,
     *      'multiple': boolean,
     *      'createFromNull': boolean,
     *      'unsetSource': boolean,
     * ]
     *
     * @return array
     */
    public function attributesEmbedMap()
    {
        return [];
    }

    public function attributesEmbed()
    {
        if (is_null($this->_attributesEmbed)) {
            $this->_attributesEmbed = array_keys($this->attributesEmbedMap());
            $reflection = new \ReflectionClass($this);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (strpos($method->getName(), 'embed') === 0) {
                    $this->_attributesEmbed[] = lcfirst(substr($method->getName(), 5));
                }
            }
        }

        return $this->_attributesEmbed;
    }

    /**
     * @param string $name
     * @param Event|null $event
     */
    public function trigger($name, Event $event = null)
    {
        foreach ($this->attributesEmbed() as $attribute) {
            $embeddedValue = $this->{$attribute};
            if ($embeddedValue instanceof \ArrayAccess && !($embeddedValue instanceof Component)) {
                foreach ($embeddedValue as $item) {
                    self::triggerEventForItem($item, $name, $event);
                }
            } else {
                self::triggerEventForItem($embeddedValue, $name, $event);
            }
        }

        parent::trigger($name, $event);
    }

    private static function triggerEventForItem($item, $name, $event)
    {
        if (is_object($item) && $item instanceof Component) {
            $item->trigger($name, $event);
        }
    }
}