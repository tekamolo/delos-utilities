<?php
namespace Delos\Utilities\Repository;

use Illuminate\Database\Query\Builder;
use Delos\Utilities\Model\EloquentParaProviderFilterBase as ProviderFilterBase;

abstract class EloquentRepositoryFilterBase
{
    /**
     * @var ProviderFilterBase
     */
    protected $provider;

    /**
     * @var string
     */
    protected $sqlOffsetLimit;

    /**
     * @var string
     */
    protected $filters;

    /**
     * @var string
     */
    protected $manualConditions = "";

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param ProviderFilterBase $provider
     */
    public function setProvider(ProviderFilterBase $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @throws \Delos\Exception\Exception
     */
    public function checkProviderIsSet()
    {
        if (empty($this->provider)) {
            throw new \Delos\Exception\Exception("Set the filter data provider before using this method");
        }
    }

    /**
     * @param Builder $queryBuilder
     * @throws \Delos\Exception\Exception
     */
    protected function setSqlFilters(Builder $queryBuilder)
    {
        $this->checkProviderIsSet();
        $params = $this->provider->getAllParamArray();

        foreach ($params as $k => $v) {
            $offsetFields = in_array($k, array("limit", "offset","orderBy","orderType"));

            $rule = $this->provider->getRuleByProperty($k);
            if($rule == ProviderFilterBase::IGNORE) continue;
            if (!empty($this->provider->getSqlFieldNameByProperty($k))) {
                $databaseField = $this->provider->getSqlFieldNameByProperty($k);
            }


            if (!$offsetFields) {
                if($v !== $this->provider->getSqlNullCase($k) && $v !== $this->provider->getSqlNotNullCase($k)){
                    if($rule == ProviderFilterBase::IS_LIKE_IN_THE_MIDDLE){
                        $queryBuilder->where($databaseField,"like",'%'.$v.'%');
                    }else{
                        $queryBuilder->where($databaseField,$rule,$v);
                    }
                }else{
                    if($rule == ProviderFilterBase::IS_NULL_SQL){
                        $queryBuilder->whereNull($databaseField);
                    }elseif($rule == ProviderFilterBase::IS_NOT_NULL_SQL){
                        $queryBuilder->whereNotNull($databaseField);
                    }
                }
            }else{
                if($k == "offset"){
                    $queryBuilder->offset($v);
                }
                if($k == "limit"){
                    $queryBuilder->limit($v);
                }
                if($k == "orderBy" && !empty($this->provider->getOrderType())){
                    $queryBuilder->orderBy($v,$this->provider->getOrderType());
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getSqlOffsetLimit()
    {
        return $this->sqlOffsetLimit;
    }

    /**
     * @param string $sqlOffsetLimit
     */
    public function setSqlOffsetLimit($sqlOffsetLimit)
    {
        $this->sqlOffsetLimit = $sqlOffsetLimit;
    }
}
