<?php

namespace FOS\ElasticaBundle\Paginator;

use Elastica\ResultSet;
use Elastica\Result;

/**
 * Raw partial results transforms to a simple array.
 */
class RawPartialResults implements PartialResultsInterface
{
    protected $resultSet;

    /**
     * @param ResultSet $resultSet
     */
    public function __construct(ResultSet $resultSet)
    {
        $this->resultSet = $resultSet;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $resultSetResults = $this->resultSet->getResults();

        return array_map(function (Result $result) {
            $res = $result->getSourceWithInnerHits();
            return $res;
        }, $resultSetResults);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalHits()
    {
        return $this->resultSet->getTotalHits();
    }

    /**
     * {@inheritDoc}
     */
    public function getFacets()
    {
        if ($this->resultSet->hasFacets()) {
            return $this->resultSet->getFacets();
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getAggregations()
    {
        if ($this->resultSet->hasAggregations()) {
            return $this->resultSet->getAggregations();
        }

        return;
    }
}
