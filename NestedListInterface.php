<?php
/**
 * Created
 * User: red
 * Date: 06.04.18
 * Time: 13:37
 */

namespace yii2tech\embedded;

/**
 * Interface NestedListInterface
 *
 * Use it if Nested object can be used in list
 *
 * @package yii2tech\embedded
 */
interface NestedListInterface extends NestedInterface
{
    public function getIndex();
    public function setIndex($index);
    public function formName($withIndex);
}