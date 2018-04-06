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

    /** @var string */
    public $ownerAttribute;

    /** @var string|integer */
    public $index;

    public function formName($withIndex = true)
    {
        if (empty($this->owner)) {
            return parent::formName();
        }

        $mapping = $this->owner->getEmbeddedMapping($this->ownerAttribute);

        if ($this->owner instanceof NestedListInterface) {
            $formName = $this->owner->formName(true) . "[{$this->ownerAttribute}]";
        } else {
            $formName = $this->owner->formName() . "[{$this->ownerAttribute}]";
        }

        if ($this instanceof NestedListInterface && $mapping->multiple && $withIndex) {
            return "{$formName}[{$this->getIndex()}]";
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

    /**
     * @param ContainerTrait|BaseObject $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param mixed $ownerAttribute
     */
    public function setOwnerAttribute($ownerAttribute)
    {
        $this->ownerAttribute = $ownerAttribute;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setIndex($index) {
        $this->index = $index;
    }
}