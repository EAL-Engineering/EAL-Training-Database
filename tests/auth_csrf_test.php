<?php
// Simple CLI tests for CSRF helpers in auth.php

chdir(__DIR__ . '/..'); // make includes work when running from tests dir

require_once 'auth.php';

function assert_true($cond, $msg) {
    if (!$cond) {
        echo "FAIL: $msg\n";
        exit(2);
    }
}

echo "Running CSRF helper tests...\n";

// Ensure session is available
if (session_status() == PHP_SESSION_NONE) session_start();

$t1 = getCSRFToken();
assert_true(is_string($t1) && strlen($t1) >= 32, "getCSRFToken() must return a token string");
echo "- getCSRFToken OK (len=" . strlen($t1) . ")\n";

assert_true(verifyCSRFToken($t1) === true, "verifyCSRFToken() should validate current token");
echo "- verifyCSRFToken OK\n";

$t_before = $t1;
$t2 = regenerateCSRFToken();
assert_true(is_string($t2) && strlen($t2) >= 32, "regenerateCSRFToken() must return a token string");
assert_true($t2 !== $t_before, "regenerateCSRFToken() should produce a new token");
echo "- regenerateCSRFToken OK (rotated)\n";

assert_true(verifyCSRFToken($t2) === true, "verifyCSRFToken() should validate new token");
assert_true(verifyCSRFToken($t_before) === false, "old token must no longer validate after rotation");
echo "- rotation validation OK\n";

echo "ALL CSRF HELPER TESTS PASSED\n";
exit(0);

?>
