<?php
if (true) {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();
    $sql = "SELECT 
    track_id, 
    TIMEDIFF(
            (SELECT `in` FROM `action` WHERE `track_id` = a.track_id AND `count` = (SELECT MAX(`count`) FROM `action` WHERE `track_id` = a.track_id)),
            (SELECT `out` FROM `action` WHERE `track_id` = a.track_id AND `count` = 1)
        ) AS action_time FROM (SELECT DISTINCT track_id FROM `action`) a";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die("Query error: " . $connection->error);
    }
    $durationOfTransaction = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $office_rec = $row["office_rec"];
            $in = $row["in"] == null ? "" : ((new DateTime($row["in"]))->format("F j, Y \a\\t g:i A"));
            $out = $row["out"] == null ? "" : ((new DateTime($row["out"]))->format("F j, Y \a\\t g:i A"));
            $liaison = $row["liaison"];
            $comment = $row["comment"];
            $proceed = $row["proceed"];
            $count = $row["count"];
    
            $durationOfTransaction[] = array(
                "office_rec" => $office_rec,
                "in" => $in,
                "out" => $out,
                "liaison" => $liaison,
                "comment" => $comment,
                "proceed" => $proceed,
                "count" => $count
            );
        }
    } else {
        $data["error"] = "No records found";
    }
    $connection->close();
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    echo "Missing parameters";
}
?>
