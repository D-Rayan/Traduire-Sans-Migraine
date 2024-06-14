import {CustomWindow} from "../../Main/WindowTSM";

declare let window: CustomWindow;
function initModalEvents() {
    const modals = document.querySelectorAll('.traduire-sans-migraine-modal');
    modals.forEach(modal => {
        if (!(modal instanceof HTMLElement)) {
            return;
        }
        if (modal.dataset.initiliazed) {
            return;
        }
        modal.dataset.initiliazed = "true";
        const closeBtn = modal.querySelector('.traduire-sans-migraine-modal__content-header-close');
        closeBtn.addEventListener('click', () => {
            removeModal(modal.parentElement);
        });
    });
}

export function addModalToBody(modalHTML: string): HTMLElement {
    const div = document.createElement('div');
    div.innerHTML = modalHTML;
    div.id = `traduire-sans-migraine-modal-${Date.now()}`
    document.body.appendChild(div);
    initModalEvents();

    return document.querySelector(`#${div.id}`);
}

export function removeModal(modal: Element) {
    modal.remove();
}

export async function renderModal(title: string = "", message: string = "", button: Array<string> = []) {
    const fetchResponse = await fetch(`${window.tsmVariables.url}render_modal`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            title,
            message,
            button
        })
    });
    const data = await fetchResponse.text();
    return addModalToBody(data);
}