function getQuery(queryName) {
    // parse the current url to get the params tsmShow
    const url = window.location.href;
    const urlObj = new URL(url);
    return urlObj.searchParams.get(queryName);
}

async function loadModalTraduireSansMigraine() {
    const fetchResponse = await fetch(`${tsm.url}editor_onSave_render&post_id=${getQuery("post")}`);
    const data = await fetchResponse.text();
    const modal = addModalToBody(data);
    injectFunctionTranslationModal(modal);
}

if (window && window.wp && window.wp.data && window.wp.data.dispatch('core/editor')) {
    const editor = window.wp.data.dispatch('core/editor')
    const savePost = editor.savePost
    editor.savePost = function (options) {
        options = options || {}

        return savePost(options)
            .then(() => {
                if (!options.isAutosave && document.querySelector("input[name='traduire-sans-migraine–is-enable']").checked) {
                    loadModalTraduireSansMigraine();
                }
            })
    };

} else {
    if (getQuery('tsmShow') === 'on') {
        window.history.replaceState(null, '', window.location.href.replace('&tsmShow=on', '').replace('tsmShow=on', ''));
        loadModalTraduireSansMigraine();
    }
}

const buttonDisplayTraduireSansMigraine = document.querySelector('#display-traduire-sans-migraine-button');
if (buttonDisplayTraduireSansMigraine) {
    buttonDisplayTraduireSansMigraine.addEventListener('click', (e) => {
        e.preventDefault();
        loadModalTraduireSansMigraine();
        return false;
    });
}