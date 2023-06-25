<?php

namespace TuCreusesOu\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigFunctions extends AbstractExtension {
    public function getFunctions() {
        return [
            new TwigFunction('example', [$this, 'example'])
        ];
    }

    public function example(): string {
        return '';
    }
}