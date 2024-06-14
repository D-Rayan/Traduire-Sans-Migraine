import {CustomWindow} from "../../Main/WindowTSM";
import {addModalToBody, removeModal} from "../../Components/Modal/Modal";

declare let window: CustomWindow;

async function deleteTranslations(modal: HTMLElement, id: string) {
    const contentModal = modal.querySelector(".traduire-sans-migraine-modal__content-body-text");
    const fetchResponse = await fetch(`${window.tsmVariables.url}article_deleted_delete_translations&post_id=${id}`);
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
async function loadModalTraduireSansMigraineForTrash(id: string) {
    const fetchResponse = await fetch(`${window.tsmVariables.url}article_deleted_render&post_id=${id}`);
    if (fetchResponse.status >= 400) {
        return;
    }
    const data = await fetchResponse.text();
    const modal = addModalToBody(data);
    const noButton = modal.querySelector("#no-button");
    const yesButton = modal.querySelector("#yes-button");
    noButton.addEventListener("click", (e: MouseEvent) => {
        e.preventDefault();
        removeModal(modal);
    });
    yesButton.addEventListener("click", async (e: MouseEvent) => {
        e.preventDefault();
        setButtonLoading(yesButton);
        setButtonLoading(noButton);
        await deleteTranslations(modal, id);
        stopButtonLoading(yesButton);
        stopButtonLoading(noButton);
    });
}

(() => {
    if (!("trashed" in window.tsmVariables) || !("ids" in window.tsmVariables) || !window.tsmVariables.trashed || typeof window.tsmVariables.ids !== "string") {
        return;
    }
    const ids = window.tsmVariables.ids.split(",");
    if (ids.length > 1) {
        return; // will not display a modal par post that will be too much for the user
    }

    loadModalTraduireSansMigraineForTrash(ids[0]);
})();

