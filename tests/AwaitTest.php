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
