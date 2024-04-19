<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();
    $sql = "SELECT * FROM office_list";
    if (isset($_POST['office_code'])) {
        $office_code = $_POST['office_code'];
        $sql .= " WHERE office_code = ?";
    }
    $stmt = $connection->prepare($sql);
    if (isset($_POST['office_code'])) {
        $stmt->bind_param("i", $office_code);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die("Query error: " . $connection->error);
    }
    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $office_code = $row["office_code"];
            $office_name = $row["office_name"];
    
            $data[] = array(
                "office_code" => $office_code,
                "office_name" => $office_name
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
