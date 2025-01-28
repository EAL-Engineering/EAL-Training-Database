<?php
/**
 * Common utility functions for the Training Management System.
 *
 * This file contains helper functions used across various parts of the application.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

/**
 * Builds a dropdown (pulldown) menu of active operators for training purposes.
 *
 * This function queries the `operators` table for active operators and generates
 * an HTML `<select>` dropdown menu with their names. Each option corresponds to
 * an operator's unique sequence number.
 *
 * @return void
 *
 * @throws mysqli_sql_exception If the database query fails.
 */
function Build_Operator_Training_pulldown()
{
    $operator_list = mysqli_query($mysqli, "SELECT * FROM operators WHERE status='Active' ORDER BY name");
    echo "<select name='selname'>";
    $row = $operator_list -> fetch_array(MYSQL_NUM);
    printf("%s (%s)\n", $row[0], $row[1]);

    // while ($operator = $operator_list -> fetch_array(MYSQLI_NUM)) {
    //     echo '<option value="' . $operator['seq_nmbr'] . '">' .$operator.['name'];
    // }
}

?>
