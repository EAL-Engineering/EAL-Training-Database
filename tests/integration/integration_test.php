<?php
/**
 * Integration test script for EAL Training app.
 *
 * Usage (environment variables or args):
 *   BASE_URL=https://inpp.ohio.edu/~leblanc/eal_2024 php integration_test.php 
 *   or
 *   php integration_test.php https://inpp.ohio.edu/~leblanc/eal_2024 username password operator_id
 *
 * The script will:
 *  - GET the login page and parse CSRF token
 *  - POST credentials to login and maintain cookies
 *  - GET personnel_edit.php?id=operator_id and parse CSRF token
 *  - POST to personnel_save.php with a small change and verify redirect/success
 */

date_default_timezone_set('UTC');

$argv0 = isset($argv[0]) ? $argv[0] : 'integration_test.php';

function usage() {
    global $argv0;
    echo "Usage:\n";
    echo "  BASE_URL=https://host/path php $argv0 USERNAME PASSWORD OPERATOR_ID\n";
    echo "Or pass args: php $argv0 https://host/path username password operator_id\n";
    exit(2);
}

// read inputs
if (isset($argv[1]) && strpos($argv[1], 'http') === 0) {
    $base = rtrim($argv[1], '/');
    $user = isset($argv[2]) ? $argv[2] : null;
    $pass = isset($argv[3]) ? $argv[3] : null;
    $operatorId = isset($argv[4]) ? $argv[4] : null;
} else {
    $base = rtrim(getenv('BASE_URL') ?: '', '/');
    $user = getenv('LOGIN_USER') ?: getenv('USERNAME');
    $pass = getenv('LOGIN_PASS') ?: getenv('PASSWORD');
    $operatorId = getenv('OPERATOR_ID');
}

// Dry-run mode is ON by default. Disable with DRY_RUN=0 or pass --live.
$dryRun = true;
if (getenv('DRY_RUN') === '0' || in_array('--live', $argv)) {
    $dryRun = false;
}
if ($dryRun) {
    echo "DRY RUN mode enabled (default) — no POSTs will be executed. Use --live or DRY_RUN=0 to run POSTs.\n";
} else {
    echo "LIVE mode enabled — destructive POSTs will be executed.\n";
}

// If a local test config exists at tests/config.php, load it and use values
$localConfig = __DIR__ . '/../config.php';
if (file_exists($localConfig)) {
    include $localConfig;
    // Expect variables: $TEST_BASE_URL, $TEST_LOGIN_USER, $TEST_LOGIN_PASS, $TEST_OPERATOR_ID
    if (empty($base) && !empty($TEST_BASE_URL)) $base = rtrim($TEST_BASE_URL, '/');
    if (empty($user) && !empty($TEST_LOGIN_USER)) $user = $TEST_LOGIN_USER;
    if (empty($pass) && !empty($TEST_LOGIN_PASS)) $pass = $TEST_LOGIN_PASS;
    if (empty($operatorId) && !empty($TEST_OPERATOR_ID)) $operatorId = $TEST_OPERATOR_ID;
    // Optional preferred trainer id configured in tests/config.php
    if (!empty($TEST_SELECTED_TRAINER)) {
        $PREFERRED_TRAINER = $TEST_SELECTED_TRAINER;
    }
}

if (empty($base) || empty($user) || empty($pass) || empty($operatorId)) {
    usage();
}

$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eal_integration_cookies_' . uniqid() . '.txt';

function httpGet($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EAL-Integration-Test/1.0');
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return [$info, $body, $err];
}

function httpPost($url, $postFields, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // inspect redirect manually
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EAL-Integration-Test/1.0');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    // Include headers so we can parse Location on redirect
    curl_setopt($ch, CURLOPT_HEADER, true);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);

    // Separate headers and body
    $header_size = isset($info['header_size']) ? $info['header_size'] : 0;
    $headers = $header_size ? substr($resp, 0, $header_size) : '';
    $body = $header_size ? substr($resp, $header_size) : $resp;

    return [$info, $body, $err, $headers];
}

function parseCsrf($html) {
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    // try single-quoted attributes in different order
    if (preg_match('/value=["\']([^"\']+)["\']\s+name=["\']csrf_token["\']/', $html, $m2)) {
        return $m2[1];
    }
    return null;
}

function parseSelectOptions($html, $selectName) {
    $opts = [];
    if (preg_match('/<select[^>]*name=["\']'.preg_quote($selectName,'/').'["\'][^>]*>(.*?)<\/select>/is', $html, $m)) {
        $inside = $m[1];
        if (preg_match_all('/<option[^>]*value=["\']([^"\']+)["\'][^>]*>(.*?)<\/option>/is', $inside, $oms, PREG_SET_ORDER)) {
            foreach ($oms as $o) {
                $opts[] = ['value' => $o[1], 'text' => trim(strip_tags($o[2]))];
            }
        }
    }
    return $opts;
}

echo "Integration test starting against $base\n";

// 1) GET login page
list($info, $body, $err) = httpGet($base . '/login.php', $cookieFile);
if ($err) { echo "cURL error: $err\n"; exit(2); }
if ($info['http_code'] !== 200) { echo "GET /login.php returned HTTP {$info['http_code']}\n"; exit(2); }
echo "- fetched login page\n";

$csrf = parseCsrf($body);
if (!$csrf) { echo "Failed to parse CSRF token from login page\n"; exit(2); }
echo "- parsed login CSRF token\n";

// 2) POST login
$loginUrl = $base . '/login.php';
$post = [
    'username' => $user,
    'password' => $pass,
    'csrf_token' => $csrf
];
list($info, $body, $err, $headers) = httpPost($loginUrl, $post, $cookieFile);
if ($err) { echo "cURL error during login: $err\n"; exit(2); }

// If login posts redirect, follow Location header by performing GET to Location if present
$loc = null;
if (!empty($headers) && preg_match('/^Location:\s*(.+)$/mi', $headers, $mloc)) {
    $loc = trim($mloc[1]);
}
if ($loc) {
    if (strpos($loc, 'http') !== 0) $loc = $base . '/' . ltrim($loc, '/');
    list($i2,$b2,$e2) = httpGet($loc, $cookieFile);
    $info = $i2; $body = $b2; $err = $e2;
}

// Check if we are authenticated by accessing index.php
list($i, $b, $e) = httpGet($base . '/index.php', $cookieFile);
if ($i['http_code'] !== 200) { echo "Login failed or index.php not accessible (HTTP {$i['http_code']})\n"; exit(2); }
echo "- login succeeded, index accessible\n";

// 3) GET personnel_edit to fetch CSRF for save
$editUrl = $base . '/personnel_edit.php?id=' . urlencode($operatorId);
list($i, $b, $e) = httpGet($editUrl, $cookieFile);
if ($i['http_code'] !== 200) { echo "GET personnel_edit.php returned HTTP {$i['http_code']}\n"; exit(2); }
echo "- fetched personnel_edit for id $operatorId\n";

$csrf_edit = parseCsrf($b);
if (!$csrf_edit) { echo "Failed to parse CSRF token from personnel_edit page\n"; exit(2); }
echo "- parsed edit CSRF token\n";

// 4) POST to personnel_save.php with small change
$saveUrl = $base . '/personnel_save.php';
// Prepare minimal required fields; reuse existing fields scraped from form would be best,
// but server requires several fields; we'll submit the required fields: seq_nmbr, name, fname, email, status, csrf_token
// Extract current values for name, fname, email, status from the edit page
function extractValue($html, $name) {
    if (preg_match('/name=["\']'.preg_quote($name,'/').'["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/<textarea[^>]*name=["\']'.preg_quote($name,'/').'["\'][^>]*>(.*?)<\/textarea>/s', $html, $m2)) return $m2[1];
    return '';
}

$seq = extractValue($b, 'seq_nmbr');
$namev = extractValue($b, 'name');
$fnamev = extractValue($b, 'fname');
$emailv = extractValue($b, 'email');
$statusv = extractValue($b, 'status');

if (empty($seq)) { $seq = $operatorId; }
// change the comments field to mark test
$comments = 'Integration test update ' . date('c');

$postData = [
    'seq_nmbr' => $seq,
    'name' => $namev,
    'fname' => $fnamev,
    'email' => $emailv,
    'altemail' => '',
    'phones' => '',
    'status' => $statusv ?: 'Active',
    'office' => '',
    'home' => '',
    'comments' => $comments,
    'csrf_token' => $csrf_edit
];

if ($dryRun) {
    echo "DRY RUN: would POST to $saveUrl with:\n" . print_r($postData, true) . "\n";
    // treat as successful in dry-run
    echo "- personnel_save POST skipped (dry-run)\n";
    echo "Proceeding to certification endpoint checks...\n";
} else {
    list($infoSave, $bodySave, $errSave, $headersSave) = httpPost($saveUrl, $postData, $cookieFile);
    if ($errSave) { echo "cURL error during save: $errSave\n"; exit(2); }

// If the POST returned a redirect, follow it and assert success marker on the final page
$location = null;
if (!empty($headersSave) && preg_match('/^Location:\s*(.+)$/mi', $headersSave, $mloc2)) {
    $location = trim($mloc2[1]);
}
if ($location) {
    if (strpos($location, 'http') !== 0) $location = $base . '/' . ltrim($location, '/');
    list($finalInfo, $finalBody, $finalErr) = httpGet($location, $cookieFile);
    if ($finalInfo['http_code'] !== 200) {
        echo "Followed redirect but final page returned HTTP {$finalInfo['http_code']}\n";
        @unlink($cookieFile);
        exit(2);
    }
    $finalText = strip_tags($finalBody);
    if (strpos($location, 'personnel_list.php') !== false || strpos($finalText, 'update_success') !== false || stripos($finalText, 'success') !== false) {
        echo "- personnel_save POST redirected and final page contains success marker\n";
        echo "INTEGRATION TESTS PASSED\n";
        // Continue with additional endpoint checks
        echo "Proceeding to certification endpoint checks...\n";
    } else {
        echo "Redirected final page did not contain expected success marker.\n";
        echo "Response snippet:\n" . substr($finalText,0,400) . "\n";
        @unlink($cookieFile);
        exit(2);
    }
    
    // CERTIFICATION ADD/SAVE check
    $certAddUrl = $base . '/certification_add.php?id=' . urlencode($operatorId);
    list($ci, $cb, $ce) = httpGet($certAddUrl, $cookieFile);
    if ($ci['http_code'] !== 200) { echo "GET certification_add.php returned HTTP {$ci['http_code']}\n"; @unlink($cookieFile); exit(2); }
    $csrf_cert = parseCsrf($cb);
    if (!$csrf_cert) { echo "Failed to parse CSRF token from certification_add page\n"; @unlink($cookieFile); exit(2); }
    echo "- parsed certification_add CSRF token\n";

    // parse available certifications and trainers
    $certOptions = parseSelectOptions($cb, 'cert_id');
    if (count($certOptions) === 0) { echo "No certifications available to add; skipping certification_add check.\n"; }
    else {
        $chosenCert = $certOptions[0]['value'];
        // trainers select is named 'completed_by'
        $trainerOptions = parseSelectOptions($cb, 'completed_by');
        $chosenTrainer = isset($trainerOptions[0]) ? $trainerOptions[0]['value'] : '';

        $certPost = [
            'operator_id' => $operatorId,
            'cert_id' => $chosenCert,
            'completed_by' => $chosenTrainer,
            'csrf_token' => $csrf_cert
        ];
        if ($dryRun) {
            echo "DRY RUN: would POST to certification_save.php with:\n" . print_r($certPost, true) . "\n";
            echo "- certification_save skipped (dry-run)\n";
        } else {
            list($cInfo, $cBody, $cErr, $cHeaders) = httpPost($base . '/certification_save.php', $certPost, $cookieFile);
            if ($cErr) { echo "cURL error during certification_save: $cErr\n"; @unlink($cookieFile); exit(2); }
        }
        // follow redirect if present
        $cLoc = null;
        if (!empty($cHeaders) && preg_match('/^Location:\s*(.+)$/mi', $cHeaders, $mlc)) { $cLoc = trim($mlc[1]); }
        if ($cLoc) {
            if (strpos($cLoc, 'http') !== 0) $cLoc = $base . '/' . ltrim($cLoc, '/');
            list($fI, $fB, $fE) = httpGet($cLoc, $cookieFile);
            $fText = strip_tags($fB);
            if ($fI['http_code'] === 200 && (strpos($cLoc, 'certification_add.php') !== false || stripos($fText, 'success') !== false)) {
                echo "- certification_save succeeded and redirected page contains success marker\n";
            } else {
                echo "Certification save redirect did not indicate success. Snippet:\n" . substr($fText,0,400) . "\n";
                @unlink($cookieFile); exit(2);
            }
        } else {
            $bodyText = strip_tags($cBody);
            if (stripos($bodyText, 'success') !== false) {
                echo "- certification_save returned success content\n";
            } else {
                echo "certification_save did not indicate success. Snippet:\n" . substr($bodyText,0,400) . "\n";
                @unlink($cookieFile); exit(2);
            }
        }
    }
    
    // TRAINER CERT ADD/REMOVE (optional): if we discovered a trainer id from cert add page, try add/remove
    if (!empty($chosenTrainer)) {
        echo "Proceeding to trainer certification add/remove checks for trainer id $chosenTrainer\n";
        // Need a CSRF token from trainer_edit.php for trainer actions
        $trainerEditUrl = $base . '/trainer_edit.php?id=' . urlencode($chosenTrainer);
        list($ti, $tb, $te) = httpGet($trainerEditUrl, $cookieFile);
        if ($ti['http_code'] !== 200) { echo "GET trainer_edit.php returned HTTP {$ti['http_code']}\n"; @unlink($cookieFile); exit(2); }
        $csrf_trainer = parseCsrf($tb);
        if (!$csrf_trainer) { echo "Failed to parse CSRF token from trainer_edit page\n"; @unlink($cookieFile); exit(2); }

        // Add certification to trainer
        $trainerAddPost = [
            'trainer_id' => $chosenTrainer,
            'cert_id' => $chosenCert,
            'csrf_token' => $csrf_trainer
        ];
        list($taInfo, $taBody, $taErr, $taHeaders) = httpPost($base . '/trainer_certification_add.php', $trainerAddPost, $cookieFile);
        if ($taErr) { echo "cURL error during trainer_certification_add: $taErr\n"; @unlink($cookieFile); exit(2); }
        // follow redirect and check message
        $taLoc = null; if (!empty($taHeaders) && preg_match('/^Location:\s*(.+)$/mi', $taHeaders, $mta)) $taLoc = trim($mta[1]);
        if ($taLoc) {
            if (strpos($taLoc, 'http') !== 0) $taLoc = $base . '/' . ltrim($taLoc, '/');
            list($fI2,$fB2,$fE2) = httpGet($taLoc, $cookieFile);
            $fText2 = strip_tags($fB2);
            if ($fI2['http_code'] === 200 && stripos($fText2, 'Certification added successfully') !== false) {
                echo "- trainer_certification_add succeeded (message found)\n";
            } else {
                echo "trainer_certification_add did not show expected success message. Snippet:\n" . substr($fText2,0,400) . "\n";
                @unlink($cookieFile); exit(2);
            }
        }

        // Now remove the certification we just added
        // Need new CSRF token from trainer_edit page
        list($ti2, $tb2, $te2) = httpGet($trainerEditUrl, $cookieFile);
        $csrf_trainer2 = parseCsrf($tb2);
        if (!$csrf_trainer2) { echo "Failed to parse CSRF token for removal from trainer_edit page\n"; @unlink($cookieFile); exit(2); }
        $trainerRemPost = [
            'trainer_id' => $chosenTrainer,
            'cert_id' => $chosenCert,
            'csrf_token' => $csrf_trainer2
        ];
        list($trInfo,$trBody,$trErr,$trHeaders) = httpPost($base . '/trainer_certification_remove.php', $trainerRemPost, $cookieFile);
        if ($trErr) { echo "cURL error during trainer_certification_remove: $trErr\n"; @unlink($cookieFile); exit(2); }
        $trLoc = null; if (!empty($trHeaders) && preg_match('/^Location:\s*(.+)$/mi', $trHeaders, $mtr)) $trLoc = trim($mtr[1]);
        if ($trLoc) {
            if (strpos($trLoc, 'http') !== 0) $trLoc = $base . '/' . ltrim($trLoc, '/');
            list($fI3,$fB3,$fE3) = httpGet($trLoc, $cookieFile);
            $fText3 = strip_tags($fB3);
            if ($fI3['http_code'] === 200 && stripos($fText3, 'Certification removed successfully') !== false) {
                echo "- trainer_certification_remove succeeded (message found)\n";
            } else {
                echo "trainer_certification_remove did not show expected message. Snippet:\n" . substr($fText3,0,400) . "\n";
                @unlink($cookieFile); exit(2);
            }
        }
    } else {
        echo "No trainer found in certification_add page; skipping trainer cert add/remove checks.\n";
    }

    // If we reached here, all checks passed
    echo "ALL INTEGRATION CHECKS PASSED\n";
    @unlink($cookieFile);
    exit(0);
        @unlink($cookieFile);
        exit(0);
    }
    echo "Redirected final page did not contain expected success marker.\n";
    echo "Response snippet:\n" . substr($finalText,0,400) . "\n";
    @unlink($cookieFile);
    exit(2);
} else {
    // No redirect; check the POST response body for success indicators
    $bodyText = strip_tags($bodySave);
    if (strpos($bodySave, 'update_success') !== false || stripos($bodyText, 'success') !== false) {
        echo "- personnel_save returned success content\n";
        echo "INTEGRATION TESTS PASSED\n";
        @unlink($cookieFile);
        exit(0);
    }
    echo "personnel_save did not indicate success (HTTP {$infoSave['http_code']}).\n";
    echo "Response snippet:\n" . substr($bodyText,0,400) . "\n";
    @unlink($cookieFile);
    exit(2);
}
