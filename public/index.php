<?php
//error_reporting(0);
session_start();
if (isset($_SESSION["user_id"]) || (isset($_COOKIE["auth_token"]) && $_COOKIE["auth_token"] < time())) {
    header("Location: file tracking.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
  $username = $_POST["username"];
  $password = $_POST["password"];

  require_once('../includes/db_config.php');
  $connection = getDBConnection();

  $sql = "SELECT * FROM account WHERE user_id = ? OR name = ?";
  $stmt = $connection->prepare($sql);
  $stmt->bind_param("ss", $username, $username);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      if (password_verify($password, $row['password'])) {
        $_SESSION["user_id"] = $row['user_id'];
        $expires = time() + (24 * 3600);
        setcookie("auth_token", $username, $expires, "/");
        header("Location: file tracking.php");
        exit;
      } else {
          $loginError = "Invalid Username or Password.";
      }
  } else {
      $loginError = "Invalid Username or Password";
  }
  $stmt->close();
  $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/tracker.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/qrScan.css">

    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>File Tracking</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    
<div class="container" id="container">
	<div class="form-container sign-up-container">
		<form id="track2Form" action="" method="post">
			<h1>Track Transaction</h1>
			<div class="input-with-icon">
                <input type="text" id="publictrackno" name="track_id" placeholder="Enter tracking number" required oninput="trackidPattern(this)">
                <i class="fas fa-qrcode" onclick="QRScanner('#track2Form', 'cameraPopup', 'cam-public', 'publictrackno')"></i>
            </div><br><br>
            <!-- Add the reCAPTCHA widget -->
            <!-- <div class="g-recaptcha" data-sitekey="6LeIl7gpAAAAANGr67r6LGReceGV35zu25iILkT_"></div> -->
			<button>Search</button>
		</form>
	</div>
	<div class="form-container sign-in-container">
        <form class="Login-form" method="post" action>
			<h1>Sign in</h1>
			<input type="text" placeholder="Username" name="username" required autocomplete="on"/>
			<input type="password" placeholder="Password" name="password" required autocomplete="off"/>
			<a href="#">Forgot your password?</a>
			<button>Login</button>
            <?php
                if(!empty($loginError)) {
                    echo '<p style="color: red;">' . $loginError . '</p>';
                }
            ?>
		</form>
	</div>
	<div class="overlay-container">
		<div class="overlay">
			<div class="overlay-panel overlay-left">
				<h1>Welcome Back!</h1>
				<p>Sign in to your LGU-employee account.</p>
				<button class="ghost" id="signIn">Login</button>
			</div>
			<div class="overlay-panel overlay-right">
				<h1>Hello, Friend!</h1>
				<p>Track your Transaction here</p>
				<button class="ghost" id="signUp">Search</button>
			</div>
		</div>
	</div>
    <div class="popup-wrapper" id="cameraPopup" style="display: none;">
        <div class="popup">
            <span class="close-popup" onclick="closePopupWithCamera('cameraPopup')">&times;</span><br><br>
            <div id="cam-public"></div>
        </div>
    </div>
    <div class="popup-wrapper" id="trackerPublic" style="display: none;">
        <div class="popup">
            <span class="close-popup" onclick="closePopup('trackerPublic')">&times;</span>
            <div id="tracking-results"></div>
        </div>
    </div>
</div>

<script>
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('container');

    signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
    });

    signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
    });

    function verifyAndSearch() {
        const trackingNumberInput = document.getElementById('trackingNumber');
        const trackingNumber = trackingNumberInput.value.trim();
        
        // Check if the reCAPTCHA challenge is successfully completed
        const response = grecaptcha.getResponse();
        if (response === "") {
            alert("Please complete the reCAPTCHA challenge.");
            return;
        }

        if (trackingNumber === "") {
            alert("Please enter a tracking number.");
        } else {
            // Perform search action here
            console.log("Searching for tracking number:", trackingNumber);
            // You can replace the console.log with your actual search functionality
        }
    }
    $("#track2Form").submit(function(e) {
            e.preventDefault();
            var dataString = $(this).serialize();
            getTrack(dataString);
            $("#trackerPublic").show();
        });

        function closePopup(popupId) {
            document.getElementById(popupId).style.display = "none";
        }
</script>
<script src="js/tracker.js"></script>
<script src="js/qrScan.js"></script>
<script src="js/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</body>
</html>
