function getQuery(queryName) {
    // parse the current url to get the params tsmShow
    const url = window.location.href;
    const urlObj = new URL(url);
    return urlObj.searchParams.get(queryName);
}

async function loadModalTraduireSansMigraine() {
    setButtonLoading("#display-traduire-sans-migraine-button");
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_onSave_render&post_id=${getQuery("post")}`), (response) => {
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
            await translateInternalLinks();
            stopButtonLoading(button);
        })],
        window.tsmVariables._tsm_first_visit_after_translation === "true"
        );
}

if (window && window.wp && window.wp.data && window.wp.data.dispatch('core/editor')) {
    /*if (tsmVariables.autoOpen === "true") {
        const editor = window.wp.data.dispatch('core/editor')
        const savePost = editor.savePost
        editor.savePost = function (options) {
            options = options || {}

            return savePost(options)
                .then(() => {
                    if (!options.isAutosave) {
                        loadModalTraduireSansMigraine();
                    }
                })
        };
    }*/

    const moveButtonToHeader =  () => {
        const headerElement = document.querySelector(".edit-post-header__settings");
        if (headerElement) {
            const button = document.querySelector('#display-traduire-sans-migraine-button');
            headerElement.appendChild(button);
            document.querySelector("#metaboxTraduireSansMigraine").remove();
        } else {
            setTimeout(moveButtonToHeader, 100);
        }
    };
    moveButtonToHeader();
} else {
    /*if (getQuery('tsmShow') === 'on') {
        window.history.replaceState(null, '', window.location.href.replace('&tsmShow=on', '').replace('tsmShow=on', ''));
        loadModalTraduireSansMigraine();
    }*/
}


const buttonDisplayTraduireSansMigraine = document.querySelector('#display-traduire-sans-migraine-button');
if (buttonDisplayTraduireSansMigraine) {
    buttonDisplayTraduireSansMigraine.addEventListener('click', (e) => {
        e.preventDefault();
        loadModalTraduireSansMigraine();
        return false;
    });
}

async function translateInternalLinks() {
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_translate_internal_links&post_id=${getQuery("post")}`), (response) => {
        console.error("response", response);
    }, async (response) => {
        window.location.reload();
    });
}