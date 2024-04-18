<?php
function getTimeZone($format) {
    // Set the default timezone to Asia/Manila
    date_default_timezone_set('Asia/Manila');
    
    $response = file_get_contents('http://worldtimeapi.org/api/timezone/Asia/Manila');
    $data = json_decode($response, true);
    $current_date_time = $data['datetime'];
    
    // Convert to MySQL datetime format
    $mysql_date_time = date('Y-m-d H:i:s', strtotime($current_date_time));
    $mysql_date = date('Y-m-d', strtotime($current_date_time));
    
    if ($format == 'dt') return $mysql_date_time;
    else if ($format == 'd') return $mysql_date;
    else return $current_date_time;
}
?>
