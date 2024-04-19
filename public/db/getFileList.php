<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();
    require_once('getTimeZone.php');
    $dt = getTimeZone('dt');
    
    $sql = "SELECT *, SUBSTRING(track_id, 10, 4) as office FROM file LEFT JOIN transaction_type ON file.trans_id = transaction_type.trans_id";
    
    if (isset($_POST['track_id'])) {
        $track_id = $_POST['track_id'];
        $sql .= " WHERE track_id = ?";
        
        // Prepare and bind parameters
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $track_id);
    } else {
        // For 'all' option
        $stmt = $connection->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        die("Query error: " . $connection->error);
    }
    
    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $track_id = $row["track_id"];
            $trans_id = $row["trans_id"];
            $payee = $row["payee"];
            $particulars = $row["particulars"];
            $amount = $row["amount"];
            $additional_info = $row["additional_info"];
            $created = (new DateTime($row["created"]))->format("F j, Y \a\\t g:i A");
            $status = $row["status"];
            $step = $row["step"];
            $office = $row["office"];
            $trans_date = $row["trans_date"];
            $trans_type = $row["trans_type"];
            $steps = $row["steps"];
            $lastupdate = $row["lastupdate"];
            $dif = strtotime($dt) - strtotime($row["lastupdate"]);
            $days = floor($dif / (60 * 60 * 24));
            $hours = floor(($dif % (60 * 60 * 24)) / (60 * 60));
            $minutes = floor(($dif % (60 * 60)) / 60);
            $elapsedTime = '';
            if ($days > 0) {
                $elapsedTime .= $days == 1 ? '1 day' : "$days days";
                if ($hours > 0) {
                    $elapsedTime .= " $hours hours";
                }
            } elseif ($hours > 0) {
                $elapsedTime .= $hours == 1 ? '1 hour' : "$hours hours";
                if ($minutes > 0) {
                    $elapsedTime .= " $minutes minutes";
                }
            } elseif ($minutes > 0) {
                $elapsedTime .= $minutes == 1 ? '1 minute' : "$minutes minutes";
            } else {
                $elapsedTime .= 'less than a minute';
            }
            $data[] = array(
                "track_id" => $track_id,
                "trans_id" => $trans_id,
                "payee" => $payee,
                "particulars" => $particulars,
                "amount" => $amount,
                "additional_info" => $additional_info,
                "created" => $created,
                "status" => $status,
                "lastupdate" => $lastupdate,
                "step" => $step,
                "office" => $office,
                "trans_date" => $trans_date,
                "trans_type" => $trans_type,
                "steps" => $steps,
                "elapsedTime" => $elapsedTime
            );
        }
    } else {
        $data["error"] = "No records found";
    }
    
    // Close connection
    $connection->close();
    
    // Set response header
    header('Content-Type: application/json');
    
    // Send JSON response
    echo json_encode($data);
} else {
    echo "Missing parameters";
}
?>
