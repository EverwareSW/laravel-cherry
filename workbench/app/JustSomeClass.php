<?php

namespace Workbench\App;

class JustSomeClass
{
    public function __construct(
        public bool $bool,
        public int $int,
        public float $float,
        public string $string,
        public array $array,
    ){}
}