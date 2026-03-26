// Scripts para ContraGuard
document.addEventListener('DOMContentLoaded', function() {
    // Autohide alerts
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 3000);
});
