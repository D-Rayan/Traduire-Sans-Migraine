function setButtonLoading(buttonSelector: string | Element) {
    const button = typeof buttonSelector === "string" ? document.querySelector(buttonSelector) : buttonSelector;
    button.classList.add('loading');
    button.setAttribute('disabled', 'true');
}

function stopButtonLoading(buttonSelector: string | Element) {
    const button = typeof buttonSelector === "string" ? document.querySelector(buttonSelector) : buttonSelector;
    button.classList.remove('loading');
    button.setAttribute('disabled', 'false');
}