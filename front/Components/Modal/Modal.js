function initModalEvents() {
    const modals = document.querySelectorAll('.traduire-sans-migraine-modal');
    modals.forEach(modal => {
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

function addModalToBody(modal) {
    const div = document.createElement('div');
    div.innerHTML = modal;
    div.id = `traduire-sans-migraine-modal-${Date.now()}`
    document.body.appendChild(div);
    initModalEvents();

    return document.querySelector(`#${div.id}`);
}

function removeModal(modal) {
    modal.remove();
}

async function renderModal(title = "", message = "", button = []) {
    const fetchResponse = await fetch(`${tsmVariables.url}render_modal`, {
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