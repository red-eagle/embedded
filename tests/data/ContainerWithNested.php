<?php

namespace yii2tech\tests\unit\embedded\data;

use yii\base\BaseObject;
use yii\base\Model;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;
use yii2tech\embedded\NestedTrait;

/**
 * @property Nested $nestedModel
 * @property Nested[] $nestedList
 * @property ContainerWithNested $self
 * @property \stdClass[] $null
 * @property \stdClass[] $nullAutoCreate
 * @property \stdClass[]|null $nullList
 */
class ContainerWithNested extends Model implements ContainerInterface
{
    use ContainerTrait, NestedTrait;

    public $nestedData = [];
    public $nestedListData = [];
    public $nestedSelfData = [];

    public function embedNestedModel()
    {
        return $this->mapEmbedded('nestedData', Nested::class);
    }

    public function embedNestedList()
    {
        return $this->mapEmbeddedList('nestedListData', Nested::class);
    }

    public function embedSelf()
    {
        return $this->mapEmbedded('nestedSelfData', __CLASS__);
    }
}