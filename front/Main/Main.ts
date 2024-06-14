import {TSM_Notification} from "../Components/Notification/Notification";

export async function tsmHandleRequestResponse(response: any, onError: (response: any, data?: any) => any, onSuccess: (response: any) => any) {
    if (response.status >= 400) {
        const {data} = await response.json();
        if (!data) {
            return onError(response, data);
        }
        TSM_Notification.show(data.title, data.message, data.logo, "error");
        return onError(response);
    }
    return onSuccess(response);
}

export function getQuery(queryName: string) {
    // parse the current url to get the params tsmShow
    const url = window.location.href;
    const urlObj = new URL(url);
    return urlObj.searchParams.get(queryName);
}