async function tsmHandleRequestResponse(response, onError, onSuccess) {
    if (response.status >= 400) {
        const {data} = await response.json();
        Notification.show(data.title, data.message, data.logo, "error");
        return onError(response);
    }
    return onSuccess(response);
}