function domReady(fn) {
    if (document.readyState === "complete" || document.readyState === "interactive") {
        setTimeout(fn, 1);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}

var htmlScanner;
function QRScanner(form, body, render, results, l_results) {
    $("#" + body).show();
    domReady(function () {
        const scannedResult = document.getElementById(results);
        const scannedl_Result = document.getElementById(l_results);
        function onScanSuccess(decodedText) {
            if (l_results == null) scannedResult.value = decodedText;
            if (/^(?:\d{8}-\d{4}-\d{4}|\d{16})$/.test(decodedText)) {
                hideCamera(body);
                scannedResult.value = decodedText;
                if (form != '#') $(form).submit();
            }
            else if (l_results != null && /\d{6}/.test(l_results)) {
                hideCamera(body);
                scannedl_Result.value = decodedText;
                if (form != '#') $(form).submit();
            }
            else {
                if (l_results != null) {
                    scannedl_Result.setCustomValidity("Invalid Code!");
                    scannedl_Result.reportValidity();
                }
                scannedResult.setCustomValidity("Invalid Code!");
                scannedResult.reportValidity();
            }
        }
        htmlScanner = new Html5QrcodeScanner(render, { fps: 10, qrbox: { height: 400, width: 400 } });
        htmlScanner.render(onScanSuccess);
    });
}
$("#QRScanner").click(function(e) {
    console.log('QR scanner clicked');
    
});

function hideCamera(body) {
    htmlScanner.clear();
    $("#" + body).hide();
}

function closePopupWithCamera(popupId) {
    document.getElementById(popupId).style.display = "none";
    htmlScanner.clear();
}

function trackidPattern(input) {
    var inputValue = input.value;
    var isValid = /^(?:\d{8}-\d{4}-\d{4}|\d{16})$/.test(inputValue);

    if (isValid) {
        input.setCustomValidity('');
    } else {
        input.setCustomValidity('Ex. 20240101-0001-0001');
    }
}