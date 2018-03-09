<?php

    // get db connection
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR.'db-connect.php');

    $spec_date = false;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $spec_date = $_POST['spec_date'];
    }

    // check for url based access
    // split url
    $actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    if (strpos($actual_link, '?spec_date=') !== false) {
        $spec_date = explode('?spec_date=', $actual_link)[1];
        // basic format check
        if (count(explode('/',$spec_date)) != 3) {
            exit('improper date format, please use: mm/dd/yyyy');
        }
    }

    // set date
    date_default_timezone_set('America/Chicago');
    if (!$spec_date) {
        $date_now = date('m/d/Y h:i A', time());
        $date_now_parts = explode(" ", $date_now);
        $date_date = $date_now_parts[0];
    }
    else {
        $date_date = $spec_date;
    }

    // load data
    $stmt = $dbh->prepare('SELECT panel_id, date_date, date_time, actual_analog, computed FROM multi_panel_log WHERE date_date=:date_now ORDER BY id ASC');
    $stmt->bindParam(':date_now', $date_date, PDO::PARAM_STR);
    if ($stmt->execute()) {
        // set status
        $result = $stmt->fetchAll();
        $counter = 0;

        $panel_data = [];
        $panel_data['left_panel'] = [];
        $panel_data['right_panel'] = [];

        foreach($result as $row) {
            $counter++;
            // second time check
            $cur_hour = intval(explode(':', explode(' ',$row['date_time'])[0])[0]);
            // show all plots
            if ($cur_hour >= 8 && $cur_hour < 19) {

                if ($row['panel_id'] == 'le_panel') {
                    $panel_data['left_panel'][$row['date_time']] = str_replace(' V', '', $row['computed']);
                }
                else if ($row['panel_id'] == 'ri_panel') {
                    $panel_data['right_panel'][$row['date_time']] = str_replace(' V', '', $row['computed']);
                }

            }
        }

        // return json
        echo json_encode($panel_data);

    }

?>
