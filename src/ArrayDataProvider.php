<?php
namespace app\components\array_search_models;

namespace lotos2512\array_search_model;

abstract class ArrayDataProvider
{
    abstract public function getData() : array;
}