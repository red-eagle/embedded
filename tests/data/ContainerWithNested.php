<?php

namespace yii2tech\tests\unit\embedded\data;

use yii\base\BaseObject;
use yii\base\Model;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;
use yii2tech\embedded\NestedInterface;
use yii2tech\embedded\NestedListInterface;
use yii2tech\embedded\NestedTrait;

/**
 * @property Nested $nestedModel
 * @property Nested[] $nestedList
 * @property Nested $nestedMappedModel
 * @property Nested[] $nestedMappedList
 * @property ContainerWithNested $self
 * @property ContainerWithNested[] $selfList
 * @property \stdClass[] $null
 * @property \stdClass[] $nullAutoCreate
 * @property \stdClass[]|null $nullList
 */
class ContainerWithNested extends Model implements ContainerInterface, NestedInterface, NestedListInterface
{
    use ContainerTrait, NestedTrait;

    public $nestedData = [];
    public $nestedMappedData = [];
    public $nestedListData = [];
    public $nestedMappedListData = [];
    public $nestedSelfData = [];
    public $nestedSelfListData = [];

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

    public function embedSelfList()
    {
        return $this->mapEmbeddedList('nestedSelfData', __CLASS__);
    }

    public function attributesEmbedMap()
    {
        return [
            'nestedMappedModel' => [
                'source' => 'nestedMappedData',
                'target' => Nested::class
            ],
            'nestedMappedList' => [
                'source' => 'nestedMappedListData',
                'target' => Nested::class,
                'multiple' => true
            ],
        ];
    }

}