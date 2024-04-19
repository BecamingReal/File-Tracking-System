function getTrack(dataString) {
    $.ajax({
        type: "POST",
        url: "db/getFileList.php",
        data: dataString,
        success: function(response) {
            if (response.hasOwnProperty("error")) {
                if (response.error == "No records found") {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tracking Number Not Found!',
                        text: dataString.split('=')[1]
                    });
                }
            }
            else {
                var currentstep = response[0].step;
                var steps = JSON.parse(response[0].steps);
                var maxStep = Object.keys(steps).length;
                const formattedDate = new Date(response[0].trans_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                const formattedAmount = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP' }).format(response[0].amount);
                var status = response[0].status;
                var statusText = status === 0 ? "In Office" : status === 1 ? "En Route" : "Completed";
                var timeDifference = new Date() - new Date(response[0].lastupdate);
                var days = Math.floor(timeDifference / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeDifference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var elapsedTime = days > 0 ? `${days} days` : hours > 0 ? `${hours} hours` : "less than an hour";
                $('#tracking-results').empty();
                $('#tracking-results').html(`
                    <div class="tracking-step">
                        <div class="info">
                            <h2>Transaction Info</h2>
                            <div class="flex-container">
                                <div class="headerlabel">Tracking Number:</div>
                                <div class="detail">${response[0].track_id}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Transaction Type:</div>
                                <div class="detail">${response[0].trans_type}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Transaction Date:</div>
                                <div class="detail">${formattedDate}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Payee:</div>
                                <div class="detail">${response[0].payee}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Particulars:</div>
                                <div class="detail">${response[0].particulars}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Amount:</div>
                                <div class="detail">${formattedAmount}</div>
                            </div>
                            ${response[0].additional_info.trim() != "" ? `<div class="flex-container">
                                <div class="headerlabel">Additional Info:</div>
                                <div class="detail"><pre>${response[0].additional_info}</pre></div>
                            </div>` : ''}
                            <div class="flex-container">
                                <div class="headerlabel">Created On:</div>
                                <div class="detail">${response[0].created}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Status:</div>
                                <div class="detail">${statusText}</div>
                            </div>
                            <div class="flex-container">
                                <div class="headerlabel">Last Modified:</div>
                                <div class="detail">${response[0].elapsedTime}</div>
                            </div>
                        </div>
                    </div>
                    <div class="borderline"></div>
                `);
                console.log(dataString);
                $.ajax({
                    type: "POST",
                    url: "db/getAction.php",
                    data: dataString,
                    success: function(response) {
                        var count = 0;
                        if (response.hasOwnProperty("error")) {
                            if (response.error == "No records found") {
                                while(count < maxStep) {
                                    count++;
                                    appendStep({
                                        liaison: "",
                                        out: "",
                                        comment: "",
                                        office_rec: steps[count],
                                        in: "",
                                        count: count,
                                        proceed: 2}, "red");
                                    ;
                                }
                            }
                        }
                        else {
                            var totalcount = response.length;
                            var color = "green";
                            if (totalcount === 1) {
                                if (status == 1) color = "yellow";
                                appendStep(response[0], color);
                            } else {
                                response.forEach(function(step) {
                                    console.log(status + ":" + step.count + "= " + totalcount);
                                    if (step.count == totalcount && status == 1) color = "yellow";
                                    appendStep(step, color);
                                });
                            }
                            if (status != 2) {
                                count = currentstep;
                                while(count < maxStep) {
                                    count++;
                                    console.log("sss:"+steps[count]);
                                    appendStep({
                                        liaison: "",
                                        out: "",
                                        comment: "",
                                        office_rec: steps[count],
                                        in: "",
                                        count: count,
                                        proceed: 2}, "red");
                                    ;
                                }
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log('AJAX Error: ' + textStatus);
                        console.log('Error Thrown: ' + errorThrown);
                        console.log('Error: ' + jqXHR);
                    }
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX Error: ' + textStatus);
            console.log('Error Thrown: ' + errorThrown);
            console.log('Error: ' + jqXHR);
        }
    });
}

function appendStep(step, color) {
    $('#tracking-results').append(`
        <div class="info">
            <h3>Release</h3>
            <div class="flex-container">
                <div class="detaillabel">Timestamp:</div>
                <div class="detail">${step.out}</div>
            </div>
            <div class="flex-container">
                <div class="detaillabel">Liaison:</div>
                <div class="detail">${step.liaison != "" ? String(step.liaison).padStart(6, '0') : ""}</div>
            </div>
            <div class="flex-container">
                <div class="detaillabel">Conclusion:</div>
                <div class="detail">${step.proceed == 1 ? "PROCEED TO NEXT STEP" : step.proceed == 0 ? "RETURN TO LAST STEP" : ""}</div>
            </div>
        </div>
    </div>
    <div class="borderline"></div>
    <div class="tracking-step">
        <div class="officestop">${step.office_rec != "" ? String(step.office_rec).padStart(4, '0') : ""}</div>
        <div class="circle" style="background-color:${color};"></div>
        <div class="info">
            <h3>Receive</h3>
            <div class="flex-container">
                <div class="detaillabel">Timestamp:</div>
                <div class="detail">${step.in}</div>
            </div>
        </div>
    </div>
    `);
}