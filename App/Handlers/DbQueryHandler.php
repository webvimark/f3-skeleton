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
        $this->setFetchMode(\PDO::FETCH_COLUMN);
        return $this->get();
    }
 
    /**
     * Get scalar value from the first selected column first found row
     *
     * @return string|null
     */
    public function getScalar()
    {
        $this->setFetchMode(\PDO::FETCH_COLUMN);
        return $this->first();
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
                $result = $this->mapResults(parent::get());

                \Cache::instance()->set($cacheKey, $result, $this->cacheTtl);
            }

            return $result;
        }
        return $this->mapResults(parent::get());
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