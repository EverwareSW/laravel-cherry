<?php

namespace Everware\LaravelCherry\Collections;

/**
 * @inheritDoc
 */
class LazyCollection extends \Illuminate\Support\LazyCollection
{
    /**
     * Whether the all() method should preserve the keys passed from the source.
     * See https://github.com/laravel/framework/pull/53153
     *
     * @var bool
     */
    public bool $preserveKeys = true;

    /**
     * Because the parent LazyCollection runs `new static` many times from many different methods,
     * the newly created LazyCollection loses the $preserveKeys property value.
     * This checks if the new instance was created from within one of those methods and inherits the property value.
     * @inheritDoc
     */
    public function __construct($source = null)
    {
        parent::__construct($source);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $creator = $trace[1]['object'] ?? null;
        if ($creator instanceof self) {
            $this->preserveKeys = $creator->preserveKeys;
        }
    }

    /**
     * Make the all() method preserve or not preserve the keys passed from the source.
     * @param bool $preserveKeys
     * @return $this
     */
    public function preserveKeys(bool $preserveKeys = true): static
    {
        $this->preserveKeys = $preserveKeys;
        return $this;
    }

    /**
     * Make the all() method not preserve the keys passed from the source.
     * @return $this
     */
    public function dontPreserveKeys(): static
    {
        return $this->preserveKeys(false);
    }

    /**
     * @inheritDoc
     */
    public function all()
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator(), $this->preserveKeys);
    }
}