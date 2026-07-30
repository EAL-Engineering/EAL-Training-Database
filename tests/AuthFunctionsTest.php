<?php

use PHPUnit\Framework\TestCase;

// Include the file containing the pure functions
require_once __DIR__ . '/../auth_functions.php';

class AuthFunctionsTest extends TestCase
{
    public function testIsSafeRedirectAllowsRelativePaths()
    {
        $this->assertTrue(isSafeRedirect('index.php'));
        $this->assertTrue(isSafeRedirect('personnel_list.php?status=Active'));
        $this->assertTrue(isSafeRedirect('/images/logo.png'));
    }

    public function testIsSafeRedirectBlocksAbsoluteUrls()
    {
        $this->assertFalse(isSafeRedirect('http://evil.com'));
        $this->assertFalse(isSafeRedirect('https://google.com'));
        $this->assertFalse(isSafeRedirect('HTTP://EVIL.COM'));
    }

    // --- SET UP ---
    // This runs before EVERY test to give us a clean slate
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    // --- CSRF TESTS ---

    public function testGetCSRFTokenGeneratesNewToken()
    {
        // Session is empty, so this should generate a new token
        $token = getCSRFToken();
        
        $this->assertNotEmpty($token);
        // bin2hex(random_bytes(32)) results in a 64-character string
        $this->assertEquals(64, strlen($token)); 
        // Ensure it actually saved to the session
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testGetCSRFTokenReturnsExistingToken()
    {
        // Fake an existing session token
        $_SESSION['csrf_token'] = 'my_fake_token_123';
        
        // It should return the existing one, not generate a new one
        $this->assertEquals('my_fake_token_123', getCSRFToken());
    }

    public function testVerifyCSRFToken()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        
        // Test matching token
        $this->assertTrue(verifyCSRFToken('valid_token'));
        
        // Test wrong token
        $this->assertFalse(verifyCSRFToken('wrong_token'));
        
        // Test missing token
        $this->assertFalse(verifyCSRFToken(null));
    }

    public function testRegenerateCSRFToken()
    {
        $_SESSION['csrf_token'] = 'old_token';
        
        regenerateCSRFToken();
        
        // The token should no longer be the old one
        $this->assertNotEquals('old_token', $_SESSION['csrf_token']);
        $this->assertNotEmpty($_SESSION['csrf_token']);
    }

    public function testIsSafeRedirectRejectsEmptyStrings()
    {
        $this->assertFalse(isSafeRedirect(''));
    }

    // --- USER ROLE TESTS ---

    public function testGetUserRoleReturnsCorrectRole()
    {
        // Pretend an Admin (Role 2) is logged in
        $_SESSION['role_id'] = 2;
        $this->assertEquals(2, getUserRole());

        // Pretend a standard Trainer (Role 1) is logged in
        $_SESSION['role_id'] = 1;
        $this->assertEquals(1, getUserRole());
    }

    public function testGetUserRoleDefaultsToZero()
    {
        // Don't set $_SESSION['role_id'] at all
        $this->assertEquals(0, getUserRole());
    }

    // --- TIME EXPIRE TESTS ---

    public function testGetTimeUntilSessionExpires()
    {
        // Figure out what the server's max lifetime is (e.g., 7200 or 1440)
        $maxLifetime = (int)(ini_get('session.gc_maxlifetime') ?: 1440);

        // Scenario 1: Fresh session (activity just happened)
        $_SESSION['last_activity'] = time();
        $this->assertEquals($maxLifetime, getTimeUntilSessionExpires());

        // Scenario 2: Mid-way through session (60 seconds elapsed)
        $_SESSION['last_activity'] = time() - 60;
        $this->assertEquals($maxLifetime - 60, getTimeUntilSessionExpires());

        // Scenario 3: Completely expired session
        // Set last activity to 10 minutes past the expiration limit
        $_SESSION['last_activity'] = time() - ($maxLifetime + 600);
        
        // The function uses max(0, ...), so it should never return a negative number
        $this->assertEquals(0, getTimeUntilSessionExpires());
    }
}
