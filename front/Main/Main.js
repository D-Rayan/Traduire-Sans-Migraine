async function tsmHandleRequestResponse(response, onError, onSuccess) {
    if (response.status >= 400) {
        const {data} = await response.json();
        if (!data) {
            return onError(response, data);
        }
        const buttons = [];
        if (data.buttons) {
            data.buttons.forEach((button) => {
                buttons.push(Button.createNode(button.label, button.type, async (buttonNode) => {
                    setButtonLoading(buttonNode);
                    if ("url" in button) {
                        window.open(button.url, '_blank');
                    }
                    stopButtonLoading(buttonNode);
                }));
            });
        }
        Notification.show(data.title, data.message, data.logo, "error", ("persist" in data), document.body, buttons);
        return onError(response);
    }
    return onSuccess(response);
}

function getQuery(queryName) {
    // parse the current url to get the params tsmShow
    const url = window.location.href;
    const urlObj = new URL(url);
    return urlObj.searchParams.get(queryName);
}