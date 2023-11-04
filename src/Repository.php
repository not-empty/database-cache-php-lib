<?php

namespace DatabaseCache;

use Exception;
use Predis\Client as Redis;

class Repository
{
    public $redis;
    private $redisConfig;
    private $redisOptions;

    /**
     * construct class with redis config if pass
     * @param array $redisConfig
     * @param array $redisOptions
     * @return void
     */
    public function __construct(
        array $redisConfig = [],
        array $redisOptions = []
    ) {
        $this->redisConfig = $this->redisConfig($redisConfig);
        $this->redisOptions = $redisOptions;
    }

    /**
     * get database data in cache
     * @param string $identifier
     * @return string
     */
    public function getQuery(
        string $identifier
    ): ?string {
        try {
            $redis = $this->validateConnection();
            return $redis->get($identifier);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * put database result in cache
     * @param string $identifier
     * @param string $queryResult
     * @param int $expireInSeconds
     * @return bool
     */
    public function setQuery(
        string $identifier,
        string $queryResult,
        int $expireInSeconds = 0
    ): bool {
        try {
            $redis = $this->validateConnection();

            if ($expireInSeconds) {
                $redis->setex(
                    $identifier,
                    $expireInSeconds,
                    $queryResult
                );

                return true;
            }

            $redis->set($identifier, $queryResult);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * remove database cache from redis
     * @param string $identifier
     * @return bool
     */
    public function delQuery(
        string $identifier
    ): bool {
        try {
            $redis = $this->validateConnection();
            $redis->del($identifier);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * generate identifier using values in array
     * @param array $array
     * @return string
     */
    public function generateIdentifierByArray(
        array $array
    ): string {
        $identifier = ':';
        if (array_keys($array) !== range(0, count($array) - 1)) {
            foreach ($array as $key => $value) {
                $identifier .= "{$key}:{$value}:";
            }

            return $identifier;
        }

        foreach ($array as $value) {
            $identifier .= "{$value}:";
        }

        return $identifier;
    }

    /**
     * verify if already exists connection
     * @return Redis
     */
    public function validateConnection(): Redis
    {
        if ($this->redis) {
            return $this->redis;
        }

        return $this->connectRedis();
    }

    /**
     * create predis client config
     * @param array $redisConfig
     * @return array
     */
    public function redisConfig(
        array $redisConfig
    ): array {
        $defaultConfig = [
            'scheme' => 'tcp',
            'host'   => 'localhost',
            'port'   => 6379,
        ];

        return array_merge($defaultConfig, $redisConfig);
    }

    /**
     * @codeCoverageIgnore
     * return predis client object
     * @return Redis
     */
    public function connectRedis(): Redis
    {
        $this->redis = new Redis($this->redisConfig, $this->redisOptions);
        return $this->redis;
    }
}
