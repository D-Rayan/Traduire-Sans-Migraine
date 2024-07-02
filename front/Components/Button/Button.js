function setButtonLoading(buttonSelector) {
    const button = typeof buttonSelector === "string" ? document.querySelector(buttonSelector) : buttonSelector;
    button.classList.add('loading');
    button.disabled = true;
}

function stopButtonLoading(buttonSelector) {
    const button = typeof buttonSelector === "string" ? document.querySelector(buttonSelector) : buttonSelector;
    button.classList.remove('loading');
    button.disabled = false;
}

class Button {
    static createNode(text, type, onClick) {
        const button = document.createElement('button');
        button.classList.add('traduire-sans-migraine-button', `traduire-sans-migraine-button--${type}`);
        button.innerHTML = (tsmI18N && text in tsmI18N) ? tsmI18N[text] : text;
        button.addEventListener('click', () => onClick(button));
        return button;
    }
}