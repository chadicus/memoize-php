<?php

namespace TraderInteractive\Memoize;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Predis\Client;
use Predis\ClientInterface;

/**
 * @coversDefaultClass \TraderInteractive\Memoize\Predis
 * @covers ::<private>
 */
class PredisTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::memoizeCallable
     */
    public function memoizeCallableWithCachedValue()
    {
        $count = 0;
        $key = 'foo';
        $value = 'bar';
        $cachedValue = json_encode(['result' => $value]);
        $compute = function () use (&$count, $value) {
            $count++;

            return $value;
        };


        $client = $this->getPredisMock();
        $client->expects(
            $this->once()
        )->method('get')->with($this->equalTo($key))->will($this->returnValue($cachedValue));

        $memoizer = new Predis($client);

        $this->assertSame($value, $memoizer->memoizeCallable($key, $compute));
        $this->assertSame(0, $count);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::memoizeCallable
     */
    public function memoizeCallableWithExceptionOnGet()
    {
        $count = 0;
        $key = 'foo';
        $value = 'bar';
        $compute = function () use (&$count, $value) {
            $count++;

            return $value;
        };

        $client = $this->getPredisMock();
        $client->expects(
            $this->once()
        )->method('get')->with($this->equalTo($key))->will($this->throwException(new \Exception()));

        $memoizer = new Predis($client);

        $this->assertSame($value, $memoizer->memoizeCallable($key, $compute));
        $this->assertSame(1, $count);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::memoizeCallable
     */
    public function memoizeCallableWithUncachedKey()
    {
        $count = 0;
        $key = 'foo';
        $value = 'bar';
        $cachedValue = json_encode(['result' => $value]);
        $cacheTime = 1234;
        $compute = function () use (&$count, $value) {
            $count++;

            return $value;
        };

        $client = $this->getPredisMock();
        $client->expects($this->once())->method('get')->with($this->equalTo($key))->will($this->returnValue(null));
        $client->expects($this->once())->method('set')->with($this->equalTo($key), $this->equalTo($cachedValue));
        $client->expects($this->once())->method('expire')->with($this->equalTo($key), $this->equalTo($cacheTime));

        $memoizer = new Predis($client);

        $this->assertSame($value, $memoizer->memoizeCallable($key, $compute, $cacheTime));
        $this->assertSame(1, $count);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::memoizeCallable
     */
    public function memoizeCallableWithUncachedKeyWithExceptionOnSet()
    {
        $count = 0;
        $key = 'foo';
        $value = 'bar';
        $cachedValue = json_encode(['result' => $value]);
        $compute = function () use (&$count, $value) {
            $count++;

            return $value;
        };

        $client = $this->getPredisMock();
        $client->expects(
            $this->once()
        )->method('get')->with($this->equalTo($key))->will($this->returnValue(null));
        $setExpectation = $client->expects(
            $this->once()
        )->method('set')->with($this->equalTo($key), $this->equalTo($cachedValue));
        $setExpectation->will($this->throwException(new \Exception()));

        $memoizer = new Predis($client);

        $this->assertSame($value, $memoizer->memoizeCallable($key, $compute));
        $this->assertSame(1, $count);
    }

    private function getPredisMock() : ClientInterface
    {
        return $this->getMockBuilder('\Predis\Client')->setMethods(['get', 'set', 'expire'])->getMock();
    }
}
