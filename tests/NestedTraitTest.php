<?php

namespace yii2tech\tests\unit\embedded;

use yii2tech\tests\unit\embedded\data\ContainerWithNested;
use yii2tech\tests\unit\embedded\data\Nested;

/**
 * Class NestedTraitTest
 * @package yii2tech\tests\unit\embedded
 * @method assertTrue($condition)
 * @method assertFalse($condition)
 * @method assertEquals($expected, $actual)
 * @method assertNotEquals($expected, $actual)
 * @method assertSame($expected, $actual)
 * @method assertEmpty($value)
 * @method assertNotEmpty($value)
 */
class NestedTraitTest extends TestCase
{
    public function testNestedFillUpEmbed()
    {
        $container = new ContainerWithNested();
        $container->nestedData = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $this->assertTrue($container->getEmbedded('nestedModel') instanceof Nested);
        $this->assertTrue($container->getEmbedded('nestedModel') === $container->nestedModel);
        $this->assertEquals('value1', $container->nestedModel->name1);
        $this->assertEquals('value2', $container->nestedModel->name2);
    }

    public function testNestedModelSet()
    {
        $container = new ContainerWithNested();
        $nested = new Nested([
            'name1' => 'value1',
            'name2' => 'value2',
        ]);
        $container->nestedModel = $nested;

        $this->assertEmpty($container->nestedData);
        $container->refreshFromEmbedded();
        $this->assertNotEmpty($container->nestedData);
        $this->assertEquals($container->nestedData['name1'], 'value1');
        $this->assertEquals($container->nestedData['name2'], 'value2');

        $this->assertTrue($container->getEmbedded('nestedModel') instanceof Nested);
        $this->assertTrue($container->getEmbedded('nestedModel') === $container->nestedModel);
        $this->assertEquals('value1', $container->nestedModel->name1);
        $this->assertEquals('value2', $container->nestedModel->name2);
    }

    public function testNestedModelListSet()
    {
        $container = new ContainerWithNested();
        $nestedList = new \ArrayObject([
            new Nested([
                'name1' => 'list1Value1',
                'name2' => 'list1Value2',
            ]),
            new Nested([
                'name1' => 'list2Value1',
                'name2' => 'list2Value2',
            ]),
        ]);
        $container->nestedList = $nestedList;

        $this->assertEmpty($container->nestedListData);

        $container->refreshFromEmbedded();

        $this->assertNotEmpty($container->nestedListData);

        $this->assertTrue($container->getEmbedded('nestedList') instanceof \ArrayAccess);
        $this->assertSame($nestedList, $container->getEmbedded('nestedList'));
    }

    public function testNestedFillUpEmbedList()
    {
        $container = new ContainerWithNested();
        $container->nestedListData = [
            [
                'name' => 'name1',
            ],
            [
                'name' => 'name2',
            ],
        ];

        $this->assertTrue($container->getEmbedded('nestedList') === $container->nestedList);
        $this->assertTrue($container->nestedList[0] instanceof Nested);
        $this->assertTrue($container->nestedList[1] instanceof Nested);

        foreach ($container->nestedList as $key => $nested) {
            $this->assertSame($container, $nested->owner);
            $this->assertEquals($key, $nested->index);
        }

        $this->assertEquals('name1', $container->nestedList[0]->name);
        $this->assertEquals('name2', $container->nestedList[1]->name);
    }

    public function testFormNameForNestedModel()
    {
        $container = new ContainerWithNested();
        $container->nestedData = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];

        $this->assertSame($container, $container->nestedModel->owner);
        $this->assertEquals($container->formName() . '[nestedModel]', $container->nestedModel->formName());
    }

    public function testFormNameForNestedSelfModel()
    {
        $container = new ContainerWithNested();

        $this->assertSame($container, $container->self->owner);
        $this->assertEquals($container->formName() . '[self]', $container->self->formName());
    }

    public function testNestedModelLabelGeneration() {
        $container = new ContainerWithNested();

        $this->assertEquals('Nested Model Name1', $container->getAttributeLabel('nestedModel[name1]'));
        $this->assertEquals('Nested Model Name2', $container->getAttributeLabel('nestedModel[name2]'));
    }
}