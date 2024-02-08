window.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.traduire-sans-migraine-alert__title-close');
    alerts.forEach(alert => {
        alert.addEventListener('click', () => {
            const parentNode = alert.parentNode.parentNode;
            parentNode.parentNode.removeChild(parentNode);
        });
    });
});