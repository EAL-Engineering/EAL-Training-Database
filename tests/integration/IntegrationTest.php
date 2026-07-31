<?php

use PHPUnit\Framework\TestCase;

// Load the test database configuration, NOT the live one
require_once __DIR__ . '/../config.php';
// Load the functions we are testing
require_once __DIR__ . '/../../auth_functions.php';
require_once __DIR__ . '/../../common_functions.php';

class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        global $mysqli;
        
        // Start a transaction before every test
        $mysqli->begin_transaction();
        
        // Ensure a clean session state
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        global $mysqli;
        
        // Roll back the transaction after every test
        // This instantly deletes any fake data inserted during the test!
        $mysqli->rollback();
    }

    public function testCheckCertificationReturnsTrueForAuthorizedTrainer()
    {
        global $mysqli;

        // 1. Insert fake data for this specific test
        // Insert an operator
        $mysqli->query("INSERT INTO operators (seq_nmbr, name, status) VALUES (999, 'Test Operator', 'Active')");
        
        // Insert a trainer linked to that operator
        $mysqli->query("INSERT INTO trainers (seq_nmbr, login_name, optbl_ptr) VALUES (888, 'test_trainer', 999)");
        
        // Insert a certification rule allowing them to train certification #5
        $mysqli->query("INSERT INTO can_certify (trainer_ptr, cert_ptr) VALUES (999, 5)");

        // 2. Call the function
        // Does trainer 888 have permission to certify certification 5?
        $result = checkCertification(888, 5);

        // 3. Assert the result
        $this->assertTrue($result);
    }

    public function testCheckCertificationReturnsFalseForUnauthorizedTrainer()
    {
        global $mysqli;

        // Insert an operator and trainer, but DO NOT link them in can_certify
        $mysqli->query("INSERT INTO operators (seq_nmbr, name, status) VALUES (999, 'Test Operator', 'Active')");
        $mysqli->query("INSERT INTO trainers (seq_nmbr, login_name, optbl_ptr) VALUES (888, 'test_trainer', 999)");

        // Trainer 888 trying to certify certification 5 (which they are not allowed to)
        $result = checkCertification(888, 5);

        $this->assertFalse($result);
    }

    public function testBuildOperatorTrainingPulldownOutputsActiveOperators()
    {
        global $mysqli;

        // 1. Insert fake data
        // We add one active operator and one inactive operator to ensure the SQL filter works
        $mysqli->query("INSERT INTO operators (seq_nmbr, fname, name, status) VALUES (101, 'Active', 'Alice', 'Active')");
        $mysqli->query("INSERT INTO operators (seq_nmbr, fname, name, status) VALUES (102, 'Inactive', 'Bob', 'Inactive')");

        // 2. Capture the HTML output
        ob_start(); // Turn on output buffering
        
        // Call the function. We will name the select 'operator_select' and pre-select Alice (101)
        Build_Operator_Training_pulldown('operator_select', 101);
        
        $output = ob_get_clean(); // Turn off buffering and grab the captured HTML string

        // 3. Assertions
        // Check that the select box was named correctly
        $this->assertStringContainsString('<select name="operator_select"', $output);
        
        // Check that Active Alice is in the dropdown
        $this->assertStringContainsString('Active Alice', $output);
        
        // Check that Alice was pre-selected based on the argument we passed
        $this->assertStringContainsString('value="101" selected', $output);

        // Check that Inactive Bob was successfully filtered out by the WHERE clause
        $this->assertStringNotContainsString('Inactive Bob', $output);
    }
}
