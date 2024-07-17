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
    });
}

if (window.tsmVariables && window.tsmVariables._has_been_translated_by_tsm === "true") {
    Notification.show(
        'postTranslatedByTSMTitle',
        "postTranslatedByTSMMessage",
        "loutre_docteur_no_shadow.png",
        "success",
        true,
        document.body,
        [Button.createNode("translateInternalLinksButton", "primary", async (button) => {
            setButtonLoading(button);
            await translateInternalLinks(window.tsmVariables.wpNonce_editor_translate_internal_links);
            stopButtonLoading(button);
        })],
        window.tsmVariables._tsm_first_visit_after_translation === "true"
    );
}

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