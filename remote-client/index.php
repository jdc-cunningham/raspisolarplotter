<?php

    // get url
    $actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    // continue
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR.'db-connect.php');

    $pos_submit_check = strpos($actual_link, '?action=submit_data');

    // parse url contents
    if ($pos_submit_check !== false) {

        // split url
        $url_parts = explode('http://raspisolarplotter.com/?action=submit_data', $actual_link);
        $url_payload = explode('&', $url_parts[1]);

        // secret key
        $secret_key = '';
        $second_key = ''; // replace ip check

        // check second key
        $url_split_for_key = explode('&second_key=', $actual_link)[1];
        $url_second_key = explode('&', $url_split_for_key)[0];
        if ($url_second_key != $second_key) {
            exit;
        }

        // check for key
        $pos_key_check = strpos($actual_link, '&key=');
        if ($pos_key_check !== false) {
            $key_supplied = explode('&key=', $actual_link)[1];
            if ($key_supplied != $secret_key) {
                exit;
            }
        }
        else {
            // key doesn't exist
            exit;
        }

        $id = null;
        $panel_id = '';
        $date_date = '';
        $date_time = '';
        $day = '';
        $actual_analog = 0;
        $computed = '';

        // for date
        function replaceDashWithSlash($inp_str) {
            return str_replace('-', '/', $inp_str);
        }

        // for time
        function replaceDashWithColon($inp_str) {
            $inp_str = str_replace('%20', ' ', $inp_str);
            return str_replace('-', ':', $inp_str);
        }

        foreach ($url_payload as $payload) {

            $payload_parts = explode('=', $payload);
            $data = $payload_parts[1];
            $key = $payload_parts[0];

            switch ($key) {

                // don't care about id
                case 'panel_id':
                    $panel_id = strip_tags($data);
                    break;
                case 'date_date':
                    $date_date = replaceDashWithSlash($data);
                    break;
                case 'date_time':
                    $date_time = replaceDashWithColon($data);
                    break;
                case 'day':
                    $day = $data;
                    break;
                case 'actual_analog':
                    $actual_analog = $data;
                    break;
                case 'computed':
                    $computed = urldecode($data);
                    break;
            }
        }

        $stmt = $dbh->prepare('INSERT INTO multi_panel_log VALUES (:id, :panel_id, :date_date, :date_time, :day, :actual_analog, :computed)');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':panel_id', $panel_id, PDO::PARAM_STR);
        $stmt->bindParam(':date_date', $date_date, PDO::PARAM_STR);
        $stmt->bindParam(':date_time', $date_time, PDO::PARAM_STR);
        $stmt->bindParam(':day', $day, PDO::PARAM_STR);
        $stmt->bindParam(':actual_analog', $actual_analog, PDO::PARAM_INT);
        $stmt->bindParam(':computed', $computed, PDO::PARAM_STR);
        if ($stmt->execute()) {
          $status = 'post success';
        }
        else {
          $status = 'post fail';
        }

        // echo $status;

    }

?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <title>Raspberry Pi Solar Plotter</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta description="This is a site that shows daily solar measurements from Raspberry Pi's as of 09-28-2017, there is currently only 1 Raspberry Pi connected to this site.">
        <!-- CSS Reset -->
        <link href="css-reset.css" rel="stylesheet">
        <!-- site CSS -->
        <link href="index.css" rel="stylesheet">
        <link href="responsive.css" rel="stylesheet">
        <!-- Load c3.css -->
        <link href="c3.css" rel="stylesheet">
        <!-- Load d3.js and c3.js -->
        <script src="d3.min.js" charset="utf-8"></script>
        <script src="c3.min.js"></script>
    </head>
    <body>
        <div id="main-container" class="flex f-c-t f-d-c">
            <div id="left-arrow" class="flex f-c-c nav-arrow" title="load previous day chart">
                <
            </div>
            <div id="right-arrow" class="flex f-c-c nav-arrow" title="load next day chart" style="display: none;">
                >
            </div>
            <div id="mc-greeting" class="flex f-c-c" style="display: none;">
                <h2 id="mcg-text"></h2>
            </div>
            <div id="mc-loading-msg" class="flex f-c-t f-d-c">
                <img src="white-blue-loading.gif" width="66px" height="66px">
                <br>
                <h4 id="mclm-text">Loading rays...</h4>
            </div>
            <div id="chart" style="display: none;"></div>
            <div id="mc-raw-data" style="display: none;"></div>
        </div>
        <script>
            // timing
            var chartReady = false;
            // client
            var clientW = Math.max(document.documentElement.clientWidth, window.innerWidth || 0),
                clientH = Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
                mcgTextTarget = document.getElementById('mcg-text');
            // set title
            if (clientW < 600) {
                mcgTextTarget.innerHTML = 'Raspi Solar Plotter - Kansas City, KS<br>Pi Zero 5V 100mA Solar Cell';
            }
            else {
                mcgTextTarget.innerHTML = 'Raspi Solar Plotter - Kansas City, KS - Pi Zero 5V 100mA Solar Cell';
            }
            // get data
            function httpGetAsync(theUrl, callback) {
                var xmlHttp = new XMLHttpRequest();
                xmlHttp.onreadystatechange = function() {
                    if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
                        callback(xmlHttp.responseText);
                }
                xmlHttp.open("GET", theUrl, true); // true for asynchronous
                xmlHttp.send(null);
            }
            function getChartData(chartData) {
                var chartDisp = document.getElementById('chart'),
                    mcLoad = document.getElementById('mc-loading-msg'),
                    mcGreet = document.getElementById('mc-greeting'),
                    chartValues = JSON.parse(chartData),
                    timeArray = [],
                    voltageArrayLeft = [],
                    voltageArrayRight = [];
                    voltageArrayLeft.push('left panel');
                    voltageArrayRight.push('right panel');
                for (var panel_id in chartValues) {
                    for (var sample_time in chartValues[panel_id]) {
                        if (panel_id == 'left_panel') {
                            voltageArrayLeft.push(chartValues[panel_id][sample_time]);
                        }
                        else {
                            voltageArrayRight.push(chartValues[panel_id][sample_time]);
                        }
                    }
                }
                // make chart
                var chart = c3.generate({
                    data: {
                        columns: [
                            // timeArray,
                            voltageArrayLeft,
                            voltageArrayRight
                        ],
                        type: 'bar',
                        colors: {
                            'left panel':'#ffffff',
                            'right panel':'#282828'
                        }
                    },
                    bar: {
                        width: {
                            ratio: 0.5 // this makes bar width 50% of length between ticks
                        }
                    }
                });

                // hide loading
                mcLoad.style.display = 'none';
                mcGreet.style.display = 'block';
                // show chart
                chartDisp.style.display = 'block';
                // update timing
                chartReady = true;

            }
            httpGetAsync('dual_get_data.php',getChartData);

            // get raw data
            function getRawData(rawData) {
                var rawDataValues = JSON.parse(rawData),
                    rawDataDisp = document.getElementById('mc-raw-data'),
                    curDate = '',
                    curAnalog = 0,
                    curComp = '',
                    panelID = '';
                // console.log(rawData);
                rawDataDisp.innerHTML += '<h2 id="rData-text">' + rawDataValues['title'] + '</h2>' + '<br><br>';
                
                function roundFcn(inpNum) {
                    return (Math.round((inpNum * 1000)/10)/100).toFixed(2);
                }

                for (var rData in rawDataValues['measurements']) {
                    panelID = rawDataValues['measurements'][rData]['panel_id'];
                    curDate = rawDataValues['measurements'][rData]['date'];
                    curAnalog = rawDataValues['measurements'][rData]['analog'];
                    curComp = rawDataValues['measurements'][rData]['computed'];
                    

		    // perform Watt calculation
		    // V = IR -> I = V/R
 		    // W = AV ->  W = I*V
		    var curVoltage = parseFloat(curComp.split(' V')[0]),
                        curCurrent = 0,
                        powerProduced = '';
		    curCurrent = roundFcn((curVoltage / 25)); // was 110
                    powerProduced = roundFcn((curCurrent * curVoltage)) + ' W'; 

                    rawDataDisp.innerHTML += curDate + '<br><br>' + 'Panel ID: ' + panelID + ' Analog: ' + curAnalog + '<br>' + 'Computed voltage: ' + curComp + ' Computed current: ' + (curCurrent * 1000).toFixed(2) + ' mA' + '<br>' + 'Power produced: ' + powerProduced + '<br><br>';
                }

                // show raw data display
                function checkReady() {
                    if (chartReady == true) {
                        rawDataDisp.style.display = 'block';
                    }
                    else {
                        // call again
                        setTimeout(function() {
                            checkReady();
                        }, 80);
                    }
                }
                checkReady();
            }
            httpGetAsync('dual_get_raw_data.php', getRawData);

            // left right arrow listeners
            const leftArrow = document.getElementById('left-arrow'),
                  rightArrow = document.getElementById('right-arrow');

            let rightArrowVis = false,
                curDate = new Date();

            // date state from Stack Overflow
            var today = new Date();
            var dd = today.getDate();
            var mm = today.getMonth()+1; //January is 0!

            var yyyy = today.getFullYear();
            if(dd<10){
                dd='0'+dd;
            } 
            if(mm<10){
                mm='0'+mm;
            } 
            var today = dd+'/'+mm+'/'+yyyy;

            // MDN post function
            function postAjax(url, data, success) {
                var params = typeof data == 'string' ? data : Object.keys(data).map(
                        function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
                    ).join('&');
                var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
                xhr.open('POST', url);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState>3 && xhr.status==200) { success(xhr.responseText); }
                };
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(params);
                return xhr;
            }

            function reloadData(dayDir) {
                let loadDay = changeDay(dayDir),
                    checkDate = new Date(loadDay),
                    todayDate = new Date();
                curDate = checkDate;
                // check to make sure not in the future
                if (curDate > todayDate) {
                    rightArrowVis = false;
                    rightArrow.style.display = 'none';
                    alert('future day');
                    return;
                }
                else {
                    // deal with showing right arrow
                    if (checkDate < todayDate) {
                        // show right arrow
                        if (!rightArrowVis) {
                            rightArrowVis = true;
                            rightArrow.style.display = 'flex';
                        }
                    }
                    else if (checkDate.getDate() == todayDate.getDate()) {
                        rightArrowVis = false;
                        rightArrow.style.display = 'none';
                    }
                }
                postAjax('dual_get_data.php', 'spec_date='+loadDay, function(data) {
                    getChartData(data);
                    // modify title
                    mcgTextTarget.innerText = 'Showing data for ' + addZero(curDate.getMonth() + 1) + '/' + addZero(curDate.getDate()) + '/' + curDate.getFullYear();
                });
            }

            // add zero function
            function addZero(inpNum) {
                    if (inpNum < 10) {
                        return '0' + inpNum;
                    }
                    else {
                        return inpNum;
                    }
                }

            // function to change days
            function changeDay(dir) {
                let dayMod = 0;
                if (dir == 'left') {
                    // subtract a day
                    dayMod = -1;
                }
                else {
                    // add a day
                    dayMod = 1;
                }
                let d = curDate,
                    epochTime = d.setDate(curDate.getDate() + dayMod),
                    nd = new Date(epochTime);
                return (addZero(nd.getMonth()+1) + '/' + addZero(nd.getDate()) + '/' + nd.getFullYear());
            }

            // could use class
            leftArrow.addEventListener('click', function() {
                reloadData('left');
            });

            rightArrow.addEventListener('click', function() {
                reloadData('right');
            });

        </script>
    </body>
</html>
