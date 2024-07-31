async function tsmHandleRequestResponse(response, onError, onSuccess, retryFunction = undefined) {
    if (response.status >= 400) {
        const {data} = await response.json();
        if (!data) {
            return onError(response, data);
        }
        const buttons = [];
        if ("buttons" in data && data.buttons) {
            data.buttons.forEach((button) => {
                buttons.push(Button.createNode(button.label, button.type, async (buttonNode) => {
                    setButtonLoading(buttonNode);
                    if ("url" in button) {
                        window.open(button.url, '_blank');
                    }
                    if (button.action === "add-new-language") {
                        const subResponse = await fetch(`${tsmVariables.url}add_new_language`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                language: document.querySelector("#global-languages").value,
                                wp_nonce: button.wpNonce
                            })
                        });
                        await subResponse.json();
                        if (typeof retryFunction === "function") { retryFunction(); }
                    }
                    stopButtonLoading(buttonNode);
                }));
            });
        }
        if ("title" in data && "message" in data && "logo" in data) {
            Notification.show(data.title, data.message, data.logo, "error", ("persist" in data) ? true : ("semi-persist" in data) ? 1 : false, document.body, buttons);
        }
        return onError(data);
    }
    return onSuccess(response);
}

function getQuery(queryName) {
    // parse the current url to get the params tsmShow
    const url = window.location.href;
    const urlObj = new URL(url);
    return urlObj.searchParams.get(queryName);
}

const installRequiredPlugins = document.querySelector("#install-required-plugins");
if (installRequiredPlugins) {
    installRequiredPlugins.addEventListener("click", async (e) => {
        e.preventDefault();
        setButtonLoading(installRequiredPlugins);
        const response = await fetch(`${tsmVariables.url}install_required_plugin`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                wp_nonce: installRequiredPlugins.dataset.wpnonce
            }),
        });
        await response.text();
        window.location.reload();
    });
}