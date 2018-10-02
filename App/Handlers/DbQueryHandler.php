<?php
namespace App\Handlers;

/**
 * Extended QueryBuilder with cache() support
 */
class DbQueryHandler extends \Pixie\QueryBuilder\QueryBuilderHandler
{
    /**
     * The PDO fetch parameters to use
     *
     * @var array
     */
    protected $fetchParameters = array(\PDO::FETCH_ASSOC);

    /**
     * Cache time in seconds. 0 - no cache
     *
     * @var int
     */
    protected $cacheTtl = 0;
    /**
     * If not set then sha1 of the raw SQL will be used
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * If get() method should dump raw SQL
     *
     * @var boolean
     */
    protected $dump = false;

    /**
     * array_map() function that will be performed on result
     *
     * @var \Closure|null
     */
    protected $mapFunction;

    /**
     * @see $this->withMany() and $this->withManyVia()
     * @var array
     */
    protected $withs = [];

    /**
     * Cache time in seconds. 0 - no cache
     *
     * @param integer $ttl
     * @param string $cacheKey - if not set then sha1 of the raw SQL will be used
     * @return $this
     */
    public function cache($ttl = 3600, $cacheKey = null)
    {
        if (!is_int($ttl) || $ttl < 0) {
            throw new \InvalidArgumentException('Cache ttl should be positive integer or 0');
        }
        $this->cacheTtl = $ttl;
        $this->cacheKey = $cacheKey;
        return $this;
    }

    /**
     * array_map() function that will be performed on result
     * 
     * @param \Closure $mapFunction
     * @return $this
     */
    public function map(\Closure $mapFunction)
    {
        $this->mapFunction = $mapFunction;
        return $this;
    }

    /**
     * Add related data from "external_table"
     *
     * @param string $external_table
     * @param string $external_table_id
     * @param string $original_table_id
     * @param string $name
     * @return $this
     */
    public function withMany($external_table, $external_table_id, $original_table_id = 'id', $name = null)
    {
        if ($name === null) {
            $name = $external_table;
        }

        $this->withs[$name] = array_merge(
            ['type' => __FUNCTION__],
            compact(
                'name',
                'external_table',
                'external_table_id',
                'original_table_id'
            )
        );

        return $this;
    }

    /**
     * Add related data from "external_table" connnected from "via_table"
     *
     * @param string $external_table
     * @param string $via_table
     * @param string $via_table_original_id
     * @param string $via_table_external_id
     * @param string $external_table_id
     * @param string $original_table_id
     * @param string $name
     * @return $this
     */
    public function withManyVia(
        $external_table,
        $via_table,
        $via_table_original_id,
        $via_table_external_id,
        $external_table_id = 'id',
        $original_table_id = 'id',
        $name = null
    ) {
        if ($name === null) {
            $name = $external_table;
        }

        $this->withs[$name] = array_merge(
            ['type' => __FUNCTION__],
            compact(
                'name',
                'external_table',
                'via_table',
                'via_table_original_id',
                'via_table_external_id',
                'external_table_id',
                'original_table_id'
            )
        );

        return $this;
    }

    /**
     * If get() method should dump raw SQL
     * 
     * @param boolean $dump
     * @return $this
     */
    public function dump($dump = true)
    {
        $this->dump = $dump;
        return $this;
    }

    /**
     * Note - be careful with the left and right joins (because they can add more rows)
     * 
     * Respond with array containing following elements
     * [
     *      'items' => [...] // array of results
     *      'total' => 183 // total records
     * ]
     *
     * @param integer $currentPage
     * @param integer $perPage
     * @param boolean $countTotal - use false if you do not want to count records (minus query)
     * @return array
     */
    public function paginate($currentPage, $perPage = 20, $countTotal = true)
    {
        $currentPage = (int)$currentPage >= 1 ? (int)$currentPage : 1;
        $perPage = (int)$perPage >= 1 ? (int)$perPage : 1;

        $this->limit($perPage)->offset(($currentPage - 1) * $perPage);

        if ($countTotal) {
            return [
                'items' => $this->get(),
                'total' => $this->count(),
            ];
        } else {
            return [
                'items' => $this->get(),
            ];
        }
    }

    /**
     * Get 1-dimmenstional array with values from the first selected column
     *
     * @return array
     */
    public function getColumn()
    {
        return $this->setFetchMode(\PDO::FETCH_COLUMN)->get();
    }

    /**
     * Get scalar value from the first selected column first found row
     *
     * @return string|null
     */
    public function getScalar()
    {
        return $this->setFetchMode(\PDO::FETCH_COLUMN)->first();
    }

    /**
     * Get all rows
     *
     * @return array|null
     */
    public function get()
    {
        if ($this->dump) {
            echo $this->getQuery()->getRawSql();
            die;
        }

        if ($this->cacheTtl > 0) {
            $cacheKey = $this->cacheKey ? : sha1($this->getQuery()->getRawSql());

            if (!\Cache::instance()->exists($cacheKey, $result)) {
                $result = $this->mapResults($this->performWith(parent::get()));

                \Cache::instance()->set($cacheKey, $result, $this->cacheTtl);
            }

            return $result;
        }
        return $this->mapResults($this->performWith(parent::get()));
    }

    /**
     * Iterate over "withs" and add related datasets to the result
     *
     * @param array|null $result
     * @return array|null
     */
    protected function performWith($result)
    {
        if (!$result || !$this->withs) {
            return $result;
        }

        $qb = new static($this->getConnection());

        foreach ($this->withs as $name => $params) {
            if ($params['type'] === 'withManyVia') {
                $with = $qb->table($params['external_table'])
                    ->select($params['external_table'] . '.*')
                    ->select([$params['via_table'] . '.' . $params['via_table_original_id'] => '___placeholder'])
                    ->innerJoin($params['via_table'], $params['via_table'] . '.' . $params['via_table_external_id'], '=', $params['external_table'] . '.' . $params['external_table_id'])
                    ->whereIn($params['via_table'] . '.' . $params['via_table_original_id'], array_column($result, $params['original_table_id']))
                    ->get();

            } elseif ($params['type'] === 'withMany') {
                $with = $qb->table($params['external_table'])
                    ->select($params['external_table'] . '.*')
                    ->select([$params['external_table'] . '.' . $params['external_table_id'] => '___placeholder'])
                    ->whereIn($params['external_table'] . '.' . $params['external_table_id'], array_column($result, $params['original_table_id']))
                    ->get();
            }

            foreach ($result as &$item) {
                $item[$params['name']] = [];
                foreach ($with as $val) {
                    if ($item[$params['original_table_id']] == $val['___placeholder']) {
                        unset($val['___placeholder']);
                        $item[$params['name']][] = $val;
                    }
                }
            }

            $with = null;
        }

        return $result;
    }

    /**
     * Helper function for get() to implement array_map() on results
     * if $this->mapFunction is set
     *
     * @param array $result
     * @return array
     */
    protected function mapResults($result)
    {
        if ($this->mapFunction instanceof \Closure) {
            return array_map($this->mapFunction, $result);
        }

        return $result;
    }
}