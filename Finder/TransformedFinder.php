<?php

namespace FOS\ElasticaBundle\Finder;

use Elastica\Document;
use FOS\ElasticaBundle\Paginator\HybridPaginatorAdapter;
use FOS\ElasticaBundle\Paginator\RawPaginatorAdapter;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use FOS\ElasticaBundle\Paginator\FantaPaginatorAdapter;
use Pagerfanta\Pagerfanta;
use Elastica\SearchableInterface;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Result;

/**
 * Finds elastica documents and map them to persisted objects.
 */
class TransformedFinder implements PaginatedFinderInterface
{
    protected $searchable;
    protected $transformer;

    public function __construct(SearchableInterface $searchable, ElasticaToModelTransformerInterface $transformer)
    {
        $this->searchable  = $searchable;
        $this->transformer = $transformer;
    }

    /**
     * Search for a query string.
     *
     * @param string  $query
     * @param integer $limit
     * @param array   $options
     *
     * @return array of model objects
     **/
    public function find($query, $limit = null, $options = array())
    {
        $results = $this->search($query, $limit, $options);

        return $this->transformer->transform($results);
    }

    public function findHybrid($query, $limit = null, $options = array())
    {
        $results = $this->search($query, $limit, $options);

        return $this->transformer->hybridTransform($results);
    }

    public function findRaw($query, $limit = null, $options = array())
    {
        $results = $this->search($query, $limit, $options);

        return array_map(function (Result $result) {
            $res = $result->getSourceWithInnerHits();
            return $res;
        }, $results);
    }

    /**
     * Find documents similar to one with passed id.
     *
     * @param integer $id
     * @param array   $params
     * @param array   $query
     *
     * @return array of model objects
     **/
    public function moreLikeThis($id, $params = array(), $query = array())
    {
        $doc = new Document($id);
        $results = $this->searchable->moreLikeThis($doc, $params, $query)->getResults();

        return $this->transformer->transform($results);
    }

    /**
     * @param $query
     * @param null|int $limit
     * @param array    $options
     *
     * @return array
     */
    protected function search($query, $limit = null, $options = array())
    {
        $queryObject = Query::create($query);
        if (null !== $limit) {
            $queryObject->setSize($limit);
        }
        $results = $this->searchable->search($queryObject, $options)->getResults();

        return $results;
    }

    /**
     * Gets a paginator wrapping the result of a search.
     *
     * @param string $query
     * @param array  $options
     *
     * @return Pagerfanta
     */
    public function findPaginated($query, $options = array())
    {
        $queryObject = Query::create($query);
        $paginatorAdapter = $this->createPaginatorAdapter($queryObject, $options);

        return new Pagerfanta(new FantaPaginatorAdapter($paginatorAdapter));
    }

    /**
     * Gets a paginator wrapping the result of a search.
     *
     * @param string $query
     * @param array  $options
     *
     * @return Pagerfanta
     */
    public function findPaginatedHybrid($query)
    {
        $queryObject = Query::create($query);
        $paginatorAdapter = $this->createHybridPaginatorAdapter($queryObject);

        return new Pagerfanta(new FantaPaginatorAdapter($paginatorAdapter));
    }

    /**
     * Gets a paginator wrapping the result of a search.
     *
     * @param string $query
     * @param array  $options
     *
     * @return Pagerfanta
     */
    public function findPaginatedRaw($query)
    {
        $queryObject = Query::create($query);
        $paginatorAdapter = $this->createRawPaginatorAdapter($queryObject);

        return new Pagerfanta(new FantaPaginatorAdapter($paginatorAdapter));
    }

    /**
     * {@inheritdoc}
     */
    public function createPaginatorAdapter($query, $options = array())
    {
        $query = Query::create($query);

        return new TransformedPaginatorAdapter($this->searchable, $query, $options, $this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function createHybridPaginatorAdapter($query)
    {
        $query = Query::create($query);

        return new HybridPaginatorAdapter($this->searchable, $query, $this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function createRawPaginatorAdapter($query)
    {
        $query = Query::create($query);

        return new RawPaginatorAdapter($this->searchable, $query);
    }
}
