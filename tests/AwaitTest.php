<?php

use React\Promise\Promise;
use ReactPromiseAwait\Await;

class AwaitTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    public function testAwaitOne()
    {
        $await = new Await($this->loop);
        $result = $await->one($this->makeTimeout(0.1, "tick"));
        $this->assertEquals("tick", $result);
        $result = $await->one($this->makeTimeout(0.1, "tick2"));
        $this->assertEquals("tick2", $result);
    }

    public function testAwaitOneException()
    {
        $await = new Await($this->loop);

        $e = null;
        try {
            $await->one($this->makeTimeout(0.1, new \Exception("rejection")));
        } catch (\Exception $e) {
            // pass
        }
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertEquals("rejection", $e->getMessage());

        $result = $await->one($this->makeTimeout(0.1, "tick"));
        $this->assertEquals("tick", $result);
    }

    public function testAwaitAll()
    {
        $await = new Await($this->loop);
        $promises = [];
        $expected = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->makeTimeout(random_int(0, 100) / 1000, $i);
            $expected[] = $i;
        }
        $result = $await->all(...$promises);
        $this->assertEquals($expected, $result);
    }

    public function testAwaitAllException()
    {
        $await = new Await($this->loop);
        $promises = [$this->makeTimeout(0, new \Exception("reject"))];
        for ($i = 1; $i < 10; $i++) {
            $promises[] = $this->makeTimeout(random_int(1, 100) / 1000, $i);
        }
        $this->expectExceptionMessage("reject");
        $await->all(...$promises);
    }

    public function testAwaitRace()
    {
        $await = new Await($this->loop);
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->makeTimeout($i, $i);
        }
        $result = $await->race(...$promises);
        $this->assertEquals(0, $result);
    }

    public function testAwaitRaceException()
    {
        $await = new Await($this->loop);
        $promises = [$this->makeTimeout(0, new \Exception("reject"))];
        for ($i = 1; $i < 10; $i++) {
            $promises[] = $this->makeTimeout(random_int(1, 100) / 1000, $i);
        }
        $this->expectExceptionMessage("reject");
        $await->all(...$promises);
    }

    public function testParallelExecution()
    {
        $await = new Await($this->loop);

        $promise1 = $this->makeTimeout(0, "");
        $promise2 = $this->makeTimeout(0.1, "");

        $firstResult = null;
        $firstTime = null;
        $promise1->then(function() use ($await, &$firstResult, &$firstTime) {
            $firstResult = $await->one($this->makeTimeout(0.2, "first"));
            $firstTime = microtime(true);
        });
        $promise2->then(function() use ($await, &$secondResult, &$secondTime) {
            $secondResult = $await->one($this->makeTimeout(0, "second"));
            $secondTime = microtime(true);
        });

        $this->loop->run();

        $await->one($this->makeTimeout(0.3, ""));

        $this->assertEquals("first", $firstResult);
        $this->assertEquals("second", $secondResult);
        $this->assertGreaterThanOrEqual(0.08, $firstTime - $secondTime);
    }

    protected function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
    }

    private function makeTimeout($timeout, $result)
    {
        return new Promise(
            function ($resolve, $reject) use ($timeout, $result) {
                $this->loop->addTimer(
                    $timeout,
                    function () use ($resolve, $reject, $result) {
                        if ($result instanceof \Exception) {
                            $reject($result);
                        } else {
                            $resolve($result);
                        }
                    }
                );
            }
        );
    }

}
