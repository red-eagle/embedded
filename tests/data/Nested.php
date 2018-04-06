<?php

namespace yii2tech\tests\unit\embedded\data;

use yii\base\Model;
use yii2tech\embedded\NestedInterface;
use yii2tech\embedded\NestedListInterface;
use yii2tech\embedded\NestedTrait;

class Nested extends Model implements NestedInterface, NestedListInterface
{
    use NestedTrait;

    public $name;
    public $name1;
    public $name2;

}