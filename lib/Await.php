<?php

use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

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

        $promise->then(
            function ($result) use (&$returnValue) {
                $returnValue = $result;
                $this->loop->stop();
            },
            function ($error) use (&$exception) {
                $exception = $error instanceof \Throwable ? $error : new \RuntimeException(print_r($error, true));
                $this->loop->stop();
            }
        );

        $this->loop->run();

        if ($exception) {
            throw $exception;
        }

        return $returnValue;
    }

    public function all(PromiseInterface ...$promises)
    {
        throw new \Exception("Not implemented");
    }

}
