function getQuery(queryName) {
    // parse the current url to get the params tsmShow
    const url = window.location.href;
    const urlObj = new URL(url);
    return urlObj.searchParams.get(queryName);
}

async function loadModalTraduireSansMigraine(wpNonce) {
    setButtonLoading("#display-traduire-sans-migraine-button");
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_onSave_render&post_id=${getQuery("post")}&wp_nonce=${wpNonce}`), (response) => {
        stopButtonLoading("#display-traduire-sans-migraine-button");
    }, async (response) => {
        const data = await response.text();
        const modal = addModalToBody(data);
        injectFunctionTranslationModal(modal);
        stopButtonLoading("#display-traduire-sans-migraine-button");
    }, () => {
        Notification.cleanAll();
        return loadModalTraduireSansMigraine(wpNonce);
    });
}

const fetchNotifications = async (context = "") => {
    if (!window.tsmVariables || !window.tsmVariables.wpNonce_editor_get_post_notifications) {
        return;
    }
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_get_post_notifications&post_id=${getQuery("post")}&wp_nonce=${tsmVariables.wpNonce_editor_get_post_notifications}&context=${context}`), (response) => {

    }, async (response) => {
        const data = await response.json();
        const notifications = data.data;
        notifications.forEach((notification) => {
            const buttons = [];
            if (notification.buttons) {
                notification.buttons.forEach((button) => {
                    buttons.push(Button.createNode(button.label, button.type, async (buttonNode) => {
                        setButtonLoading(buttonNode);
                        if ("url" in button) {
                            window.open(button.url, '_blank');
                        }
                        if ("action" in button) {
                            if (button.action === "translateInternalLinks") {
                                await translateInternalLinks(button.wpNonce);
                            } else if (button.action === "updateTranslations") {
                                await updateTranslations(button.wpNonce);
                            }
                        }
                        stopButtonLoading(buttonNode);
                    }));
                });
            }
            Notification.show(notification.title, notification.message, notification.logo, notification.type, notification.persist, document.body, buttons, notification.displayDefault);
        });
    });
}
fetchNotifications();

if (window && window.wp && window.wp.data && window.wp.data.dispatch('core/editor')) {
    const moveButtonToHeader =  () => {
        const headerElement = document.querySelector(".edit-post-header__settings") || document.querySelector(".editor-header__settings");
        if (headerElement) {
            const button = document.querySelector('#display-traduire-sans-migraine-button');
            headerElement.appendChild(button);
            document.querySelector("#metaboxTraduireSansMigraine").remove();
        } else {
            setTimeout(moveButtonToHeader, 100);
        }
    };
    moveButtonToHeader();

    const editor = window.wp.data.dispatch('core/editor')
    const savePost = editor.savePost
    editor.savePost = function (options) {
        options = options || {}

        return savePost(options)
            .then(() => {
                if (!options.isAutosave) {
                    fetchNotifications("onSave");
                }
            })
    };
}


const buttonDisplayTraduireSansMigraine = document.querySelector('#display-traduire-sans-migraine-button');
if (buttonDisplayTraduireSansMigraine) {
    buttonDisplayTraduireSansMigraine.addEventListener('click', (e) => {
        e.preventDefault();
        loadModalTraduireSansMigraine(buttonDisplayTraduireSansMigraine.dataset.wp_nonce);
        return false;
    });
}


async function translateInternalLinks(wpNonce) {
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_translate_internal_links&post_id=${getQuery("post")}&wp_nonce=${wpNonce}`), (response) => {
        console.error("response", response);
    }, async (response) => {
        window.location = `${window.location.href}&internal_links_translated=1`;
    });
}
async function updateTranslations(wpNonce) {
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_update_translations&post_id=${getQuery("post")}&wp_nonce=${wpNonce}`), (response) => {
        console.error("response", response);
        Notification.cleanAll();
    }, async (response) => {
        const data = await response.json();
        const notification = data.data;
        Notification.cleanAll();
        Notification.show(notification.title, notification.message, notification.logo, "success");
    });
}
