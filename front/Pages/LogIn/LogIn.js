function addListenerToLogInButton(callbackOnSuccess) {
    const buttonLogIn = document.querySelector("button#log-in");
    if (buttonLogIn) {
        buttonLogIn.addEventListener("click", async () => {
            const href = buttonLogIn.dataset.href;
            window.open(href, "_blank");
            startFetchingLogInStatus(callbackOnSuccess);
        });
    }
}

async function isOtterLoggedIn() {
    const fetchResponse = await fetch(`${tsm.url}is_otter_logged_in`);
    const data = await fetchResponse.json();
    return data.logged_in;
}

async function renderLogInHTML(element, callbackOnSuccess) {
    const fetchResponse = await fetch(`${tsm.url}get_log_in_html`);
    const data = await fetchResponse.text();
    element.innerHTML = data;
    addListenerToLogInButton(callbackOnSuccess);
}

function startFetchingLogInStatus(callbackOnSuccess) {
    const interval = setInterval(async () => {
        const isLoggedIn = await isOtterLoggedIn();
        if (isLoggedIn) {
            clearInterval(interval);
            if (typeof callbackOnSuccess === "function") {
                callbackOnSuccess();
            }
        }
    }, 1000);
}

addListenerToLogInButton(() => {
    window.location.reload();
});