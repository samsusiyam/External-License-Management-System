<?php

/**
 * ELMS test bootstrap (no external dependencies).
 *
 * Loads the application autoloader/config so tests can use the App\ namespace,
 * then defines lightweight assertion helpers usable by the test suites.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

// --- Assertion helpers ------------------------------------------------------
// Kept deliberately tiny; each throws on failure so the runner can catch it.

function assert_true($cond, string $msg = ''): void
{
    if (!$cond) {
        throw new \Exception($msg ?: 'assert_true failed');
    }
}

function assert_false($cond, string $msg = ''): void
{
    if ($cond) {
        throw new \Exception($msg ?: 'assert_false failed');
    }
}

function assert_equals($expected, $actual, string $msg = ''): void
{
    if ($expected != $actual) {
        throw new \Exception($msg ?: sprintf('expected %s, got %s', var_export($expected, true), var_export($actual, true)));
    }
}

function assert_not_equals($expected, $actual, string $msg = ''): void
{
    if ($expected == $actual) {
        throw new \Exception($msg ?: 'values are equal (expected different)');
    }
}

function assert_string_contains(string $haystack, string $needle, string $msg = ''): void
{
    if (strpos($haystack, $needle) === false) {
        throw new \Exception($msg ?: sprintf("'%s' not found in '%s'", $needle, $haystack));
    }
}

function assert_matches(string $pattern, string $subject, string $msg = ''): void
{
    if (preg_match($pattern, $subject) !== 1) {
        throw new \Exception($msg ?: sprintf("'%s' does not match pattern %s", $subject, $pattern));
    }
}
