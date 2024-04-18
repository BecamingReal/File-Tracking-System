<?php
session_start();
if (!isset($_SESSION["user_id"]) && (!isset($_COOKIE["auth_token"]) || $_COOKIE["auth_token"] > time())) {
    header("Location: logout.php");
    exit;
}
require_once ('../includes/db_config.php');
$connection = getDBConnection();
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM account 
        INNER JOIN office_list ON (account.office = office_list.office_code)
        WHERE user_id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: logout.php");
    exit;
}

$row = $result->fetch_assoc();
$office_code = $row['office'];
$name = $row['name'];
$office_name = $row['office_name'];
$role = $row['role'];
$roleText = $role == 0 ? "Administrator" : ($role == 1 ? "Moderator" : ($role == 2 ? "User" : "Liaison"));
echo "<script>const office_code = '$office_code', office_name = '$office_name', role = '$role', name = '$name';</script>";
$menu = isset($_GET['menu']) ? $_GET['menu'] : '';
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <!-- Boxicons CSS -->
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <title>File Tracking</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.css">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>

    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tracker.css">
    <link rel="stylesheet" href="css/qrScan.css">
</head>

<body>
    <nav class="sidebar close">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="image/logo.png" alt="">
                </span>

                <div class="text logo-text">
                    <span class="name"><?php echo $name ?></span>
                    <span class="profession"><?php echo $roleText ?></span>
                    <span class="office"><?php echo $office_name ?></span>
                </div>
            </div>

            <i class='bx bx-chevron-right toggle'></i>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <li class="search-box">
                    <i class='bx bx-search icon'></i>
                    <input type="text" placeholder="Search...">
                </li>

                <ul class="menu-links">
                    <li class="nav-link">
                        <a href="#transaction" id="transactionLink">
                            <i class='bx bx-home-alt icon'></i>
                            <span class="text nav-text">Transaction</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="#management">
                            <i class='bx bx-buildings icon'></i>
                            <span class="text nav-text">Management</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="#Analytics">
                            <i class="fa-solid fa-chart-line icon"></i>
                            <span class="text nav-text">Analytics</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="#file-tracking">
                            <i class='bx bx-map icon'></i>
                            <span class="text nav-text">File Tracking</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="#Remarks">
                        <i class="fa-solid fa-comment icon"></i>
                            <span class="text nav-text">Remarks</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="#about">
                            <i class='bx bx-question-mark icon'></i>
                            <span class="text nav-text">About Us</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="#account">
                            <i class='bx bxs-user-account icon'></i>
                            <span class="text nav-text">Account</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="bottom-content">
                <li class="nav-link">
                    <a href="logout.php">
                        <i class='bx bx-log-out icon'></i>
                        <span class="text nav-text">Logout</span>
                    </a>
                </li>

                <li class="mode">
                    <div class="sun-moon">
                        <i class='bx bx-moon icon moon'></i>
                        <i class='bx bx-sun icon sun'></i>
                    </div>
                    <span class="mode-text text">Dark mode</span>

                    <div class="toggle-switch">
                        <span class="switch"></span>
                    </div>
                </li>
            </div>
        </div>
    </nav>


    <!-- Transaction Section -->
    <section id="transaction" class="home">
        <div class="text">Transaction Status</div>
        <div class="search_bar">
            <h3>Filters:</h3>
            <select id="officeSelect" onchange="table.column(0).search(this.value == '' ? '' : '-' + String(this.value).padStart(4, '0') + '-').draw()"></select>
            <select id="transTypeSelect" onchange="table.column(1).search(this.value == '' ? '' : '^' + this.value + '$', true, false).draw();"></select>
            <select id="searchStatusSelect" onchange="table.column(5).search(this.value == '' ? '' : '^' + this.value + '$', true, false).draw()">
                <option value="">All Status</option>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
                <option value="Ongoing">Ongoing</option>
            </select>

            <button id="newButtonId" onclick="openAddFilePopup()"><i class="fa-solid fa-file-circle-plus"></i> Add Transaction</button>

            <div class="popup-wrapper" id="addFilePopup">
                <div class="popup">
                    <span class="close-popup" onclick="closePopup('addFilePopup')">&times;</span>
                    <!-- Registration form -->
                    <form id="registrationForm" action="" method="post">
                        <h2>Registration Form</h2>
                        <label for="filetransactionType">Type of Transaction:</label>
                        <select id="filetransactionType" name="trans_id" required>
                            <option value="">Select Transaction Type</option>
                        </select><br><br>
                        <label for="filepayee">Payee:</label>
                        <input type="text" id="filepayee" name="payee" required><br><br>
                        <label for="fileparticulars">Particulars:</label>
                        <input type="text" id="fileparticulars" name="particulars" required><br><br>
                        <label for="fileamount">Amount:</label>
                        <input type="text" id="fileamount" name="amount" required><br><br>
                        <label for="fileadditional_info">Additional Information:</label>
                        <textarea id="fileadditional_info" name="additional_info" rows="2"></textarea><br><br>
                        <label for="filetransactiondate">Transaction Date:</label>
                        <input type="date" id="filetransactiondate" name="trans_date" required><br><br>
                        <input type="submit" value="Submit">
                    </form>
                </div>
            </div>
        </div>
        </div>

        </div>
        </div>

        <!-- Add your form or content for adding a new file here -->
        <div class="table_body display" id="TransactionContent" style="width:100%">
            <table id="transaction-table">
                <thead>

                    <tr>
                        <th>Transaction Number</th>
                        <th>Transaction Type</th>
                        <th>Payee</th>
                        <th>Transaction Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Last Modified</th>

                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

    </section>



    <!-- Management Section -->
    <section id="management" class="home">
        <div class="text">Management</div>
        <!-- Bottom links -->

        <div class="bottom-links">
            <a href="#NewOffice" onclick="openaddNewOfficePopup()"><i class="fa-solid fa-laptop"></i> Add Office</a>
            <a href="#UpdateOffice" onclick="openUpdateOfficePopup()"><i class="fa-solid fa-laptop"></i> Update
                Office</a>
            <a href="#DeleteOffice" onclick="openDeleteOfficePopup()"><i class="fa-solid fa-laptop"></i> Delete
                Office</a>
            <a href="#AddTransactionType" onclick="openAddTransactionTypePopup()"><i
                    class="fa-solid fa-folder-plus"></i> Add Transaction Type</a>
            <a href="#DeleteTransactionType" onclick="openDeleteTransactionTypePopup()()"><i
                    class="fa-solid fa-folder-plus"></i> Delete Transaction Type</a>
            <a href="#UpdateTransactionProcess" onclick="openupdatePopup()"><i class="fa-solid fa-rotate"></i> Update
                Transaction Process</a>
            <a href="#ReceiveTransactionFile" onclick="openreceivePopup()"><i class="fa-solid fa-file-import"></i>
                Receive Transaction File</a>
            <a href="#ReleaseTransactionFile" onclick="openreleasePopup()"><i class="fa-solid fa-file-export"></i>
                Release Transaction File</a>
        </div>

        <!-- Pop-up wrapper for Add Office -->
        <div class="popup-wrapper" id="addNewOfficePopup">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('addNewOfficePopup')">&times;</span>
                <h2>Add New Office</h2>
                <form id="addOfficeForm" action="" method="post">
                    <label for="addofficecode">Office Code:</label>
                    <input type="text" id="addofficecode" name="office_code" pattern="\d{4}" oninvalid="this.setCustomValidity('Ex. 0001')" required><br><br>
                    <label for="addofficename">Office Name:</label>
                    <input type="text" id="addofficename" name="office_name" required><br><br>
                    <input type="submit" value="Submit">
                </form>
            </div>
        </div>

        <!-- Pop-up wrapper for Update Office -->
        <div class="popup-wrapper" id="updateOfficePopup">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('updateOfficePopup')">&times;</span>
                <h2>Update Office</h2>
                <form id="updateOfficeForm" action="" method="post">
                    <label for="updateofficecode">Office Code:</label>
                    <input type="text" id="updateofficecode" name="office_code" pattern="\d{4}" oninvalid="this.setCustomValidity('Ex. 0001')" required>
                    <button type="button" id="retrieveDataButton">Retrieve Data</button><br><br>
                    <label for="updateofficename">Office Name:</label>
                    <input type="text" id="updateofficename" name="office_name" required><br><br>
                    <input type="submit" value="Update">
                </form>
            </div>
        </div>

        <!-- Pop-up wrapper for Delete Office -->
        <div class="popup-wrapper" id="deleteOfficePopup">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('deleteOfficePopup')">&times;</span>
                <h2>Delete Office</h2>
                <!-- Your registration form goes here -->
                <form id="deleteOfficeForm" action="" method="post">
                    <label for="deleteofficecode">Office Code:</label>
                    <input type="text" id="deleteofficecode" name="office_code" pattern="\d{4}" oninvalid="this.setCustomValidity('Ex. 0001')" required>
                    <input type="submit" value="Delete">
                </form>
            </div>
        </div>

        <!-- Pop-up wrapper for Add Transaction Type -->
        <div class="popup-wrapper" id="addTransactionTypePopup">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('addTransactionTypePopup')">&times;</span>
                <h2>Add Transaction Type</h2>

                <!-- Your registration form goes here -->
                <form id="addTransactionTypeForm" action="" method="post">
                    <label for="addTransactionTypeName">Transaction Name:</label>
                    <input type="text" id="addTransactionTypeName" name="addTransactionTypeName" required><br><br>
                    <div id="officeContainer"></div><br>
                    <button type="button" onclick="addOfficeStep('officeContainer')">Add Office</button><br><br>
                    <input type="submit" value="Add">
                </form>

                <script>
    let officeCounter = {
        'officeContainer': 1,
        'officeContainer2': 1
    };

    // Function to add a new office selection
    function addOfficeStep(container, preselect) {
        const officeContainer = document.getElementById(container);
        const selectContainer = document.createElement('div'); // Container for select and step
        const stepIndicator = document.createElement('span'); // Step indicator
        stepIndicator.textContent = 'Step ' + officeCounter[container] + ': ';
        selectContainer.appendChild(stepIndicator);

        const select = document.createElement('select');
        select.name = 'step' + officeCounter[container]; // Assign name based on step number
        select.required = true;
        // Adding options to the select element
        $.ajax({
            type: "POST",
            url: "db/getOfficeList.php",
            data: { all: "1" },
            success: function (response) {
                response.forEach(office => {
                    const option = document.createElement('option');
                    option.value = office.office_code;
                    option.text = "(" + String(office.office_code).padStart(4, '0') + ") " + office.office_name;
                    if (preselect == office.office_code) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        });
        // Adding a button to delete the office selection
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.textContent = 'Delete';
        deleteButton.onclick = function () {
            officeContainer.removeChild(selectContainer);
            updateStepNumbers(container); // Pass container to the function to update step numbers
        };
        // Appending select and delete button to the container
        selectContainer.appendChild(select);
        selectContainer.appendChild(deleteButton);
        officeContainer.appendChild(selectContainer);

        // Assign class based on step number
        selectContainer.classList.add('step' + officeCounter[container]);

        officeCounter[container]++;
    }

    // Function to update step numbers after deletion
    function updateStepNumbers(container) {
        const officeContainers = document.querySelectorAll('#' + container + ' > div'); // Use container parameter
        officeCounter[container] = 1;
        officeContainers.forEach(container => {
            const stepIndicator = container.querySelector('span');
            stepIndicator.textContent = 'Step ' + officeCounter[container.parentElement.id] + ': '; // Use parent container id
            // Update class based on step number
            container.className = container.className.replace(/\bstep\d+\b/, 'step' + officeCounter[container.parentElement.id]);
            // Update select name based on step number
            container.querySelector('select').name = 'step' + officeCounter[container.parentElement.id];
            officeCounter[container.parentElement.id]++;
        });
    }
</script>


            </div>
        </div>

        <!-- Pop-up wrapper for Update Transaction Process -->
        <div class="popup-wrapper" id="updateTransactionTypePopup" style="display: none;">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('updateTransactionTypePopup')">&times;</span>
                <h2>Update Transaction Process</h2>
                <!-- Your receive transaction file form goes here -->
                <form id="updateTransactionTypeForm" action="" method="post">
                    <label for="updatetrans_id">Transaction ID:</label>
                    <input type="text" id="updatetrans_id" name="trans_id" pattern="\d{4}" oninvalid="this.setCustomValidity('Ex. 0001')" required>
                    <button type="button" id="retrieveTransDataButton">Retrieve Data</button><br>
                    <span id="updateTransactionTypeNotice" style="color: red;"></span><br><br>
                    <label for="updateTransactionTypeName">Transaction Name:</label>
                    <input type="text" id="updateTransactionTypeName" name="updateTransactionTypeName" required><br><br>
                    <div id="officeContainer2"></div><br>
                    <button type="button" onclick="addOfficeStep('officeContainer2','')">Add Office</button><br><br>
                    <input type="submit" value="Update">
                </form>
            </div>
        </div>

        <!-- Pop-up wrapper for Delete Transaction Process -->
        <div class="popup-wrapper" id="deleteTransactionTypePopup" style="display: none;">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('deleteTransactionTypePopup')">&times;</span>
                <h2>Delete Transaction Type</h2>
                <!-- Your receive transaction file form goes here -->
                <form id="deleteTransactionTypeForm" action="" method="post">
                    <label for="deletetrans_id">Transaction ID:</label>
                    <input type="text" id="deletetrans_id" name="trans_id" pattern="\d{4}" oninvalid="this.setCustomValidity('Ex. 0001')" required>
                    <input type="submit" value="Delete">
                </form>
            </div>
        </div>

        <!-- Pop-up wrapper for Receive Transaction File -->
        <div class="popup-wrapper" id="receivePopup" style="display: none;">
            <div class="popup">
                <span class="close-popup" onclick="closePopupWithCamera('receivePopup')">&times;</span>
                <h2>Receive Transaction</h2>
                <form id="receiveTransactionForm" action="" method="post">
                    <label for="receivefileno">Tracking Number:</label>
                    <div class="input-with-icon">
                        <input type="text" id="receivefileno" name="track_id" placeholder="Enter tracking number" required oninput="trackidPattern(this)">
                        <i class="fas fa-qrcode" onclick="QRScanner('#receiveTransactionForm', 'body-Receive', 'cam-Receive', 'receivefileno', null)"></i>
                    </div><br><br>
                    <label id="body-Receive" style="display: none;">
                        <i class='bx bx-x-circle' onclick="hideCamera('body-Receive')"></i>
                        <div id="cam-Receive" style="margin-bottom: 20px;"></div>
                    </label>
                    <input type="submit" value="Receive">
                </form>
            </div>
        </div>
        <!-- Pop-up wrappers for Release Transaction File -->
        <div class="popup-wrapper" id="releasePopup">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('releasePopup')">&times;</span>
                <h2>Release Transaction</h2>
                <form id="releaseTransactionForm" action="" method="post">
                    <label for="releasefileno">Tracking Number:</label>
                    <div class="input-with-icon">
                        <input type="text" id="releasefileno" name="track_id" placeholder="Ex. 20240101-0001-0001" required oninput="trackidPattern(this)">
                        <i class="fas fa-qrcode" onclick="QRScanner('#', 'body-Release', 'cam-Release', 'releasefileno', null)"></i>
                    </div><br><br>
                    <label id="body-Release" style="display: none;">
                        <i class='bx bx-x-circle' onclick="hideCamera('body-Release')"></i>
                        <div id="cam-Release" style="margin-bottom: 20px;"></div>
                    </label>
                    <label for="releaseliaison_id">Liaison ID:</label>
                    <div class="input-with-icon">
                        <input type="text" id="releaseliaison_id" name="liaison_id" placeholder="Ex. 000001" required pattern="\d{6}" oninvalid="this.setCustomValidity('Ex. 000001')"><br><br>
                        <i class="fas fa-qrcode" onclick="QRScanner('#', 'body-Release', 'cam-Release', 'releasefileno', 'releaseliaison_id')" style="top:20px"></i>
                    </div><br><br>
                    <label for="release">Conclusion:</label>
                    <select type="select" id="release" name="release" required>
                        <option value="1" style="color: blue;"><strong>Proceed to next step</strong></option>
                        <option value="0" style="color: red;"><strong>Return to last step</strong></option>
                    </select><br><br>
                    <label for="releasecomment">Comment:</label>
                    <textarea id="releasecomment" name="comment" rows="2" cols="50"></textarea><br><br>
                    <input type="submit" value="Release">
                </form>
            </div>
        </div>
    </section>


    <!-- Analytics Section -->
    <section id="Analytics" class="home">
        <div class="text">Analytics</div>

    </section>


    <!-- File Tracking Section -->
    <section id="file-tracking" class="home">
        <div class="text">File tracking</div>
        <div style="align-items: center;">
            <form id="trackForm" action="" method="post">
                <div class="input-with-icon">
                    <input type="text" id="track_search" name="track_id" placeholder="Enter tracking number" required oninput="trackidPattern(this)">
                    <i class="fas fa-qrcode" onclick="QRScanner('#trackForm', 'body-search', 'cam-search', 'track_search', null)" style="right:20px"></i>
                </div><br><br>
                <button>Track</button>
            </form>
            <label id="body-search" style="display: none;">
                <i class='bx bx-x-circle' onclick="hideCamera('body-search')"></i>
                <div id="cam-search" style="margin-bottom: 20px;"></div>
            </label>
        </div>
        <div id="tracking-results"></div>
    </section>
    
<!-- Remarks -->
<section id="Remarks" class="home">
        <div class="text">Remarks</div>
</section>

    <!-- About Section -->
    <section id="about" class="home">
        <div class="text">About</div>
        <div class="about-content">
            <div class="person">
                <img src="image/2151035157.jpg" alt="John Michael Gabay">
                <div class="person-info">
                    <h2>John Michael Gabay</h2>
                    <p class="position">Developer</p>
                    <p class="description">LA SALLE UNIVERSITY - BSCPE</p>
                </div>
            </div>
            <!-- Add more person divs for other individuals -->
            <div class="person">
                <img src="image/2151035157.jpg" alt="Macky Boy Talisic">
                <div class="person-info">
                    <h2>Macky Boy Talisic</h2>
                    <p class="position">Developer</p>
                    <p class="description">LA SALLE UNIVERSITY - BSCPE</p>
                </div>
            </div>
            <div class="person">
                <img src="image/2151035157.jpg" alt="Nazel Arcayena">
                <div class="person-info">
                    <h2>Nazel Arcayena</h2>
                    <p class="position">Developer</p>
                    <p class="description">LA SALLE UNIVERSITY - BSCPE</p>
                </div>
            </div>
            <div class="person">
                <img src="image/2151035157.jpg" alt="Dexter Rejoy">
                <div class="person-info">
                    <h2>Dexter Rejoy</h2>
                    <p class="position">Supervisor</p>

                </div>
            </div>
        </div>
    </section>

    <!-- Acount Section -->
    <section id="account" class="home">
        <div class="text">Account</div>
        <div class="account-buttons">
            <a href="#" onclick=""><i class="fas fa-user"></i> Account List</a>
            <a href="#" onclick="openaddPopup()"><i class="fas fa-user-plus"></i> Add Account</a>
            <a href="#" onclick="openeditaPopup()"><i class="fas fa-edit"></i> Edit Account</a>
            <a href="#" onclick="opendeletePopup()"><i class="fas fa-trash-alt"></i> Delete Account</a>
        </div>
        <div class="popup-wrapper" id="addPopup" style="display: none;">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('addPopup')">&times;</span>
                <h2>Add New Account</h2>
                <!-- Your receive transaction file form goes here -->
                <!-- Example form -->
                <form id="AddAccountForm" action="" method="post">
                    <!-- Form fields -->
                    <label for="addname">Name of the account:</label>
                    <input type="text" id="addname" name="addname" required><br><br>
                    <div class="pass_show">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div><br><br>
                    <div class="pass_show">
                        <label for="confirmpassword">Confirm Password:</label>
                        <input type="password" id="confirmpassword" name="confirmpassword" required>
                    </div><br><br>
                    <label for="addaccount">Office:</label>
                    <select id="addoffice" name="addoffice" required>
                        <option value=1>Voucher</option>
                        <option value=2>Pay Roll</option>
                        <option value=3>Scholarship</option>
                    </select><br><br>
                    <label for="addaccount">Privilege:</label>
                    <select id="addrole" name="addrole" required>
                        <option value=0>Administrator</option>
                        <option value=1>Moderator</option>
                        <option value=2>User</option>
                        <option value=3>Liaison</option>
                    </select><br><br>
                    <input type="submit" value="Add Account">
                </form>
            </div>
        </div>

        <div class="popup-wrapper" id="editaPopup" style="display: none;">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('editaPopup')">&times;</span>
                <h2>Edit Account</h2>
                <!-- Your receive transaction file form goes here -->
                <!-- Example form -->
                <form action="/submit_edit_account" method="post">
                    <!-- Form fields -->
                    <label for="editaccount">Name of the account:</label>
                    <input type="text" id="editaccount" name="editaccount" required><br><br>
                    <label for="Password">Old Password:</label>
                    <input type="text" id="Password" name="Password" required><br><br>
                    <label for="Password">New Password:</label>
                    <input type="text" id="newPassword" name="newPassword" required><br><br>
                    <input type="submit" value="Edit Account">
                </form>
            </div>
        </div>

        <div class="popup-wrapper" id="deletePopup" style="display: none;">
            <div class="popup">
                <span class="close-popup" onclick="closePopup('deletePopup')">&times;</span>
                <h2>Delete Account</h2>
                <!-- Your receive transaction file form goes here -->
                <!-- Example form -->
                <form action="/submit_delete_transaction" method="post">
                    <!-- Form fields -->
                    <label for="DeletefileNumber">Name of Account:</label>
                    <input type="text" id="DeletefileNumber" name="DeletefileNumber" required><br><br>

                    <input type="submit" value="Delete">
                </form>
            </div>
        </div>
    </section>
    <script>

        $(document).ready(function () {
            $('.pass_show').append('<span class="ptxt"><i class="bx bx-show"></i></span>');
        });

        $(document).on('click', '.pass_show .ptxt', function () {
            var iconClass = $(this).find('i').attr('class');
            if (iconClass.includes('bx-show')) {
                $(this).find('i').removeClass('bx-show').addClass('bx-hide');
                $(this).prev().attr('type', 'text');
            } else {
                $(this).find('i').removeClass('bx-hide').addClass('bx-show');
                $(this).prev().attr('type', 'password');
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            const body = document.querySelector('body'),
                sidebar = body.querySelector('nav'),
                toggle = body.querySelector(".toggle"),
                modeSwitch = body.querySelector(".toggle-switch"),
                modeText = body.querySelector(".mode-text");

            // Function to show the default home section
            function showDefaultSection() {
                const defaultSection = document.getElementById("transaction");
                const sections = document.querySelectorAll('section');
                sections.forEach(section => {
                    section.style.display = 'none';
                });
                defaultSection.style.display = 'block';
            }

            // Show the default section on page load
            showDefaultSection();

            toggle.addEventListener("click", () => {
                sidebar.classList.toggle("close");
            });

            modeSwitch.addEventListener("click", () => {
                body.classList.toggle("dark");
                if (body.classList.contains("dark")) {
                    modeText.innerText = "Light mode";
                } else {
                    modeText.innerText = "Dark mode";
                }
            });

            const menuLinks = document.querySelectorAll('.menu-links a');
            const sections = document.querySelectorAll('section');

            menuLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href').substring(1);
                    const targetSection = document.getElementById(targetId);
                    if (targetSection) {
                        sections.forEach(section => {
                            section.style.display = 'none';
                        });
                        targetSection.style.display = 'block';
                    }
                });
            });
        });

        // Function to open the Add New File pop-up
        function openaddNewOfficePopup() {
            document.getElementById("addNewOfficePopup").style.display = "block";
        }

        function openUpdateOfficePopup() {
            document.getElementById("updateOfficePopup").style.display = "block";
        }

        function openDeleteOfficePopup() {
            document.getElementById("deleteOfficePopup").style.display = "block";
        }

        // Function to open the Add Transaction Type pop-up
        function openAddTransactionTypePopup() {
            document.getElementById("addTransactionTypePopup").style.display = "block";
        }
        // Function to open the Release Transaction File pop-up
        function openreleasePopup() {
            document.getElementById("releasePopup").style.display = "block";
        }
        function openreceivePopup() {
            document.getElementById("receivePopup").style.display = "block";
        }
        function openupdatePopup() {
            document.getElementById("updateTransactionTypePopup").style.display = "block";
        }
        function openDeleteTransactionTypePopup() {
            document.getElementById("deleteTransactionTypePopup").style.display = "block";
        }
        function openaddPopup() {
            document.getElementById("addPopup").style.display = "block";
            getOfficeList('addoffice', 'Select an office');
        }
        function openeditaPopup() {
            document.getElementById("editaPopup").style.display = "block";
        }

        function opendeletePopup() {
            document.getElementById("deletePopup").style.display = "block";
        }

        // Function to close a specific pop-up
        function closePopup(popupId) {
            document.getElementById(popupId).style.display = "none";
        }
        // Function to open the Add New File pop-up
        function openAddFilePopup() {
            document.getElementById("addFilePopup").style.display = "block";
            $.ajax({
                type: "POST",
                url: "db/getTransactionTypeList.php",
                data: { all: "1" },
                success: function (response) {
                    var selectElement = document.getElementById("filetransactionType");
                    selectElement.innerHTML = "<option value=''>Select Transaction Type</option>";

                    $.each(response, function (index, trans) {
                        if (trans.trans_type.trim() !== "") {
                            $("<option>").val(trans.trans_id).text(trans.trans_type).appendTo("#filetransactionType");
                        }
                    });
                }
            });
        }

        // Function to close a specific pop-up
        function closePopup(popupId) {
            document.getElementById(popupId).style.display = "none";
        }

        // Function to open the Release Transaction File pop-up
        function openreleasePopup() {
            document.getElementById("releasePopup").style.display = "block";
            // Display comment input box
            document.getElementById("comment").style.display = "block";
        }

    </script>
    <script src="js/qrScan.js"></script>
    <script src="js/management.js"></script>
    <script src="js/tracker.js"></script>
    <script src="js/account.js"></script>
    <script src="js/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

</body>

</html>