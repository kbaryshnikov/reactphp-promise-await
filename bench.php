<?php

use ReactPromiseAwait\Await;

require 'vendor/autoload.php';

class Bench
{

    private $promisesCount;

    public function __construct($promisesCount)
    {
        $this->promisesCount = $promisesCount;
    }

    public function run()
    {
        $deferred = new \React\Promise\Deferred();
        $start = microtime(true);
        $this->promises()->then(
            function () use ($start, $deferred) {
                $promisesTime = microtime(true) - $start;

                $start = microtime(true);
                $this->awaits();
                $awaitsTime = microtime(true) - $start;

                $deferred->resolve(['promises' => $promisesTime, 'awaits' => $awaitsTime]);
            }
        );

        return $deferred->promise();
    }

    private function promiseLoop(\React\EventLoop\LoopInterface $loop, \React\Promise\Deferred $deferred, $count)
    {
        if ($count > 0) {
            $this->makePromise($loop)->then(
                function () use ($loop, $deferred, $count) {
                    $this->promiseLoop($loop, $deferred, $count - 1);
                }
            );
        } else {
            $deferred->resolve();
        }
    }

    private function promises()
    {
        $loop = \React\EventLoop\Factory::create();
        $deferred = new \React\Promise\Deferred();
        $this->promiseLoop($loop, $deferred, $this->promisesCount);
        $loop->run();

        return $deferred->promise();
    }

    private function awaits()
    {
        $loop = \React\EventLoop\Factory::create();
        $await = new Await($loop);
        for ($i = 0; $i < $this->promisesCount; ++$i) {
            $await->one($this->makePromise($loop));
        }
    }

    private function makePromise(\React\EventLoop\LoopInterface $loop)
    {
        return new \React\Promise\Promise(
            function ($resolve) use ($loop) {
                $loop->nextTick($resolve);
            }
        );
    }

}

$promisesCount = $_SERVER['argv'][1] ?? 500000;
echo $promisesCount . PHP_EOL;

(new Bench($promisesCount))->run()->then(
    function ($result) {
        print_r($result);
    }
);
