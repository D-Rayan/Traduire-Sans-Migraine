function addListenerToLogInButton(callbackOnSuccess) {
    const buttonLogIn = document.querySelector("button#log-in");
    if (buttonLogIn) {
        buttonLogIn.addEventListener("click", async () => {
            const href = buttonLogIn.dataset.href;
            window.open(href, "_blank");
            startFetchingLogInStatus(callbackOnSuccess, buttonLogIn.dataset.wp_nonce);
        });
    }
}

async function isOtterLoggedIn(wpNonce) {
    const fetchResponse = await fetch(`${tsmVariables.url}is_otter_logged_in&wp_nonce=${wpNonce}`);
    const data = await fetchResponse.json();
    return data;
}

async function renderLogInHTML(element, callbackOnSuccess, wpNonce) {
    const fetchResponse = await fetch(`${tsmVariables.url}get_log_in_html&wp_nonce=${wpNonce}`);
    const data = await fetchResponse.text();
    element.innerHTML = data;
    addListenerToLogInButton(callbackOnSuccess);
}

function startFetchingLogInStatus(callbackOnSuccess, wpNonce) {
    const interval = setInterval(async () => {
        const {logged_in: isLoggedIn, wpNonce: newWpNonce} = await isOtterLoggedIn(wpNonce);
        if (isLoggedIn) {
            clearInterval(interval);
            if (typeof callbackOnSuccess === "function") {
                callbackOnSuccess();
            }
        } else {
            wpNonce = newWpNonce;
        }
    }, 1000);
}

addListenerToLogInButton(() => {
    window.location.reload();
});