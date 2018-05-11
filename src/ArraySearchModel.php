<?php

namespace lotos2512\array_search_model;

use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class ArraySearchModel
 * @property ArrayDataProvider $dataProvider
 * @package app\components\array_search_models
 */
abstract class ArraySearchModel
{
    const WHERE_OPERATOR_IN = 'in';
    const WHERE_OPERATOR_EQUALLY = '==';

    const WHERE_OPERATOR_REGEX = 'regex';
    const WHERE_OPERATOR_HARD_EQUALLY = '===';
    const WHERE_OPERATOR_HARD_NOT_EQUALLY = '!==';
    const WHERE_OPERATOR_NOT_EQUALLY = '!=';
    const WHERE_OPERATOR_MORE_OR_EQUALLY = '>=';
    const WHERE_OPERATOR_LESS_OR_EQUALLY = '<=';
    const WHERE_OPERATOR_LESS = '<';
    const WHERE_OPERATOR_MORE = '>';

    public const ORDER_ASK = 'asc';
    public const ORDER_DESK = 'desk';

    private $conditions = [];
    private $maxConditionIndex;

    private $regexConditions = [];

    const CONDITION_TYPE_COMMON = 'common';
    const CONDITION_TYPE_SPECIAL = 'special';

    const CONDITION_TYPE_REGEX = 'regex';

    private $limit;
    private $index;
    private $offset;
    private $order;
    private $dataProvider;
    private $customFilter;

    final public function __construct()
    {
        $this->dataProvider = $this->getDataProvider();
        $this->init();
    }

    protected function init() : void
    {

    }

    /**
     * @return ArraySearchModel
     */
    final public static function find() : self
    {
        return new static();
    }

    /**
     * @return ArrayDataProvider
     */
    abstract public function getDataProvider() : ArrayDataProvider;

    /**
     * @param int $limit
     * @return ArraySearchModel
     */
    public function limit(int $limit) : self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return ArraySearchModel
     */
    public function offset(int $offset) : self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param string $index
     * @return ArraySearchModel
     */
    public function indexBy(string $index) : self
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @param array | callable $order
     * array - ['поле1' => self::ORDER_ASK, 'поле2' => self::ORDER_DESC,] - пока не реализованное
     * callable
     *
     * usort($tickers, function ($a, $b) {
     *      return (float) $b['exchange_price_percent'] <=> (float) $a['exchange_price_percent'];
     * });
     *
     * @return ArraySearchModel
     * @throws Exception
     */
    public function orderBy($order) : self
    {
        if (is_array($order) || is_callable($order)) {
            $this->order = $order;
            return $this;
        }
        throw new Exception('Invalid sorting format');
    }

    /**
     * @param array $params ['in', 'поле', [значения]
     * 0 - operator from constant WHERE_OPERATOR_.*
     * 1 value from array by compare.Can be callback function with result
     * 2 - value by compare
     *
     * $params = [
     *   'regex',
     *   function ($ticker) {
     *       return [
     *           $ticker['title'],
     *           $ticker['beauty_title'],
     *           @$ticker['company']['title'],
     *           $ticker['beauty_company_name'],
     *       ];
     *   },
     *   'TGHE',
     * ];
     * $params = [
     *   'regex',
     *   'key_from_array'
     *   'value_by_compare',
     * ];
     * $params = [
     *   'in',
     *   'key_from_array_by_compare'
     *   array| callable,
     * ];
     * $params = [
     *   '===',
     *   'type',
     *   'share'
     * ];
     *
     * @return static
     * @throws Exception
     */
    public function where(array $params) : self
    {
        if (count($params) === 3) {
            $conditionType = $this->getConditionType($params[0]);
            if ($this->maxConditionIndex === null) {
                $this->maxConditionIndex = 0;
                if ($conditionType === self::CONDITION_TYPE_REGEX) {
                    $this->regexConditions[$this->maxConditionIndex] = $params;
                    return $this;
                }
                $this->conditions[][$conditionType][] = $params;
            } else {
                if ($conditionType === self::CONDITION_TYPE_REGEX) {
                    $this->regexConditions[$this->maxConditionIndex] = $params;
                    return $this;
                }
                $condition = $this->conditions;
                $condition[$this->maxConditionIndex][$conditionType][] = $params;
                $this->conditions = $condition;
            }
        } else {
            throw new Exception('Invalid condition format');
        }
        return $this;
    }

    /**
     * @param array $params
     * @return ArraySearchModel
     * @throws Exception
     */
    public function orWhere(array $params) : self
    {
        if (count($params) === 3) {
            $conditionType = $this->getConditionType($params[0]);
            if ($this->maxConditionIndex === null) {
                $this->maxConditionIndex = 0;
                if ($conditionType === self::CONDITION_TYPE_REGEX) {
                    $this->regexConditions[$this->maxConditionIndex] = $params;
                    return $this;
                }
                $this->conditions[][$conditionType][] = $params;
            } else {
                $this->maxConditionIndex++;
                if ($conditionType === self::CONDITION_TYPE_REGEX) {
                    $this->regexConditions[$this->maxConditionIndex] = $params;
                    return $this;
                }
                $condition = $this->conditions;
                $condition[$this->maxConditionIndex][$conditionType][] = $params;
                $this->conditions = $condition;
            }
        } else {
            throw new Exception('Invalid condition format');
        }
        return $this;
    }

    /**
     * @param string $type
     * @return string
     * @throws Exception
     */
    protected function getConditionType(string $type) : string
    {
        if(in_array($type, $this->getCommonOperators())) {
            return self::CONDITION_TYPE_COMMON;
        } elseif (in_array($type, $this->getSpecialOperators())) {
            return self::CONDITION_TYPE_SPECIAL;
        } elseif ($type === self::WHERE_OPERATOR_REGEX) {
            return self::CONDITION_TYPE_REGEX;
        }
        throw new Exception('Invalid condition statement ' .$type);
    }




    public function getAllOperators() : array
    {
        return [
            self::WHERE_OPERATOR_EQUALLY,
            self::WHERE_OPERATOR_HARD_EQUALLY,
            self::WHERE_OPERATOR_HARD_NOT_EQUALLY,
            self::WHERE_OPERATOR_NOT_EQUALLY,
            self::WHERE_OPERATOR_MORE_OR_EQUALLY,
            self::WHERE_OPERATOR_LESS_OR_EQUALLY,
            self::WHERE_OPERATOR_LESS,
            self::WHERE_OPERATOR_MORE,
            self::WHERE_OPERATOR_IN,
            self::WHERE_OPERATOR_REGEX,
        ];
    }


    /**
     * @return array
     */
    private function getCommonOperators() : array
    {
        return [
            self::WHERE_OPERATOR_EQUALLY,
            self::WHERE_OPERATOR_HARD_EQUALLY,
            self::WHERE_OPERATOR_HARD_NOT_EQUALLY,
            self::WHERE_OPERATOR_NOT_EQUALLY,
            self::WHERE_OPERATOR_MORE_OR_EQUALLY,
            self::WHERE_OPERATOR_LESS_OR_EQUALLY,
            self::WHERE_OPERATOR_LESS,
            self::WHERE_OPERATOR_MORE,
        ];
    }

    /**
     * @return array
     */
    private function getSpecialOperators() : array
    {
        return [
            self::WHERE_OPERATOR_IN
        ];
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function all() : ?array
    {
        $data = $this->runOrder($this->whereFilter($this->dataProvider->getData()));
        if ($this->customFilter !== null) {
            $data = call_user_func($this->customFilter, $data);
        }
        $result = array_slice(
            $data,
            $this->offset,
            $this->limit
        );
        return $this->index === null
            ? $result
            : ArrayHelper::index($result, function ($element) {
                return $element[$this->index];
            });
    }

    public function customFilter(callable $function) : void
    {
        $this->customFilter = $function;
    }

    private function runOrder(array $data) : array
    {
        if (is_callable($this->order)) {
            $sortFunction = $this->order;
            $data = call_user_func($sortFunction, $data);
        }
        return $data;
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function whereFilter(array $data) : array
    {
        $result = [];
        foreach ($this->conditions as $key => $conditions) {
            $result = array_merge($result, $this->filterData($data, $conditions, $key));
        }
        return $result;
    }

    private function filterData(array $data, array $conditions, int $whereGroupIndex) : array
    {
        foreach ($conditions as $conditionType => $condition) {
            if ($conditionType === self::CONDITION_TYPE_COMMON) {
                $data = $this->commonFilterData($data, $condition, $whereGroupIndex);
            } elseif ($conditionType === self::CONDITION_TYPE_SPECIAL) {
                $data = $this->specialFilterData($data, $condition);
            }
        }
        return $data;
    }




    private function specialFilterData(array $data, array $conditions) : array
    {
        $result = [];
        foreach ($conditions as $condition) {
            $operator = $condition[0];
            $field = $condition[1];
            $filterValue = $condition[2];
            switch ($operator) {
                case  self::WHERE_OPERATOR_IN :
                    $filterResult = array_intersect_key($data, array_intersect(array_column($data, $field), $filterValue));
                    $result = array_merge($result, $filterResult) ;
                default :
            }
        }
        return $result;
    }

    /**
     * @param $data
     * @param array $conditions
     * @param int $whereGroupIndex
     * @return array
     */
    private function commonFilterData($data, array $conditions, int $whereGroupIndex) : array
    {
        $result = array_filter($data, function ($item) use ($conditions, $whereGroupIndex) {
            $result = true;
            foreach ($conditions as $commonCondition) {
                $condition = $commonCondition[0];
                $itemVal = $commonCondition[1];
                $conditionValue = $commonCondition[2];
                $itemValue = null;
                if (is_string($itemVal)) {
                    $itemValue = $item[$itemVal];
                } elseif (is_callable($itemVal)) {
                    $itemValue = call_user_func($itemVal, $item);
                }
                if ($this->checkValByCommonFilter($itemValue, $conditionValue, $condition) === false) {
                    $result = false;
                    break;
                }
            }
            if (isset($this->regexConditions[$whereGroupIndex]) && $result === true) {
                if ($this->regexFilter($item, $this->regexConditions[$whereGroupIndex]) === false) {
                    $result = false;
                }
            }
            return $result;
        });
        return $result;
    }

    /**
     * @param $itemValue
     * @param $conditionValue
     * @param string $condition
     * @return bool
     * @throws Exception
     */
    private function checkValByCommonFilter($itemValue, $conditionValue, string $condition) : bool
    {
        switch ($condition) {
            case self::WHERE_OPERATOR_EQUALLY :
                return $itemValue == $conditionValue;
            case self::WHERE_OPERATOR_HARD_EQUALLY :
                return $itemValue === $conditionValue;
            case self::WHERE_OPERATOR_MORE_OR_EQUALLY :
                return $itemValue >= $conditionValue;
            case self::WHERE_OPERATOR_LESS_OR_EQUALLY :
                return $itemValue <= $conditionValue;
            case self::WHERE_OPERATOR_LESS :
                return $itemValue < $conditionValue;
            case self::WHERE_OPERATOR_MORE :
                return $itemValue > $conditionValue;
            case self::WHERE_OPERATOR_NOT_EQUALLY :
                return $itemValue != $conditionValue;
            case self::WHERE_OPERATOR_HARD_NOT_EQUALLY :
                return $itemValue !== $conditionValue;
            default :
                throw new \Exception();
        }
    }

    /**
     * @param array $item
     * @return bool
     * @throws Exception
     */
    protected function regexFilter(array $item, array $regexCondition) : bool
    {
        $valuesByRegexCompare = [];
        $regex = "%" . $regexCondition[2] . "(/|)*.*" . "%iu";
        $result = false;
        if (is_callable($regexCondition[1])) {
            $valuesByRegexCompare = (array) call_user_func($regexCondition[1], $item);
        } elseif (is_string($regexCondition[1])) {
            if (isset($item[$regexCondition[1]])) {
                $valuesByRegexCompare = [$item[$regexCondition[1]]];
            }
        } else {
            throw new Exception('Incorrect value for compare');
        }
        foreach ($valuesByRegexCompare as $itemByRegex) {
            try {
                if (preg_match($regex, $itemByRegex)) {
                    $result = true;
                    break;
                }

            } catch (Exception $e) {
                break;
            }
        }
        return $result;
    }
}