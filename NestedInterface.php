<?php

namespace yii2tech\embedded;

interface NestedInterface
{
    public function getOwner();
    public function getOwnerAttribute();
    public function setOwner($owner);
    public function setOwnerAttribute($ownerAttribute);
}