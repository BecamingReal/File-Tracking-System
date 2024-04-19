<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();
    $sql = "SELECT * FROM transaction_type";
    if (isset($_POST['trans_id'])) {
        $trans_id = $_POST['trans_id'];
        $sql .= " WHERE trans_id = ?";
    }
    $stmt = $connection->prepare($sql);
    if (isset($_POST['trans_id'])) {
        $stmt->bind_param("i", $trans_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die("Query error: " . $connection->error);
    }
    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trans_id = $row["trans_id"];
            $trans_type = $row["trans_type"];
            $steps = $row["steps"];
    
            $data[] = array(
                "trans_id" => $trans_id,
                "trans_type" => $trans_type,
                "steps" => $steps
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
