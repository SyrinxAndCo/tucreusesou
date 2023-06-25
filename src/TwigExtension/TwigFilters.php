<?php

namespace TuCreusesOu\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigFilters extends AbstractExtension {
    public function getFilters() {
        return [
            new TwigFilter('example', [$this, 'example']),
        ];
    }

    public function example(string $val): string {
        return $val;
    }
}