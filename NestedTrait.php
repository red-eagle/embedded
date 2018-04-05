<?php
/**
 * Created
 * User: red
 * Date: 29.03.18
 * Time: 16:28
 */

namespace yii2tech\embedded;

use yii\base\BaseObject;
use yii\base\Model;

/**
 * Trait NestedTrait
 * @package yii2tech\embedded
 * @mixin BaseObject
 */
trait NestedTrait
{
    /** @var BaseObject|ContainerTrait */
    public $owner;

    public $ownerAttribute;

    public $index;

    /**
     * @return Model
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function getOwnerAttribute()
    {
        return $this->ownerAttribute;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function formName($withIndex = false)
    {
        if (empty($this->owner)) {
            return parent::formName();
        }

        $mapping = $this->owner->getEmbeddedMapping($this->ownerAttribute);
        $formName = $this->owner->formName() . "[{$this->ownerAttribute}]";

        if ($mapping->multiple && $withIndex) {
            return "{$formName}[{$this->index}]";
        } else {
            return $formName;
        }
    }

    public function getIsNewRecord() {
        if (!empty($this->owner)) {
            return $this->owner->isNewRecord;
        }

        return true;
    }
}