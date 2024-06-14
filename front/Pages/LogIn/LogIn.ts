import {CustomWindow} from "../../Main/WindowTSM";
declare let window: CustomWindow;

function addListenerToLogInButton(callbackOnSuccess: () => void) {
    const buttonLogIn = document.querySelector("button#log-in");
    if (buttonLogIn instanceof HTMLButtonElement) {
        buttonLogIn.addEventListener("click", async () => {
            const href = buttonLogIn.dataset.href;
            window.open(href, "_blank");
            startFetchingLogInStatus(callbackOnSuccess);
        });
    }
}

async function isOtterLoggedIn() {
    const fetchResponse = await fetch(`${window.tsmVariables.url}is_otter_logged_in`);
    const data = await fetchResponse.json();
    return data.logged_in;
}

export async function renderLogInHTML(element: HTMLElement, callbackOnSuccess: () => void) {
    const fetchResponse = await fetch(`${window.tsmVariables.url}get_log_in_html`);
    const data = await fetchResponse.text();
    element.innerHTML = data;
    addListenerToLogInButton(callbackOnSuccess);
}

function startFetchingLogInStatus(callbackOnSuccess: () => void) {
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