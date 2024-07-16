(() => {
    if (!("tsmVariables" in window)) {
        return;
    }
    if (!("trashed" in window.tsmVariables) || !("ids" in window.tsmVariables) || !window.tsmVariables.trashed) {
        return;
    }
    const ids = window.tsmVariables.ids.split(",");
    if (ids.length > 1) {
        return; // will not display a modal par post that will be too much for the user
    }

    async function deleteTranslations(modal, id, wpNonce) {
        const contentModal = modal.querySelector(".traduire-sans-migraine-modal__content-body-text");
        const fetchResponse = await fetch(`${tsmVariables.url}article_deleted_delete_translations&post_id=${id}&wp_nonce=${wpNonce}`);
        const data = await fetchResponse.json();
        if (!data.success) {
            const div = document.createElement("div");
            div.id = "result";
            if (contentModal.querySelector("#result")) {
                contentModal.querySelector("#result").remove();
            }
            div.innerHTML = data.data.html;
            contentModal.append(div);
            return;
        }
        contentModal.innerHTML = data.data.html;
    }
    async function loadModalTraduireSansMigraineForTrash(id, wpNonce) {
        const fetchResponse = await fetch(`${tsmVariables.url}article_deleted_render&post_id=${id}&wp_nonce=${wpNonce}`);
        if (fetchResponse.status >= 400) {
            return;
        }
        const data = await fetchResponse.text();
        const modal = addModalToBody(data);
        const noButton = modal.querySelector("#no-button");
        const yesButton = modal.querySelector("#yes-button");
        noButton.addEventListener("click", (e) => {
            e.preventDefault();
            removeModal(modal);
        });
        yesButton.addEventListener("click", async (e) => {
            e.preventDefault();
            setButtonLoading(yesButton);
            setButtonLoading(noButton);
            await deleteTranslations(modal, id, yesButton.dataset.wp_nonce);
            stopButtonLoading(yesButton);
            stopButtonLoading(noButton);
        });
    }

    loadModalTraduireSansMigraineForTrash(ids[0], window.tsmVariables.wpNonce_article_deleted_render);
})();

