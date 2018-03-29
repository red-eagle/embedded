<?php

namespace yii2tech\tests\unit\embedded\data;

use yii\base\Model;
use yii2tech\embedded\NestedTrait;

class Nested extends Model
{
    use NestedTrait;

    public $name;
    public $name1;
    public $name2;

}