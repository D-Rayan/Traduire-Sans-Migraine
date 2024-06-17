async function tsmHandleRequestResponse(response, onError, onSuccess) {
    if (response.status >= 400) {
        const {data} = await response.json();
        if (!data) {
            return onError(response, data);
        }
        Notification.show(data.title, data.message, data.logo, "error");
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