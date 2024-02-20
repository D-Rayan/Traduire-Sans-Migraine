function setButtonLoading(buttonSelector) {
    const button = document.querySelector(buttonSelector);
    button.classList.add('loading');
    button.disabled = true;
}

function stopButtonLoading(buttonSelector) {
    const button = document.querySelector(buttonSelector);
    button.classList.remove('loading');
    button.disabled = false;
}