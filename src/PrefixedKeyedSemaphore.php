<?php

namespace Amp\Sync;

final class PrefixedKeyedSemaphore implements KeyedSemaphore
{
    private readonly KeyedSemaphore $semaphore;

    private readonly string $prefix;

    public function __construct(KeyedSemaphore $semaphore, string $prefix)
    {
        $this->semaphore = $semaphore;
        $this->prefix = $prefix;
    }

    public function acquire(string $key): Lock
    {
        return $this->semaphore->acquire($this->prefix . $key);
    }
}
