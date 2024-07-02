class Notification {
    static createNode(title, message, logo, type, buttons = []) {
        const notification = document.createElement('div');
        notification.classList.add('traduire-sans-migraine-notification', `traduire-sans-migraine-notification-${type}`);
        notification.innerHTML = `<div class="traduire-sans-migraine-notification__close">X</div>
                ${logo ? `<div class="traduire-sans-migraine-notification__logo">
                    <img src="${tsmVariables.assetsURI}${logo}" alt="Traduire Sans Migraine" width="58px">
                </div>` : ''}
                <div class="traduire-sans-migraine-notification__body">
                    <h3 class="traduire-sans-migraine-notification__title">${(tsmI18N && title in tsmI18N) ? tsmI18N[title] : title}</h3>
                    <p class="traduire-sans-migraine-notification__message">${(tsmI18N && message in tsmI18N) ? tsmI18N[message] : message}</p>
                    ${buttons.length ? `<div class="traduire-sans-migraine-notification__buttons"></div>` : ''}
                </div>
        `;
        if (buttons.length) {
            notification.querySelector(".traduire-sans-migraine-notification__buttons").append(...buttons);
        }
        return notification;
    }

    static getContainer(location) {
        if (!location.querySelector('.traduire-sans-migraine-notification-container')) {
            const container = document.createElement('div');
            container.classList.add('traduire-sans-migraine-notification-container');
            const opener = document.createElement('div');
            opener.classList.add('traduire-sans-migraine-notification-opener');
            opener.innerHTML = `<img src="${tsmVariables.assetsURI}loutre_ampoule.png" alt="Traduire Sans Migraine" width="58px">`;
            opener.addEventListener('click', () => {
                opener.classList.remove('visible');
                container.querySelectorAll('.traduire-sans-migraine-notification').forEach((notification) => {
                    notification.classList.remove('traduire-sans-migraine-notification--hidden');
                });
            });
            container.prepend(opener);
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

    static displayContainerOpener(location) {
        const container = location.querySelector('.traduire-sans-migraine-notification-container');
        if (container) {
            container.querySelector('.traduire-sans-migraine-notification-opener').classList.add('visible');
        }
    }

    static show(title, message, logo, type, persist = false, location = document.body, buttons = [], displayDefault = true) {
        const notification = Notification.createNode(title, message, logo, type, buttons);
        Notification.getContainer(location).prepend(notification);
        let timeOutId = null;
        if (!persist) {
            timeOutId = setTimeout(() => {
                notification.classList.add('traduire-sans-migraine-notification--hiddenAnimation');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                    Notification.removeContainerIfEmpty(location);
                }, 800);
            }, 8000);
        }
        notification.querySelector(".traduire-sans-migraine-notification__close").addEventListener('click', () => {
            if (!persist) {
                if (timeOutId) {
                    clearTimeout(timeOutId);
                }
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                Notification.removeContainerIfEmpty(location);
            } else {
                notification.classList.add('traduire-sans-migraine-notification--hiddenAnimation');
                setTimeout(() => {
                    notification.classList.remove('traduire-sans-migraine-notification--hiddenAnimation');
                    notification.classList.add('traduire-sans-migraine-notification--hidden');
                    Notification.displayContainerOpener(location);
                }, 800);
            }
        });
        if (!displayDefault && persist) {
            notification.classList.add('traduire-sans-migraine-notification--hidden');
            Notification.displayContainerOpener(location);
        }
    }
}