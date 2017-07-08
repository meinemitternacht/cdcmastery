<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/3/2017
 * Time: 10:45 PM
 */

namespace CDCMastery\Models\Cache;


class CacheHandler
{
    const TTL_TINY = 5;
    const TTL_SMALL = 30;
    const TTL_MEDIUM = 120;
    const TTL_LARGE = 3600;
    const TTL_XLARGE = 86400;
    const TTL_MAX = 604800;

    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * CacheHandler constructor.
     * @param \Memcached $memcached
     */
    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * @param string $hash
     */
    public function delete(string $hash): void
    {
        $this->memcached->delete($hash);
    }

    /**
     * @param array $hashes
     */
    public function deleteArray(array $hashes): void
    {
        $this->memcached->deleteMulti($hashes);
    }

    public function flush(): void
    {
        $this->memcached->flush();
    }

    /**
     * @param string $hash
     * @return mixed
     */
    public function get(string $hash)
    {
        return $this->memcached->get($hash);
    }

    /**
     * @param string $key
     * @param array $params
     * @return mixed
     */
    public function hashAndGet(string $key, array $params = [])
    {
        return $this->get(
            self::hash(
                $key,
                $params
            )
        );
    }

    /**
     * @param $data
     * @param string $key
     * @param int|null $timeout
     * @param array $params
     */
    public function hashAndSet($data, string $key, ?int $timeout = null, array $params = []): void
    {
        $this->set(
            $data,
            self::hash(
                $key,
                $params
            ),
            $timeout
        );
    }

    /**
     * @param string $key
     * @param array $params
     * @return string
     */
    public static function hash(string $key, array $params = []): string
    {
        return hash(
            'sha256',
            $key . serialize(
                $params
            )
        );
    }

    /**
     * @param string $hash
     * @param int|null $timeout
     */
    public function refresh(string $hash, ?int $timeout = null): void
    {
        if (empty($hash)) {
            return;
        }

        if (is_null($timeout)) {
            $timeout = self::TTL_MEDIUM;
        }

        if ($timeout < self::TTL_TINY) {
            $timeout = self::TTL_TINY;
        }

        if ($timeout > self::TTL_MAX) {
            $timeout = self::TTL_MAX;
        }

        $this->memcached->touch(
            $hash,
            $timeout
        );
    }

    /**
     * @param $data
     * @param string $hash
     * @param int|null $timeout
     */
    public function set($data, string $hash, ?int $timeout = null): void
    {
        if (empty($hash)) {
            return;
        }

        if (is_null($timeout)) {
            $timeout = self::TTL_MEDIUM;
        }

        if ($timeout < self::TTL_TINY) {
            $timeout = self::TTL_TINY;
        }

        if ($timeout > self::TTL_MAX) {
            $timeout = self::TTL_MAX;
        }

        $this->memcached->set(
            $hash,
            $data,
            $timeout
        );
    }
}