<?php
if (true) {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();
    $sql = "SELECT * FROM action";
    if (isset($_POST['track_id'])) {
        $track_id = $_POST['track_id'];
        $sql .= " WHERE track_id = ?";
    }
    else if (isset($_POST['office_rec'])) {
        $track_id = $_POST['office_rec'];
        $sql .= " WHERE office_rec = ?";
    }
    $stmt = $connection->prepare($sql);
    if (isset($_POST['track_id'])) {
        $stmt->bind_param("s", $track_id);
    }
    else if (isset($_POST['office_rec'])) {
        $stmt->bind_param("s", $office_rec);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die("Query error: " . $connection->error);
    }
    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $office_rec = $row["office_rec"];
            $in = $row["in"] == null ? "" : ((new DateTime($row["in"]))->format("F j, Y \a\\t g:i A"));
            $out = $row["out"] == null ? "" : ((new DateTime($row["out"]))->format("F j, Y \a\\t g:i A"));
            $liaison = $row["liaison"];
            $comment = $row["comment"];
            $proceed = $row["proceed"];
            $count = $row["count"];
    
            $data[] = array(
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
