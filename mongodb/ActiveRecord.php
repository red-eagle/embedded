<?php
/**
 * @link      https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license   [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\embedded\mongodb;

use yii\base\Component;
use yii\base\Event;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

/**
 * ActiveRecord is an enhanced version of [[\yii\mongodb\ActiveRecord]], which includes 'embedded' functionality.
 *
 * Obviously, this class requires [yiisoft/yii2-mongodb](https://github.com/yiisoft/yii2-mongodb) extension installed.
 *
 * @see    \yii\mongodb\ActiveRecord
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since  1.0
 */
class ActiveRecord extends \yii\mongodb\ActiveRecord implements ContainerInterface
{
    use ContainerTrait;

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->refreshFromEmbedded();
        return true;
    }

    /**
     * @param string $name
     * @param Event|null $event
     */
    public function trigger($name, Event $event = null)
    {
        foreach ($this->attributesEmbed() as $attribute) {
            $embeddedValue = $this->{$attribute};
            if (is_iterable($embeddedValue)) {
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