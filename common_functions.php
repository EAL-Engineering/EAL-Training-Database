<?php

function build_operator_training_pulldown() {
    $operator_list = mysqli_query($mysqli, "SELECT * FROM operators WHERE status='Active' ORDER BY name");
    echo "<select name='selname'>";
    $row = $operator_list -> fetch_array(MYSQL_NUM);
    printf("%s (%s)\n", $row[0], $row[1]);

    // while ($operator = $operator_list -> fetch_array(MYSQLI_NUM)) {
    //     echo '<option value="' . $operator['seq_nmbr'] . '">' .$operator.['name'];
    // }
}

?>