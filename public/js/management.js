getTransactionList();
var table;

function getTransactionList() {
    $.ajax({
        type: "POST",
        url: "db/getFileList.php",
        data: { all: "1" },
        success: function(response) {
            console.log(response);
            var transactionTableBody = $("#transaction-table tbody");
            transactionTableBody.empty();
            if (!response.hasOwnProperty("error")) {
                $.each(response, function(index, file) {
                    const formattedDate = new Date(file.trans_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                    const formattedAmount = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP' }).format(file.amount);
                    var status = file.status === 0 ? "In Office" : file.status === 1 ? "En Route" : "Completed";
                    var newRow = $("<tr index='"+index+"' class='data included'>");
                    newRow.append("<td>" + file.track_id + "</td>");
                    newRow.append("<td>" + file.trans_type + "</td>");
                    newRow.append("<td>" + file.payee + "</td>");
                    newRow.append("<td>" + formattedDate + "</td>");
                    newRow.append("<td>" + formattedAmount + "</td>");
                    newRow.append("<td>" + status + "</td>");
                    newRow.append("<td>" + file.elapsedTime + "</td>");
                    transactionTableBody.append(newRow);
                });
            }
            let existingTable = $('#transaction-table').DataTable();
            if (existingTable) {
                existingTable.destroy();
            }

            $.fn.dataTable.ext.type.order['custom-date-pre'] = function (data) {
                var date = new Date(data);
                return date.getTime();
            };

            $.fn.dataTable.ext.type.order['custom-time-duration-pre'] = function (data) {
                var timeInSeconds = 0;
                if (data.toLowerCase().includes('less than a minute')) {
                    timeInSeconds = 1;
                } else {
                    var regex = /(?:(\d+)\s*day(?:s)?\s*)?(?:(\d+)\s*hour(?:s)?\s*)?(?:(\d+)\s*minute(?:s)?\s*)?/;
                    var matches = data.match(regex);
                    if (matches) {
                        var days = parseInt(matches[1]) || 0;
                        var hours = parseInt(matches[2]) || 0;
                        var minutes = parseInt(matches[3]) || 0;
                        timeInSeconds = days * 24 * 60 * 60 + hours * 60 * 60 + minutes * 60;
                    }
                }
                return String(timeInSeconds).padStart(8, '0');
            };

            table = $('#transaction-table').DataTable({
                "language": {
                    "search": "Search Transaction:",
                    "searchPlaceholder": "Type to search..",
                    "lengthMenu": "Show _MENU_ Transactions",
                    "info": "Showing _START_ to _END_ of _TOTAL_ Transactions"
                },
                "columnDefs": [
                    {
                        "targets": 3,
                        "type": 'custom-date'
                    },
                    {
                        "targets": 6,
                        "type": 'custom-time-duration'
                    }
                ],
                "order": [[3, 'desc']]
            });

            $('#transaction-table tbody').on('dblclick', 'tr', function () {
                let index = $(this).attr('index');
                const formattedDate = new Date(response[index].trans_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                const formattedAmount = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP' }).format(response[index].amount);
                var status = response[index].status === 0 ? "In Office" : response[index].status === 1 ? "En Route" : "Completed";
                Swal.fire({
                    icon: 'info',
                    title: 'Transaction Info',
                    showCancelButton: true,
                    cancelButtonText: 'Ok',
                    cancelButtonColor: 'blue',
                    confirmButtonText: 'Download QR Code',
                    confirmButtonColor: 'orange',
                    html: `
                    <div class="tracking-step">
                        <div class="info">
                            <div class="flex-container">
                                <div class="headerlabel">Tracking Number:</div>
                                <div class="detail">${response[index].track_id}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Transaction Type:</div>
                                <div class="detail">${response[index].trans_type}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Transaction Date:</div>
                                <div class="detail">${formattedDate}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Payee:</div>
                                <div class="detail">${response[index].payee}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Particulars:</div>
                                <div class="detail">${response[index].particulars}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Amount:</div>
                                <div class="detail">${formattedAmount}</div>
                            </div>
                            ${response[0].additional_info.trim() != "" ? `<div class="flex-container">
                                <div class="headerlabel">Additional Info:</div>
                                <div class="detail"><pre>${response[index].additional_info}</pre></div>
                            </div>` : ''}
                            <div class="flex-container">
                                <div class="headerlabel">Created On:</div>
                                <div class="detail">${response[index].created}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Status:</div>
                                <div class="detail">${status}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Last Modified:</div>
                                <div class="detail">${response[index].elapsedTime}</div>
                            </div>
                        </div>
                    </div>
                `
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Get the QR code data from the response and remove dashes
        var qrData = response[index].replace(/-/g, '');
        
        // Create an anchor element
        var downloadLink = document.createElement("a");
        downloadLink.href = "https://api.qrserver.com/v1/create-qr-code/?data=" + qrData;
        downloadLink.download = "qr_code.png"; // Filename for the downloaded file

        // Append the anchor to the body
        document.body.appendChild(downloadLink);

        // Trigger the click event of the anchor
        downloadLink.click();

        // Remove the anchor from the body
        document.body.removeChild(downloadLink);
                    }
                });
            });
        }
    });
    getTransactionTypeList('transTypeSelect');
    getOfficeList('officeSelect', 'All Office');
}

$('#transaction-table tbody').on('mouseenter', 'tr', function () {
    $(this).addClass('highlight');
});
$('#transaction-table tbody').on('mouseleave', 'tr', function () {
    $(this).removeClass('highlight');
});

$("#registrationForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    console.log(dataString);
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&addtransaction=1&office_code=" + office_code,
        success: function(response) {
            let track_idn = response.replace(/-/g, '');
            let track_id = track_idn.replace(/(\d{8})(\d{4})(\d{4})/, "$1-$2-$3");
            $("#addFilePopup").hide();
            Swal.fire({
                icon: 'success',
                title: 'New Transaction Added!',
                html: `
                <div style="text-align: center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=${track_idn}" alt="Your Image" width="250" height="250">
                    <p>${track_id}</p>
                </div>
                `,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        }
    });
});

$("#retrieveDataButton").click(function(e) {
    e.preventDefault();
    if ($("#updateofficecode").prop("readonly")) {
        $("#updateofficecode").prop("readonly", false);
        $("#retrieveDataButton").text("Retrieve Data");
    }
    else {
        var officecode = $("#updateofficecode").val();
        $.ajax({
            type: "POST",
            url: "db/getOfficeList.php",
            data: { office_code: officecode },
            success: function(response) {
                if (response.hasOwnProperty("error")) {
                    if (response.error == "No records found") {
                        $("#updateOfficePopup").hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.error,
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $("#updateOfficePopup").show();
                            }
                        });
                    }
                }
                else {
                    $("#updateofficename").val(response[0].office_name);
                    $("#updateofficecode").prop("readonly", true);
                    $("#retrieveDataButton").text("Change Office");
                }
            }
        });
    }
});

$("#addOfficeForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&addoffice=1",
        success: function(response) {
            $("#addNewOfficePopup").hide();
            
            Swal.fire({
                icon: 'success',
                title: 'New Office Added!',
                text: response
            });
        }
    });
});

$("#updateOfficeForm").submit(function(e) {
    e.preventDefault();
    if ($("#updateofficecode").prop("readonly")) {
        var dataString = $(this).serialize();
        $.ajax({
            type: "POST",
            url: "db/management.php",
            data: dataString + "&updateoffice=1",
            success: function(response) {
                $("#updateOfficePopup").hide();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Office Info Updated!',
                    text: response
                });
            }
        });
    }
});

$("#deleteOfficeForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&deleteoffice=1",
        success: function(response) {
            $("#deleteOfficePopup").hide();
            Swal.fire({
                icon: 'success',
                title: 'Office Deleted!',
                text: response
            });
        }
    });
});

$("#retrieveTransDataButton").click(function(e) {
    e.preventDefault();
    if ($("#updatetrans_id").prop("readonly")) {
        $("#updatetrans_id").prop("readonly", false);
        $("#retrieveTransDataButton").text("Retrieve Data");
    }
    else {
        var transid = $("#updatetrans_id").val();
        console.log(transid);
        $.ajax({
            type: "POST",
            url: "db/getTransactionTypeList.php",
            data: { trans_id: transid },
            success: function(response) {
                if (response.hasOwnProperty("error")) {
                    if (response.error == "No records found") {
                        $("#updateTransactionTypePopup").hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.error,
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $("#updateTransactionTypePopup").show();
                            }
                        });
                    }
                }
                else {
                    console.log(response);
                    var steps = Object.entries(JSON.parse(response[0].steps));
                    $('#officeContainer2').empty();
                    officeCounter['officeContainer2'] = 1;
                    steps.forEach(function(step) {
                        addOfficeStep('officeContainer2', step[1]);
                    });
                    $("#updateTransactionTypeName").val(response[0].trans_type);
                    $("#updatetrans_id").prop("readonly", true);
                    $("#retrieveTransDataButton").text("Change Transaction Type");
                }
            }
        });
    }
});

$("#addTransactionTypeForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    console.log(dataString);
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&addTransactionType=1",
        success: function(response) {
            $("#addTransactionTypePopup").hide();
            
            Swal.fire({
                icon: 'success',
                title: 'New Transaction Type Added!',
                text: response
            });
        }
    });
});

$("#deleteTransactionTypeForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    console.log(dataString);
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&deleteTransactionType=1",
        success: function(response) {
            $("#deleteTransactionTypePopup").hide();
            
            Swal.fire({
                icon: 'success',
                title: 'Transaction Type Deleted!',
                text: response
            });
        }
    });
});

$("#updateTransactionTypeForm").submit(function(e) {
    e.preventDefault();
    if (!$("#updatetrans_id").prop("readonly")) {
        $("#updateTransactionTypeNotice").text("Selected Transaction Type First!");
    }
    else {
        var dataString = $(this).serialize();
        console.log(dataString);
        $.ajax({
            type: "POST",
            url: "db/management.php",
            data: dataString + "&updateTransactionType=1",
            success: function(response) {
                $("#updateTransactionTypePopup").hide();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Transaction Type Updated!',
                    text: response
                });
            }
        });
    }
});

$("#receiveTransactionForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    console.log(dataString);
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&receive=1&requestby=" + office_code,
        success: function(response) {
            $("#receivePopup").hide();
            console.log(response);
            Swal.fire({
                icon: 'success',
                title: 'Receive Transaction!',
                text: response
            });
        }
    });
});

$("#releaseTransactionForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    console.log(dataString);
    $.ajax({
        type: "POST",
        url: "db/management.php",
        data: dataString + "&requestby=" + office_code,
        success: function(response) {
            $("#releasePopup").hide();
            console.log(response);
            Swal.fire({
                icon: 'success',
                title: 'Release Transaction!',
                text: response
            });
        }
    });
});

$("#trackForm").submit(function(e) {
    e.preventDefault();
    var dataString = $(this).serialize();
    getTrack(dataString);
});

function getOfficeList(element, text) {
    $.ajax({
        type: "POST",
        url: "db/getOfficeList.php",
        data: { all: "1" },
        success: function(response) {
            var selectElement = document.getElementById(element);
            selectElement.innerHTML = "<option value=''>"+text+"</option>";
            
            $.each(response, function(index, office) {
            if (office.office_name.trim() !== "") {
                $("<option>").val(office.office_code).text("(" + String(office.office_code).padStart(4, '0') + ") " + office.office_name).appendTo("#" + element);
            }
        });
        }
    });
}

function getTransactionTypeList(element) {
    $.ajax({
        type: "POST",
        url: "db/getTransactionTypeList.php",
        data: { all: "1" },
        success: function (response) {
            var selectElement = document.getElementById(element);
            selectElement.innerHTML = "<option value=''>All Transaction Type</option>";

            $.each(response, function (index, trans) {
                if (trans.trans_type.trim() !== "") {
                    $("<option>").val(trans.trans_type).text(trans.trans_type).appendTo("#" + element);
                }
            });
        }
    });
}

$("#transactionLink").click(function(e) {
    e.preventDefault();
    getTransactionList();
});

$("#Remarks").click(function(e) {
    e.preventDefault();
    getRemarks();
});

function getRemarks() {
    $.ajax({
        type: "POST",
        url: "db/getAction.php",
        data: { office_rec: office_code },
        success: function (response) {
            console.log(response);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        }
    });
}