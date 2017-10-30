<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\API;

use Claroline\CoreBundle\Persistence\AdapterProvider;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.api.finder")
 */
class FinderProvider
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var SerializerProvider
     */
    private $serializer;

    /**
     * @var AdapterProvider
     */
    private $adapter;

    private $ch;

    private $dm;

    /**
     * The list of registered finders in the platform.
     *
     * @var array
     */
    private $finders = [];

    /**
     * Finder constructor.
     *
     * @DI\InjectParams({
     *     "em"         = @DI\Inject("doctrine.orm.entity_manager"),
     *     "dm"         = @DI\Inject("doctrine_mongodb.odm.document_manager"),
     *     "serializer" = @DI\Inject("claroline.api.serializer"),
     *     "adapter"    = @DI\Inject("claroline.persistence.adapter"),
     *     "ch"         = @DI\Inject("claroline.config.platform_config_handler")
     * })
     *
     * @param ObjectManager      $om
     * @param SerializerProvider $serializer
     * @param SerializerProvider $adapter
     */
    public function __construct(
        $em,
        $dm,
        SerializerProvider $serializer,
        AdapterProvider $adapter,
        $ch
    ) {
        $this->em = $em;
        $this->dm = $dm;
        $this->serializer = $serializer;
        $this->adapter = $adapter;
        $this->ch = $ch;
    }

    /**
     * Registers a new finder.
     *
     * @param AbstractFinder $finder
     */
    public function add(AbstractFinder $finder)
    {
        $this->finders[$finder->getClass()] = $finder;
    }

    /**
     * Gets a registered finder instance.
     *
     * @param string $class
     *
     * @return AbstractFinder
     *
     * @throws \Exception
     */
    public function get($class)
    {
        if (empty($this->finders[$class])) {
            throw new FinderException(
                sprintf('No finder found for class "%s" Maybe you forgot to add the "claroline.finder" tag to your finder.', $class)
            );
        }

        return $this->finders[$class];
    }

    public function search($class, array $queryParams = [], array $serializerOptions = [])
    {
        // get search params
        $filters = isset($queryParams['filters']) ? $this->parseFilters($queryParams['filters']) : [];
        $sortBy = isset($queryParams['sortBy']) ? $this->parseSortBy($queryParams['sortBy']) : null;
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 0;
        $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : -1;

        $data = $this->fetch($class, $page, $limit, $filters, $sortBy);
        $count = $this->fetch($class, $page, $limit, $filters, $sortBy, true);

        return [
            'data' => array_map(function ($result) use ($serializerOptions) {
                return $this->serializer->serialize($result, $serializerOptions);
            }, $data),
            'totalResults' => $count,
            'page' => $page,
            'pageSize' => $limit,
            'filters' => $this->decodeFilters($filters),
            'sortBy' => $sortBy,
        ];
    }

    public function fetch($class, $page, $limit, array $filters, array $sortBy = null, $count = false)
    {
        $enableMongo = $this->adapter->has($class) && $this->ch->getParameter('enable_mongo');

        if ($this->adapter->has($class)) {
            $class = $enableMongo ?
              $this->adapter->get($class)->getDocumentClass() :
              $this->adapter->get($class)->getEntityClass();
        }

        try {
            /* @var QueryBuilder $qb */
            return ($enableMongo) ?
                $this->fetchDocuments($class, $page, $limit, $filters, $sortBy, $count) :
                $this->fetchEntities($class, $page, $limit, $filters, $sortBy, $count);
        } catch (FinderException $e) {
            //works for both document and entities
            $om = new ObjectManager($enableMongo ? $this->dm : $this->em);
            $data = $om->getRepository($class)->findBy($filters, null, 0 < $limit ? $limit : null, $page);

            return $count ? count($data) : $data;
        }
    }

    private function fetchEntities($class, $page, $limit, array $filters, array $sortBy = null, $count = false)
    {
        $om = new ObjectManager($this->em);

        $qb = $om->createQueryBuilder();
        $qb->select($count ? 'count(distinct obj)' : 'distinct obj')->from($class, 'obj');

        // filter query - let's the finder implementation process the filters to configure query
        $this->get($class)->configureEntityQueryBuilder($qb, $filters, $sortBy);

        // order query if implementation has not done it
        $this->sortResults($qb, $sortBy);

        if (!$count && 0 < $limit) {
            $qb->setFirstResult($page * $limit);
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();

        return $count ? (int) $query->getSingleScalarResult() : $query->getResult();
    }

    private function fetchDocuments($class, $page, $limit, array $filters, array $sortBy = null, $count = false)
    {
        $om = new ObjectManager($this->dm);

        $qb = $om->createQueryBuilder($class);
        // filter query - let's the finder implementation process the filters to configure query
        $this->get($class)->configureDocumentQueryBuilder($qb, $filters, $sortBy);

        if (!$count && 0 < $limit) {
            $qb->skip($page * $limit);
            $qb->limit($limit);
        }

        $query = $qb->getQuery();

        return $count ? (int) $query->getSingleScalarResult() : $query->getResult();
    }

    private function sortResults(QueryBuilder $qb, array $sortBy = null)
    {
        if (!empty($sortBy) && !empty($sortBy['property']) && 0 !== $sortBy['direction']) {
            // query needs to be sorted, check if the Finder implementation has a custom sort system
            $queryOrder = $qb->getDQLPart('orderBy');

            if (empty($queryOrder)) {
                // no order by defined
                $qb->orderBy('obj.'.$sortBy['property'], 1 === $sortBy['direction'] ? 'ASC' : 'DESC');
            }
        }
    }

    /**
     * @param string $sortBy
     *
     * @todo : we should make UI and API formats uniform to avoid such transformations
     *
     * @return array
     */
    private function parseSortBy($sortBy)
    {
        // default values
        $property = null;
        $direction = 0;

        if (!empty($sortBy)) {
            if ('-' === substr($sortBy, 0, 1)) {
                $property = substr($sortBy, 1);
                $direction = -1;
            } else {
                $property = $sortBy;
                $direction = 1;
            }
        }

        return [
            'property' => $property,
            'direction' => $direction,
        ];
    }

    private function parseFilters(array $filters)
    {
        $parsed = [];
        foreach ($filters as $property => $value) {
            // don't keep empty filters
            if ('' !== $value) {
                if (null !== $value) {
                    // parse filter value
                    if (is_numeric($value)) {
                        // convert numbers
                        $value = floatval($value);
                    } else {
                        // convert booleans
                        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if (null !== $booleanValue) {
                            $value = $booleanValue;
                        }
                    }
                }

                $parsed[$property] = $value;
            }
        }

        return $parsed;
    }

    /**
     * @param array $filters
     *
     * @todo : we should make UI and API formats uniform to avoid such transformations
     *
     * @return array
     */
    private function decodeFilters(array $filters)
    {
        $decodedFilters = [];
        foreach ($filters as $property => $value) {
            $decodedFilters[] = ['value' => $value, 'property' => $property];
        }

        return $decodedFilters;
    }
}
