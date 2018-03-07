<?php

    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR.'db-connect.php');

    $return = [];

    $return['title'] = "Raw data - showing last 15 measurements";
    $return['measurements'] = [];

    $stmt = $dbh->prepare('SELECT id, panel_id, date_date, date_time, day, actual_analog, computed FROM multi_panel_log ORDER BY id DESC LIMIT 15');
    if ($stmt->execute()) {
        $result = $stmt->fetchAll();
        $counter = 0;
        foreach($result as $row) {
            $counter = $counter + 1;
            $return['measurements'][$counter] = [];
            $return['measurements'][$counter]['panel_id'] = $row['panel_id'];
            $return['measurements'][$counter]['date'] = $row['day'] . ' ' . $row['date_date'] . ' ' . $row['date_time'];
            $return['measurements'][$counter]['analog'] = $row['actual_analog'];
            $return['measurements'][$counter]['computed'] = $row['computed'];
        }

        echo json_encode($return);
    }

?>
