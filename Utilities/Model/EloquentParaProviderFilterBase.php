<?php


namespace Delos\Utilities\Model;

abstract class EloquentParaProviderFilterBase
{
    const IGNORE = "ignore";
    const IS_NULL_SQL = "IS NULL";
    const IS_NOT_NULL_SQL = "IS NOT NULL";
    const IS_LIKE = "LIKE";
    const IS_LIKE_END_WITH = "%LIKE";
    const IS_LIKE_START_WITH = "LIKE%";
    const IS_LIKE_IN_THE_MIDDLE = "%LIKE%";
    const IS_EQUAL_TO = "=";
    const IS_SUPERIOR_OR_EQUAL = ">=";
    const IS_INFERIOR_OR_EQUAL = "<=";
    const DATABASEDATA_FIELD_INDEX = "field";
    const DATABASEDATA_RULE_INDEX = "rule";
    const DATABASEDATA_TYPE_INDEX = "type";
    const DATABASEDATA_NULLCASE_INDEX = "null_case";
    const DATABASEDATA_NOTNULLCASE_INDEX = "not_null_case";

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var string
     */
    protected $orderType;

    /**
     * @return array
     */
    public function getAllParamArray()
    {
        $vars = $this->getObjectVars();
        $array = array();
        $that = $this->getObject();
        foreach ($vars as $k => $v) {
            $baseMethodName = $this->getMethodBaseCamelCase($k);
            $method = "get" . ucfirst($baseMethodName);
            if (method_exists($that, $method)) {
                $value = $that->$method();
                if ($value !== null) {
                    $array[$k] = $that->$method();
                }
            }
        }
        return $array;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getValue($property)
    {
        $that = $this->getObject();
        $baseMethod = $this->getMethodBaseCamelCase($property);
        $method = "get" . ucfirst($baseMethod);
        if (method_exists($that, $method)) {
            return $that->$method();
        }
    }

    /**
     * @param $property
     * @return int
     */
    public function getTypeByProperty($property)
    {
        $result = $this->DatabaseMethodExistAndReturnIndexValue($property, self::DATABASEDATA_TYPE_INDEX);
        return (!empty($result)) ? $result : PDO::PARAM_STR;
    }

    /**
     * @param $property
     * @return string
     */
    public function getRuleByProperty($property)
    {
        $result = $this->DatabaseMethodExistAndReturnIndexValue($property, self::DATABASEDATA_RULE_INDEX);
        if ($this->getValue($property) === $this->getSqlNullCase($property)) {
            $result = self::IS_NULL_SQL;
        }elseif($this->getValue($property) === $this->getSqlNotNullCase($property)) {
            $result = self::IS_NOT_NULL_SQL;
        }
        return (!empty($result)) ? $result : "=";
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getSqlFieldNameByProperty($property)
    {
        return $this->DatabaseMethodExistAndReturnIndexValue($property, self::DATABASEDATA_FIELD_INDEX);
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getSqlNullCase($property)
    {
        return $this->DatabaseMethodExistAndReturnIndexValue($property, self::DATABASEDATA_NULLCASE_INDEX);
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getSqlNotNullCase($property)
    {
        return $this->DatabaseMethodExistAndReturnIndexValue($property, self::DATABASEDATA_NOTNULLCASE_INDEX);
    }

    /**
     * @param $property
     * @param $index
     * @return mixed
     */
    public function DatabaseMethodExistAndReturnIndexValue($property, $index)
    {
        $that = $this->getObject();
        $baseMethodName = $this->getMethodBaseCamelCase($property);
        $method = "get" . ucfirst($baseMethodName) . "DatabaseData";
        if (method_exists($that, $method)) {
            $databaseData = $that->$method();
            if (array_key_exists($index, $databaseData)) {
                return $databaseData[$index];
            }
        }
    }

    /**
     * @param $propertyName
     * @return string
     */
    private function getMethodBaseCamelCase($propertyName){
        $explode = explode("_",$propertyName);
        $baseMethodName = "";
        foreach ($explode as $e){
            $baseMethodName .= ucfirst($e);
        }
        return $baseMethodName;
    }

    /**
     * @return array
     */
    abstract public function getObjectVars();

    /**
     * @return the object that will implement the method
     */
    abstract public function getObject();

    /**
     * @return mixed
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param mixed $orderBy
     * @return EloquentParaProviderFilterBase
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = ($orderBy == "") ? null : $orderBy;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @param string $orderType
     * @return EloquentParaProviderFilterBase
     */
    public function setOrderType($orderType)
    {
        $this->orderType = $orderType;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return array
     */
    public function getLimitDatabaseData()
    {
        return array(self::DATABASEDATA_TYPE_INDEX => \PDO::PARAM_INT);
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return EloquentParaProviderFilterBase
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array
     */
    public function getOffsetDatabaseData()
    {
        return array(
            self::DATABASEDATA_TYPE_INDEX => \PDO::PARAM_INT
        );
    }
}