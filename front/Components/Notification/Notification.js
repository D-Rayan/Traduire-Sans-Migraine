class Notification {
    static createNode(title, message, logo, type) {
        const notification = document.createElement('div');
        notification.classList.add('traduire-sans-migraine-notification', `traduire-sans-migraine-notification-${type}`);
        notification.innerHTML = `
                ${logo ? `<div class="traduire-sans-migraine-notification__logo">
                    <img src="${tsmNotification.assetsURI}${logo}" alt="Traduire Sans Migraine" width="58px">
                </div>` : ''}
                <div class="traduire-sans-migraine-notification__body">
                    <h3 class="traduire-sans-migraine-notification__title">${(tsmI18N && title in tsmI18N) ? tsmI18N[title] : title}</h3>
                    <p class="traduire-sans-migraine-notification__message">${(tsmI18N && message in tsmI18N) ? tsmI18N[message] : message}</p>
                </div>
        `;
        return notification;
    }

    static getContainer(location) {
        if (!location.querySelector('.traduire-sans-migraine-notification-container')) {
            const container = document.createElement('div');
            container.classList.add('traduire-sans-migraine-notification-container');
            location.appendChild(container);
        }
        return location.querySelector('.traduire-sans-migraine-notification-container');
    }

    static removeContainerIfEmpty(location) {
        const container = location.querySelector('.traduire-sans-migraine-notification-container');
        if (container && !container.hasChildNodes()) {
            location.removeChild(container);
        }
    }

    static show(title, message, logo, type, location = document.body) {
        const notification = Notification.createNode(title, message, logo, type);
        Notification.getContainer(location).prepend(notification);
        setTimeout(() => {
            notification.classList.add('traduire-sans-migraine-notification--hidden');
            setTimeout(() => {
                notification.parentNode.removeChild(notification);
                Notification.removeContainerIfEmpty(location);
            }, 500);
        }, 5000);
    }
}