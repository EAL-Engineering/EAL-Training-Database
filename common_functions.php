<?php

declare(strict_types=1);

/**
 * Common utility functions for the Training Management System.
 *
 * This file contains helper functions used across various parts of the application.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

/**
 * Builds and displays a dropdown (<select>) menu of active operators.
 *
 * Queries the `operators` table for active operators and outputs an HTML `<select>`
 * menu with options corresponding to each operator's sequence number and name.
 *
 * @param string   $selectName Optional HTML name/id attribute for the select element. Defaults to 'selname'.
 * @param int|null $selectedId Optional operator sequence number to pre-select.
 *
 * @return void
 */
function Build_Operator_Training_pulldown(string $selectName = 'selname', ?int $selectedId = null): void
{
    global $mysqli;

    if (!$mysqli) {
        error_log("Database connection \$mysqli is not available in Build_Operator_Training_pulldown().");
        return;
    }

    $query = "SELECT seq_nmbr, fname, name FROM operators WHERE status = 'Active' ORDER BY name ASC";
    $result = $mysqli->query($query);

    if (!$result) {
        error_log("Query failed in Build_Operator_Training_pulldown(): " . $mysqli->error);
        return;
    }

    echo '<select name="' . htmlspecialchars($selectName) . '" id="' . htmlspecialchars($selectName) . '">';
    echo '<option value="">-- Select Operator --</option>';

    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['seq_nmbr'];
        $displayName = trim(($row['fname'] ?? '') . ' ' . ($row['name'] ?? ''));
        if ($displayName === '') {
            $displayName = $row['name'] ?? "Operator #$id";
        }

        $isSelected = ($selectedId !== null && $id === $selectedId) ? ' selected' : '';

        echo '<option value="' . htmlspecialchars((string)$id) . '"' . $isSelected . '>';
        echo htmlspecialchars($displayName);
        echo '</option>';
    }

    echo '</select>';
    $result->close();
}

/**
 * Validates that a redirect URL is a safe relative path on this site.
 *
 * @param string|null $url The URL to validate.
 *
 * @return bool True if the URL is safe to redirect to, false otherwise.
 */
function isSafeRedirect(?string $url): bool
{
    if ($url === null || $url === '') {
        return false;
    }

    return (bool) preg_match('/^[a-zA-Z0-9_\-][a-zA-Z0-9_\-\/\.]*(?:\?[^#]*)?$/', $url);
}
