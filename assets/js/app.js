// assets/js/app.js  —  F1 AutoStart Panel

// Auto-dismiss flash alerts after 4 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Live lap time format hint
    const lapField = document.getElementById('best_lap_time');
    if (lapField) {
        const hint = lapField.nextElementSibling;
        lapField.addEventListener('input', () => {
            const valid = /^\d{2}:\d{2}\.\d{3}$/.test(lapField.value);
            if (lapField.value.length > 0) {
                lapField.style.borderColor = valid ? 'var(--green)' : 'var(--red)';
            } else {
                lapField.style.borderColor = '';
            }
        });
    }
});
