$("#AddAccountForm").submit(function(e) {
    e.preventDefault();
    
    var dataString = $(this).serialize();
    $.ajax({
        type: "POST",
        url: "db/account.php",
        data: dataString += "&" + "add=1",
        success: function(response) {
            $("#addPopup").hide();
            
            Swal.fire({
                icon: 'success',
                title: 'New Users Added',
                html: 'User ID: ' + response[0].user_id + '<br>' +
                    'Name: ' + response[0].name + '<br>' +
                    'Office: ' + response[0].office_name + '<br>' +
                    'Privilege: ' + response[0].role,
            });
        }
    });
});

function selectUser(value) {
    document.getElementById("edit-username").value = value[0];
    document.getElementById("edit-name").value = value[1];
    const editRole = document.getElementById("edit-role");
    if (value[2] == "Administrator") {
        editRole.style.display = "none";
    } else {
        editRole.style.display = "block";
    }

    var selectElement = document.getElementById("user-role-edit");
    for (var i = 0; i < selectElement.options.length; i++) {
        if (selectElement.options[i].value === value[2]) {
            selectElement.options[i].selected = true;
            break;
        }
    }
}

function confirmDelete() {
    const user = document.getElementById("userlist").value.split(":");
    if (user[2] == "Administrator") {
        Swal.fire({
            icon: 'error',
            title: 'Delete Declined',
            text: "Administrator Account can't be deleted!",
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'settings.php';
            }
        });
    }
    else {
        Swal.fire({
            title: 'Confirm Deletion',
            text: 'Are you sure you want to delete this Account?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const data = new URLSearchParams();
                data.append('delete', '1');
                data.append('user', user[0]);
                
                fetch('settings.php', {
                    method: 'POST',
                    body: data,
                    headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    },
                }).then(response => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted Successfully',
                        text: 'The account has been deleted',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'settings.php';
                        }
                    });
                });
            }
        });
    } 
}