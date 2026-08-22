<?php

/**
 * ELMS test runner.
 *
 * Usage:  php tests/run.php
 *
 * Loads each suite (which registers tests via elms_test()), executes them,
 * and exits non-zero if any fail (CI-friendly). No external dependencies.
 */

require __DIR__ . '/bootstrap.php';

$GLOBALS['__elms_tests'] = [];

function elms_test(string $name, callable $fn): void
{
    $GLOBALS['__elms_tests'][$name] = $fn;
}

foreach (['SignatureServiceTest', 'KeyGeneratorTest', 'ValidatorTest', 'DomainCleanerTest'] as $suite) {
    require __DIR__ . '/' . $suite . '.php';
}

$pass = 0;
$fail = 0;

foreach ($GLOBALS['__elms_tests'] as $name => $fn) {
    try {
        $fn();
        echo 'PASS  ' . $name . PHP_EOL;
        $pass++;
    } catch (\Throwable $e) {
        echo 'FAIL  ' . $name . '  ->  ' . $e->getMessage() . PHP_EOL;
        $fail++;
    }
}

echo PHP_EOL . $pass . ' passed, ' . $fail . ' failed' . PHP_EOL;
exit($fail === 0 ? 0 : 1);
