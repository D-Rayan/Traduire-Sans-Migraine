window.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.traduire-sans-migraine-alert__title-close');
    alerts.forEach(alert => {
        alert.addEventListener('click', () => {
            const parentNode = alert.parentNode.parentNode;
            parentNode.parentNode.removeChild(parentNode);
        });
    });
});

class Alert {
    static createNode(title: string, message: string, type: string) {
        const alert = document.createElement('div');
        alert.classList.add('notice', 'traduire-sans-migraine-alert', `traduire-sans-migraine-alert-${type}`);
        alert.innerHTML = `
                ${title ? `<div class="traduire-sans-migraine-alert__title">
                    <span class="traduire-sans-migraine-alert__title-text">${title}</span>
                    <span class="traduire-sans-migraine-alert__title-close">X</span>               
                </div>` : ''}
                <div class="traduire-sans-migraine-alert__body">${message}</div>
        `;
        return alert;
    }
}