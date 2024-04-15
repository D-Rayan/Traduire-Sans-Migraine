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