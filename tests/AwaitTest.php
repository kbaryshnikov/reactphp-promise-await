<?php

use React\Promise\Promise;

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

    public function testParallelExecution()
    {
        $await = new Await($this->loop);

        $promise1 = $this->makeTimeout(0.1, "")->then(
            function () {
                return $this->makeTimeout(0.1, "tick1");
            }
        );

        $promise2 = $this->makeTimeout(0.1, "tick2");

        $promiseRejects = $this->makeTimeout(0, new \Exception("reject"));

        $result1 = $await->one($promise1);
        $this->assertEquals("tick1", $result1);

        $result2 = $await->one($promise2);
        $this->assertEquals("tick2", $result2);

        $this->assertEquals("tick3", $await->one($this->makeTimeout(0.1, "tick3")));

        $this->expectExceptionMessage("reject");
        $await->one($promiseRejects);
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
