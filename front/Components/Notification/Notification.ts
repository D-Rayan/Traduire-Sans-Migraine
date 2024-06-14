import {CustomWindow} from "../../Main/WindowTSM";

declare let window: CustomWindow;
export class TSM_Notification {
    static createNode(title: string, message: string, logo: string, type: string) {
        const notification = document.createElement('div');
        notification.classList.add('traduire-sans-migraine-notification', `traduire-sans-migraine-notification-${type}`);
        notification.innerHTML = `<div class="traduire-sans-migraine-notification__close">X</div>
                ${logo ? `<div class="traduire-sans-migraine-notification__logo">
                    <img src="${window.tsmVariables.assetsURI}${logo}" alt="Traduire Sans Migraine" width="58px">
                </div>` : ''}
                <div class="traduire-sans-migraine-notification__body">
                    <h3 class="traduire-sans-migraine-notification__title">${(window.tsmI18N && title in window.tsmI18N) ? window.tsmI18N[title] : title}</h3>
                    <p class="traduire-sans-migraine-notification__message">${(window.tsmI18N && message in window.tsmI18N) ? window.tsmI18N[message] : message}</p>
                </div>
        `;
        return notification;
    }

    static getContainer(location: Element) {
        if (!location.querySelector('.traduire-sans-migraine-notification-container')) {
            const container = document.createElement('div');
            container.classList.add('traduire-sans-migraine-notification-container');
            const opener = document.createElement('div');
            opener.classList.add('traduire-sans-migraine-notification-opener');
            opener.innerHTML = `<img src="${window.tsmVariables.assetsURI}loutre_ampoule.png" alt="Traduire Sans Migraine" width="58px">`;
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

    static removeContainerIfEmpty(location: Element) {
        const container = location.querySelector('.traduire-sans-migraine-notification-container');
        if (container && !container.hasChildNodes()) {
            location.removeChild(container);
        }
    }

    static displayContainerOpener(location: Element) {
        const container = location.querySelector('.traduire-sans-migraine-notification-container');
        if (container) {
            container.querySelector('.traduire-sans-migraine-notification-opener').classList.add('visible');
        }
    }

    static show(title: string, message: string, logo: string, type: string, persist = false, location = document.body) {
        const notification = TSM_Notification.createNode(title, message, logo, type);
        TSM_Notification.getContainer(location).prepend(notification);
        let timeOutId = null;
        if (!persist) {
            timeOutId = setTimeout(() => {
                notification.classList.add('traduire-sans-migraine-notification--hiddenAnimation');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                    TSM_Notification.removeContainerIfEmpty(location);
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
                TSM_Notification.removeContainerIfEmpty(location);
            } else {
                notification.classList.add('traduire-sans-migraine-notification--hiddenAnimation');
                setTimeout(() => {
                    notification.classList.remove('traduire-sans-migraine-notification--hiddenAnimation');
                    notification.classList.add('traduire-sans-migraine-notification--hidden');
                    TSM_Notification.displayContainerOpener(location);
                }, 800);
            }
        });
    }
}