<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->ignoreUnreadableDirs()
    ->exclude([
        __DIR__ . '/tests/Integration/ScriptRuntime/_non_writable'
    ])
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->finder($finder)
    ;