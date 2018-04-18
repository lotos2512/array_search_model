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
    const SEARCH_LIMIT = 20;
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
    private $commonWhere = [];
    private $specialWhere = [];
    private $regexFilter;
    private $limit;
    private $index;
    private $offset;
    private $order;
    private $dataProvider;
    protected static $selfObject;


    final public function __construct()
    {
        $this->init();
    }

    protected function init() : void
    {
        $this->dataProvider = $this->getDataProvider();
    }

    /**
     * @return ArraySearchModel
     */
    final public static function find() : self
    {
        if (static::$selfObject === null) {
            static::$selfObject = new static();
        }
        return static::$selfObject;
    }

    /**
     * @return ArrayDataProvider
     */
    abstract public function getDataProvider() : ArrayDataProvider;

    /**
     * @param int $limit
     * @return ArraySearchModel
     */
    public function limit(int $limit = 0) : self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return ArraySearchModel
     */
    public function offset(int $offset = 0) : self
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
            if (in_array($params[0], $this->getCommonOperators())) {
                $this->commonWhere[] = $params;
            } elseif (in_array($params[0], $this->getSpecialOperators())) {
                $this->specialWhere[] = $params;
            } elseif ($params[0] == self::WHERE_OPERATOR_REGEX) {
                $this->regexFilter = $params;
            } else {
                throw new Exception('Invalid condition statement ' . $params[0]);
            }
        } else {
            throw new Exception('Invalid condition format');
        }
        return $this;
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
        $result = array_slice(
            $this->runOrder($this->whereFilter($this->dataProvider->getData())),
            $this->offset,
            $this->limit
        );
        return $this->index === null
            ? $result
            : ArrayHelper::index($result, function ($element) {
                return $element[$this->index];
            });
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
        foreach ($this->specialWhere as $condition) {
            $data = $this->specialFilterData($data, $condition[0], $condition[1], $condition[2]);
        }
        if (count($this->commonWhere)) {
            $data = $this->commonFilterData($data);
        }
        return $data;
    }

    private function specialFilterData(array $data, string $operator, string $field, $filterValue) : array
    {
        switch ($operator) {
            case  self::WHERE_OPERATOR_IN :
                return array_intersect_key($data, array_intersect(array_column($data, $field), $filterValue));
            default :
                return $data;
        }
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    private function commonFilterData($data) : array
    {
        $regexResult = 0;
        $result = [];
        if ($this->regexFilter !== null) {
            if (mb_strlen($this->regexFilter[2]) < 2) {
                return [];
            }
        }
        foreach ($data as $item) {
            if ($regexResult >= self::SEARCH_LIMIT) {
                break;
            }
            $resultItem = true;
            foreach ($this->commonWhere as $commonCondition) {
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
                    $resultItem = false;
                    break;
                }
            }
            if ($this->regexFilter !== null) {
                if ($this->regexFilter($item) === false) {
                    $resultItem = false;
                } else {
                    $regexResult++;
                }
            }
            if ($resultItem === true) {
                $result[] = $item;
            }
        }
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
    protected function regexFilter(array $item) : bool
    {
        $valuesByRegexCompare = [];
        $regex = "%" . $this->regexFilter[2] . "(/|)*.*" . "%iu";
        $result = false;
        if (is_callable($this->regexFilter[1])) {
            $valuesByRegexCompare = (array) call_user_func($this->regexFilter[1], $item);
        } elseif (is_string($this->regexFilter[1])) {
            if (isset($item[$this->regexFilter[1]])) {
                $valuesByRegexCompare = [$item[$this->regexFilter[1]]];
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