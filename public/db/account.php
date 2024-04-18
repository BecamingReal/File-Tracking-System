<?php
function isValidUsername($username)
{
    $usernamePattern = '/^[a-zA-Z0-9_]{3,20}$/'; // Allow letters, numbers, and underscores, 3-20 characters
    return preg_match($usernamePattern, $username) === 1;
}

function isValidName($name)
{
    // Check if the name contains only letters, spaces, hyphens, and apostrophes
    return preg_match("/^[A-Za-z-' ]+$/", $name) === 1;
}

function isValidPassword($password)
{
    // Check if the password contains any spaces
    return !strpos($password, ' ');
}

$roles = array("Administrator", "Moderator", "User", "Liaison");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once('../../includes/db_config.php');
    $connection = getDBConnection();

    if (isset($_POST['add'])) {
        $name = trim($_POST["addname"]);
        $password = $_POST["password"];
        $confirmpassword = $_POST["confirmpassword"];
        $office = $_POST["addoffice"];
        $role = $_POST["addrole"];

        $sql = "SELECT * FROM account WHERE name = '$name'";
        $result = $connection->query($sql);

        //if (!isValidName($name)){echo "Invalid Name";}
        if ($password == ""){echo "Input a password!";}
        else if (!isValidPassword($password)){echo "Password should have no spacing!";}
        else if ($password != $confirmpassword){echo "Input Password Mismatch";}
        else if ($result->num_rows != 0){echo "User Already Existed";}
        else {
            $password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO account (name, password, office, role) VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ssss", $name, $password, $office, $role);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $user_id = $stmt->insert_id;
                $office_name_query = "SELECT office_name FROM office_list WHERE office_id = ?";
                $office_stmt = $connection->prepare($office_name_query);
                $office_stmt->bind_param("i", $office);
                $office_stmt->execute();
                $office_stmt->bind_result($office_name);
                $office_stmt->fetch();
                $office_stmt->close();
                if ($office_name) {
                    $data[] = array(
                        "user_id" => $user_id,
                        "name" => $name,
                        "office_name" => $office_name,
                        "role" => $roles[$role]
                    );
                } else {
                    $data["error"] = "No records found";
                }
                header('Content-Type: application/json');
                echo json_encode($data);
                $stmt->close();
            }
        }
    }
    else if (isset($_POST['edit'])) {
        $selectedUser = explode(':', $_POST["selected-user"]);
        $editUsername = trim($_POST["edit-username"]);
        $editName = trim($_POST["edit-name"]);
        $oldpassword = $_POST["current-password"];
        $password = $_POST["new-password"];
        $confirmpassword = $_POST["confirm-password"];
        $authority = $_POST["edit-authority"];

        if ($AL == $selectedUser[2] && $AL == "Administrator")
            $authority = "Administrator";
        else if ($AL != "Administrator")
            $authority = $AL;

        $sql = "SELECT * FROM account WHERE user = '$selectedUser[0]' AND password = '$oldpassword'";
        $result1 = $connection->query($sql);

        $sql = "SELECT * FROM account WHERE user = '$editUsername'";
        $result2 = $connection->query($sql);

        if (!isValidUsername($editUsername))
            notice("info", "Invalid Username", "Try another username!");
        else if (!isValidName($editName))
            notice("info", "Invalid Name", "Try another name!");
        else if ($password == "")
            notice("info", "Invalid Password Input", "Input a password!");
        else if (!isValidPassword($password))
            notice("info", "Invalid Password Input", "Password should have no spacing!");
        else if ($password != $confirmpassword)
            notice("info", "Input Password Mismatch", "New password and confirm password is not the same.");
        else if ($result1->num_rows == 0)
            notice("error", "Wrong Password", "Current password input is incorrect!");
        else if ($result2->num_rows != 0 && $editUsername != $selectedUser[0])
            notice("error", "User Already Existed", "Try another username!");
        else {
            $sql = "UPDATE account SET
            user='$editUsername',
            name='$editName',
            password='$password',
            authority='$authority'
            WHERE user='$selectedUser[0]'";
            $result = $connection->query($sql);
            echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Update Successfully',
                                text: 'Click OK!',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'settings.php';
                                }
                            });
                        </script>";
            if ($user == $selectedUser[0])
                $_SESSION["user_id"] = $editUsername;
        }
    }
    else if (isset($_POST['delete'])) {
        $deleteUser = $_POST["user"];
        $sql = "DELETE FROM account WHERE user = '$deleteUser'";
        $result = $connection->query($sql);
    }
    else if (isset($_POST['acad'])) {
        $status = $_POST["status"];
        $sql = "UPDATE `settings` SET `STATUS` = '$status' WHERE `CONFIG` = 'academic_year'";
        $result = $connection->query($sql);
    }
}
?>