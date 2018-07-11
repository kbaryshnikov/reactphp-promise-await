<?php

namespace ReactPromiseAwait;

use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\race;

class Await
{

    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function one(PromiseInterface $promise)
    {
        $returnValue = null;
        $exception = null;
        $done = false;

        $promise->then(
            function ($result) use (&$returnValue, &$done) {
                $returnValue = $result;
                $this->loop->stop();
                $done = true;
            },
            function ($error) use (&$exception, &$done) {
                $exception = $error instanceof \Throwable ? $error : new \RuntimeException(print_r($error, true));
                $this->loop->stop();
                $done = true;
            }
        );

        while (!$done) {
            $this->loop->run();
        }

        if ($exception) {
            /** @var \Throwable $exception */
            throw $exception;
        }

        return $returnValue;
    }

    public function all(PromiseInterface ...$promises)
    {
        return $this->one(all($promises));
    }

    public function race(PromiseInterface ...$promises)
    {
        return $this->one(race($promises));
    }

    public function stopLoop()
    {
        $this->loop->stop();
    }

}
