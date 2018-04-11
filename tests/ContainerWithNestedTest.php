<?php

namespace yii2tech\embedded\tests;

use yii2tech\tests\unit\embedded\data\ContainerWithNested;
use yii2tech\tests\unit\embedded\data\Nested;
use yii2tech\tests\unit\embedded\TestCase;

/**
 * Class ContainerWithNestedTest
 * @package yii2tech\embedded\tests
 * @method assertTrue($condition)
 * @method assertFalse($condition)
 * @method assertEquals($expected, $actual)
 * @method assertNotEquals($expected, $actual)
 * @method assertSame($expected, $actual)
 * @method assertEmpty($value)
 * @method assertNotEmpty($value)
 */
class ContainerWithNestedTest extends TestCase
{
    public function testMappedEmbedModelFillUp()
    {
        $container = new ContainerWithNested();
        $container->nestedMappedData = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $this->assertTrue($container->getEmbedded('nestedMappedModel') instanceof Nested);
        $this->assertTrue($container->getEmbedded('nestedMappedModel') === $container->nestedMappedModel);
        $this->assertEquals('value1', $container->nestedMappedModel->name1);
        $this->assertEquals('value2', $container->nestedMappedModel->name2);
    }

    public function testMappedEmbedListFillUp()
    {
        $container = new ContainerWithNested();
        $container->nestedMappedListData = [
            [
                'name' => 'name1',
            ],
            [
                'name' => 'name2',
            ],
        ];
        $this->assertTrue($container->getEmbedded('nestedMappedList') === $container->nestedMappedList);
        $this->assertTrue($container->nestedMappedList[0] instanceof Nested);
        $this->assertTrue($container->nestedMappedList[1] instanceof Nested);

        $this->assertEquals('name1', $container->nestedMappedList[0]->name);
        $this->assertEquals('name2', $container->nestedMappedList[1]->name);
    }
}