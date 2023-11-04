<?php

namespace DatabaseCache;

use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;
use Predis\Client as Redis;

class RepositoryTest extends TestCase
{
    /**
     * @covers \DatabaseCache\Repository::__construct
     */
    public function testCreateCredential()
    {
        $repository = new Repository();
        $this->assertInstanceOf(Repository::class, $repository);
    }

    /**
     * @covers \DatabaseCache\Repository::getQuery
     */
    public function testGetQuery()
    {
        $identifier = 'table:id';

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('get')
            ->with($identifier)
            ->once()
            ->andReturn('token')
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $getQuery = $repository->getQuery($identifier);

        $this->assertEquals($getQuery, 'token');
    }

    /**
     * @covers \DatabaseCache\Repository::getQuery
     */
    public function testGetQueryException()
    {
        $identifier = 'table:id';

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('get')
            ->with($identifier)
            ->once()
            ->andThrow(new Exception('err', 500))
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $getQuery = $repository->getQuery($identifier);

        $this->assertEquals($getQuery, null);
    }

    /**
     * @covers \DatabaseCache\Repository::setQuery
     */
    public function testSetQuery()
    {
        $identifier = 'table:id';
        $queryResult = json_encode([
            'test' => true,
        ]);

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('set')
            ->with($identifier, $queryResult)
            ->once()
            ->andReturn('token')
            ->shouldReceive('setex')
            ->never()
            ->andReturn('token')
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $setQuery = $repository->setQuery($identifier, $queryResult);

        $this->assertEquals($setQuery, true);
    }

    /**
     * @covers \DatabaseCache\Repository::setQuery
     */
    public function testSetQueryWithExpire()
    {
        $identifier = 'table:id';
        $queryResult = json_encode([
            'test' => true,
        ]);

        $expireInSecond = 60;

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('set')
            ->never()
            ->andReturn('token')
            ->shouldReceive('setex')
            ->with($identifier, $expireInSecond, $queryResult)
            ->once()
            ->andReturn('token')
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $setQuery = $repository->setQuery($identifier, $queryResult, $expireInSecond);

        $this->assertEquals($setQuery, true);
    }

    /**
     * @covers \DatabaseCache\Repository::setQuery
     */
    public function testSetQueryException()
    {
        $identifier = 'table:id';
        $queryResult = json_encode([
            'test' => true,
        ]);

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('set')
            ->with($identifier, $queryResult)
            ->once()
            ->andThrow(new Exception('err', 500))
            ->shouldReceive('setex')
            ->never()
            ->andReturn('token')
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $setQuery = $repository->setQuery($identifier, $queryResult);

        $this->assertEquals($setQuery, false);
    }

    /**
     * @covers \DatabaseCache\Repository::delQuery
     */
    public function testDelQuery()
    {
        $identifier = 'table:id';

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('del')
            ->with($identifier)
            ->once()
            ->andReturn('token')
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $delQuery = $repository->delQuery($identifier);

        $this->assertEquals($delQuery, true);
    }

    /**
     * @covers \DatabaseCache\Repository::delQuery
     */
    public function testDelQueryException()
    {
        $identifier = 'table:id';

        $redisMock = Mockery::mock(Redis::class)
            ->shouldReceive('del')
            ->with($identifier)
            ->once()
            ->andThrow(new Exception('err', 500))
            ->getMock();

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('validateConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($redisMock);

        $delQuery = $repository->delQuery($identifier);

        $this->assertEquals($delQuery, false);
    }

    /**
     * @covers \DatabaseCache\Repository::generateIdentifierByArray
     */
    public function testGenerateIdentifierByArray()
    {
        $array = [
            'test' => '1',
            'test2' => '2',
        ];

        $repository = new Repository();
        $generateIdentifierByArray = $repository->generateIdentifierByArray(
            $array
        );

        $this->assertEquals($generateIdentifierByArray, ':test:1:test2:2:');
    }

    /**
     * @covers \DatabaseCache\Repository::generateIdentifierByArray
     */
    public function testGenerateIdentifierByArrayNotAssociative()
    {
        $array = [
            '1',
            '2',
        ];

        $repository = new Repository();
        $generateIdentifierByArray = $repository->generateIdentifierByArray(
            $array
        );

        $this->assertEquals($generateIdentifierByArray, ':1:2:');
    }

    /**
     * @covers \DatabaseCache\Repository::validateConnection
     */
    public function testValidateConnection()
    {
        $redisSpy = Mockery::spy(Redis::class);

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('connectRedis')
            ->withNoArgs()
            ->once()
            ->andReturn($redisSpy);

        $validateConnection = $repository->validateConnection();

        $this->assertInstanceOf(Redis::class, $validateConnection);
    }

    /**
     * @covers \DatabaseCache\Repository::validateConnection
     */
    public function testValidateConnectionWithRedisAlreadyPass()
    {
        $redisSpy = Mockery::spy(Redis::class);

        $repository = Mockery::mock(Repository::class)->makePartial();
        $repository->shouldReceive('connectRedis')
            ->never()
            ->andReturn($redisSpy);

        $repository->redis = $redisSpy;

        $validateConnection = $repository->validateConnection();

        $this->assertInstanceOf(Redis::class, $validateConnection);
    }

    /**
     * @covers \DatabaseCache\Repository::redisConfig
     */
    public function testRedisConfig()
    {
        $config = [
            'port'   => 6380,
        ];

        $result = [
            'scheme' => 'tcp',
            'host'   => 'localhost',
            'port'   => 6380,
        ];

        $repository = new Repository();
        $redisConfig = $repository->redisConfig(
            $config
        );

        $this->assertEquals($redisConfig, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
