<?php
$roles = array("Administrator", "Moderator", "User", "Liaison");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();
    require_once('getTimeZone.php');
    $dt = getTimeZone('dt');
    $d = getTimeZone('d');
    if (isset($_POST['addtransaction'])) {
        $payee = trim($_POST["payee"]);
        $trans_id = trim($_POST["trans_id"]);
        $particulars = $_POST["particulars"];
        $amount = $_POST["amount"];
        $additional_info = trim($_POST["additional_info"]);
        $trans_date = $_POST["trans_date"];
        $office = $_POST["office_code"];
        $sql_max_track_id = "SELECT MAX(CAST(RIGHT(track_id, 4) AS UNSIGNED)) AS max_track_id
            FROM file
            WHERE LEFT(track_id, 13) = ?";
        $stmt_max_track_id = $connection->prepare($sql_max_track_id);
        if ($stmt_max_track_id) {
            $track_id = (new DateTime($dt))->format('Ymd') . "-" . str_pad($office, 4, '0', STR_PAD_LEFT);
            $stmt_max_track_id->bind_param("s", $track_id);
            $stmt_max_track_id->execute();
            $stmt_max_track_id->bind_result($max_track_id);
            $stmt_max_track_id->fetch();
            $stmt_max_track_id->close();
        } else {
            echo "Error in preparing statement to retrieve max track_id: " . $connection->error;
        }
        $sql_insert_record = "INSERT INTO file (track_id, payee, trans_id, particulars, amount, additional_info, trans_date, created, lastupdate) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_insert_record = $connection->prepare($sql_insert_record);
        if ($stmt_insert_record) {
            $track_id .= "-" . str_pad($max_track_id + 1, 4, '0', STR_PAD_LEFT);
            $stmt_insert_record->bind_param("sssisssss", $track_id, $payee, $trans_id, $particulars, $amount, $additional_info, $trans_date, $dt, $dt);
            $stmt_insert_record->execute();
            if ($stmt_insert_record->affected_rows > 0) {
                echo $track_id;
            } else {
                echo "Failed to insert record.";
            }
            $stmt_insert_record->close();
        } else {
            echo "Error in preparing statement to insert record: " . $connection->error;
        }
    }
    else if (isset($_POST['addoffice'])) {
        $office_code = trim($_POST["office_code"]);
        $office_name = trim($_POST["office_name"]);

        $sql = "SELECT * FROM office_list WHERE office_code = '$office_code'";
        $result = $connection->query($sql);

        if ($result->num_rows != 0){echo "Office Already Existed";}
        else {
            $sql = "INSERT INTO office_list (office_code, office_name) VALUES (?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ss", $office_code, $office_name);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                echo $office_name;
            }
        }
    }
    else if (isset($_POST['updateoffice'])) {
        $office_code = $_POST["office_code"];
        $office_name = trim($_POST["office_name"]);
        $sql = "UPDATE office_list SET office_name = ? WHERE office_code = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ss", $office_code, $office_name);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            echo $office_name;
        }
    }
    else if (isset($_POST['deleteoffice'])) {
        $office_code = $_POST["office_code"];
        $sql = "DELETE FROM office_list WHERE office_code = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $office_code);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "Record deleted successfully";
        } else {
            echo "No record found for deletion";
        }
        $stmt->close();
    }
    else if (isset($_POST['addTransactionType'])) {
        $addTransactionTypeName = $_POST['addTransactionTypeName'];
        $steps = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'step') === 0) {
                $step_number = substr($key, 4);
                $steps[$step_number] = $value;
            }
        }
        $steps_json = json_encode($steps);
        $sql = "INSERT INTO transaction_type (trans_type, steps) VALUES (?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$addTransactionTypeName, $steps_json]);
        echo $addTransactionTypeName;
    }
    else if (isset($_POST['updateTransactionType'])) {
        $trans_id = $_POST['trans_id'];
        $updateTransactionTypeName = $_POST['updateTransactionTypeName'];
        $steps = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'step') === 0) {
                $step_number = substr($key, 4);
                $steps[$step_number] = $value;
            }
        }
        $steps_json = json_encode($steps);
        $sql = "UPDATE transaction_type SET trans_type = ?, steps = ? WHERE trans_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$updateTransactionTypeName, $steps_json, $trans_id]);
        echo $updateTransactionTypeName;
    }
    else if (isset($_POST['deleteTransactionType'])) {
        $trans_id = $_POST['trans_id'];
        $sql = "DELETE FROM transaction_type WHERE trans_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$trans_id]);
        if ($stmt->affected_rows > 0) {
            echo "Record deleted successfully";
        } else {
            echo "No record found for deletion";
        }
    }
    else if (isset($_POST['receive'])) {
        $track_id = $_POST['track_id'];
        $requestby = $_POST['requestby'];
        if (strlen($track_id) === 16) {
            $date = substr($track_id, 0, 8);
            $office = substr($track_id, 8, 4);
            $id = substr($track_id, 12, 4);
            $track_id = $date . '-' . $office . '-' . $id;
        } else {
            list($date, $office, $id) = explode("-", $track_id);
        }
        $sql = "SELECT * FROM file WHERE track_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $track_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['status'];
            $trans_id = $row['trans_id'];
            $step = $row['step'];
            if ($status == 1) {
                $sql = "SELECT * FROM transaction_type WHERE trans_id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("s", $trans_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $steps = json_decode($row['steps'], true);
                    $newstatus = 0;
                    if (!isset($steps[$step + 1])) $newstatus = 2;
                    if (isset($steps[$step]) && $steps[$step] == $requestby) {
                        $sql = "UPDATE file SET `status` = ?, `lastupdate` = ? WHERE track_id = ?";
                        $stmt = $connection->prepare($sql);
                        $stmt->bind_param("sss", $newstatus, $dt, $track_id);
                        $stmt->execute();
                        $sql = "UPDATE action SET `in` = ? WHERE track_id = ? AND `count` = (SELECT IFNULL(MAX(`count`), 0) FROM action WHERE track_id = ?)";
                        $stmt = $connection->prepare($sql);
                        if (!$stmt) {
                            die("Prepare failed: " . htmlspecialchars($connection->error));
                        }
                        $stmt->bind_param("sss", $dt, $track_id, $track_id);
                        $stmt->execute();
                        if ($stmt->error) {
                            die("Execute failed: " . htmlspecialchars($stmt->error));
                        }
                        $stmt->close();

                        echo $newstatus == 0 ? "Received!" : "Completed!";
                    } else {
                        echo "Not Allowed!";
                    }
                }
            }
        }
    }
    else if (isset($_POST['release'])) {
        $proceed = $_POST['release'];
        $track_id = $_POST['track_id'];
        if (strlen($track_id) === 16) {
            $date = substr($track_id, 0, 8);
            $office = substr($track_id, 8, 4);
            $id = substr($track_id, 12, 4);
            $track_id = $date . '-' . $office . '-' . $id;
        } else {
            list($date, $office, $id) = explode("-", $track_id);
        }
        $liaison_id = $_POST['liaison_id'];
        $comment = $_POST['comment'];
        $requestby = $_POST['requestby'];
        $sql = "SELECT * FROM file WHERE track_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $track_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['status'];
            $trans_id = $row['trans_id'];
            $step = $row['step'];
            if ($status == 2 && $proceed == 1) echo "Already Completed!";
            else if ($status == 0 || $status == 2) {
                $sql = "SELECT * FROM transaction_type WHERE trans_id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("s", $trans_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $steps = json_decode($row['steps'], true);
                    if ((isset($steps[$step]) && $steps[$step] == $requestby) || ($step == 0 && $requestby == intval($office))) {
                        $nextstep = $step + 1;
                        $newstep = $proceed == 1 ? $nextstep : $step - 1;
                        $sql = "UPDATE file SET `status` = 1, `step` = ?, `lastupdate` = ? WHERE track_id = ?";
                        $stmt = $connection->prepare($sql);
                        $stmt->bind_param("sss", $newstep, $dt, $track_id);
                        $stmt->execute();
                        $sql = "INSERT INTO action (track_id, office_rec, `out`, liaison, comment, proceed, `count`)
                        SELECT ?, ?, ?, ?, ?, ?, IFNULL(MAX(`count`), 0) + 1
                        FROM action
                        WHERE track_id = ?
                        ";
                        $stmt = $connection->prepare($sql);
                        $stmt->bind_param("sssssss", $track_id, $steps[$newstep], $dt, $liaison_id, $comment, $proceed, $track_id);
                        $stmt->execute();
                        echo $track_id;
                    } else {
                        echo "Not Allowed!";
                    }
                }
            }
        }
    }
    $connection->close();
}
?>