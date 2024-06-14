import {CustomWindow} from "../../Main/WindowTSM";
import {getQuery, tsmHandleRequestResponse} from "../../Main/Main";
import {addModalToBody, removeModal} from "../../Components/Modal/Modal";
import {TSM_Notification} from "../../Components/Notification/Notification";
import {renderLogInHTML} from "../LogIn/LogIn";

declare let window: CustomWindow;

console.log("Traduire-Sans-Migraine Editor");

function getCheckboxesListChecked(modal: HTMLElement) {
    return modal.querySelectorAll("input[type='checkbox']:not([id='global-languages']):not([disabled]):checked")
}

function getCheckboxesList(modal: HTMLElement) {
    return modal.querySelectorAll("input[type='checkbox']:not([id='global-languages']):not([disabled])")
}

function displayCountCheckedToButton(modal: HTMLElement) {
    const checkedCheckboxes = getCheckboxesListChecked(modal);
    const buttonTranslate = modal.querySelector('#translate-button');
    if (!(buttonTranslate instanceof HTMLButtonElement)) {
        return;
    }
    if (checkedCheckboxes.length > 0) {
        buttonTranslate.disabled = false;
        buttonTranslate.classList.remove('disabled');
        buttonTranslate.innerHTML = `${buttonTranslate.dataset.default} (${checkedCheckboxes.length})`;
    } else {
        buttonTranslate.disabled = true;
        buttonTranslate.classList.add('disabled');
    }
}


async function displayLogInSection(modal: HTMLElement, callbackOnLoggedIn: () => void) {
    const modalContent = modal.querySelector(".traduire-sans-migraine-modal__content-body-text");
    if (modalContent.querySelector("#login-container")) {
        return;
    }
    setButtonLoading('#translate-button')
    const logInDiv = document.createElement("div");
    logInDiv.id = "login-container";
    modalContent.prepend(logInDiv);
    await renderLogInHTML(modal.querySelector("#login-container"), callbackOnLoggedIn);
    stopButtonLoading('#translate-button')
}


function removeLogInSection(modal: HTMLElement) {
    const logInDiv = modal.querySelector("#login-container");
    if (logInDiv) {
        logInDiv.remove();
    }
}



function getStepList(modal: HTMLElement, language: string): HTMLElement {
    return modal.querySelector(`.language[data-language="${language}"] .right-column .traduire-sans-migraine-step`)
}

async function sendRequest(modal: HTMLElement, language: string) {
    return tsmHandleRequestResponse(await fetch(`${window.tsmVariables.url}editor_start_translate&post_id=${getQuery("post")}&language=${language}`),
        (data) => {
            const stepDiv = getStepList(modal, language);
            setStep({
                percentage: 100,
                div: stepDiv,
                status: "error",
                html: typeof data.error === "string" ? data.error : data.error.message.join("<br>"),
            });
            return false;
        }, async (fetchResponse) => {
            const data = await fetchResponse.json();
            console.log({data});
            const stepDiv = getStepList(modal, language);
            if (!data.success || !("data" in data) || !("tokenId" in data.data)) {
                setStep({
                    percentage: 100,
                    div: stepDiv,
                    status: "error",
                    html: typeof data.error === "string" ? data.error : typeof data.error.message === "string" ? data.error.message : data.error.message.join("<br>"),
                });
                return false;
            }
            return data.data.tokenId;
        });
}

async function sendRequests(modal: HTMLElement, languages: Array<string>) {
    const response = await Promise.all(languages.map(async language => {
        const tokenId = await sendRequest(modal, language);
        if (!tokenId) {
            return false;
        }
        return {
            modal,
            tokenId,
            language
        }
    }));
    if (response.every(r => r === false)) {
        return;
    }
    TSM_Notification.show("Traduction en cours", "La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site.", "loutre_docteur_no_shadow.png", "success");
    await Promise.all(response.map(({modal, tokenId, language}: {modal: HTMLElement, tokenId: string, language: string}) => {
        return fetchStateTranslateUntilOver(modal, tokenId, language);
    }));
}

async function fetchStateTranslateUntilOver(modal: HTMLElement, tokenId: string, language: string) {
    const stepDiv = getStepList(modal, language);
    await tsmHandleRequestResponse(await fetch(`${window.tsmVariables.url}editor_get_state_translate&tokenId=${tokenId}`), () => {}, async (fetchResponse) => {
        const data = await fetchResponse.json();
        if (!data.success || !("data" in data)) {
            setStep({
                percentage: 100,
                div: stepDiv,
                status: "error",
                html: data.error,
            });
            return;
        }
        const {percentage, status, html} = data.data;
        setStep({
            percentage,
            div: stepDiv,
            status,
            html,
        });
        if (status === "error" || +percentage === 100) {
            return;
        }
        await new Promise(resolve => setTimeout(resolve, 1500));
        return fetchStateTranslateUntilOver(modal, tokenId, language);
    })
}

async function sendRequestPrepare(modal: HTMLElement, languages: Array<string>) {
    const body = {
        post_id: getQuery("post"),
        languages
    }
    return tsmHandleRequestResponse(await fetch(`${window.tsmVariables.url}editor_prepare_translate`, {
        method: "POST",
        body: JSON.stringify(body),
    }), () => {
        return false;
    }, async (fetchResponse) => {
        const data = await fetchResponse.json();
        if (!data.success) {
            const alert = Alert.createNode("", data.error, "error");
            modal.querySelector(".traduire-sans-migraine-modal__content-body-text").prepend(alert);
        }
        return data.success;
    })
}

function addListenerToButtonTranslate(modal: HTMLElement) {
    const buttonTranslate: HTMLElement = modal.querySelector('#translate-button');
    buttonTranslate.addEventListener('click', async (e) => {
        e.preventDefault();
        if (buttonTranslate.dataset.logged === "false") {
            await displayLogInSection(modal, () => {
                removeLogInSection(modal);
                buttonTranslate.dataset.logged = "true";
                buttonTranslate.click();
            });
            return;
        }
        const checkedCheckboxes = getCheckboxesListChecked(modal);
        const languages: Array<string> = [];
        checkedCheckboxes.forEach(checkbox => {
            languages.push(checkbox.id);
        });
        modal.querySelectorAll("input[type='checkbox']").forEach((checkbox: HTMLInputElement) => {
            checkbox.disabled = true;
        });
        setButtonLoading('#translate-button')
        const prepareIsSuccess = await sendRequestPrepare(modal, languages);
        if (prepareIsSuccess) {
            modal.querySelector("#global-languages-section").remove();
            modal.querySelectorAll("input[type='checkbox']").forEach((checkbox: HTMLInputElement) => {
                if (checkbox.checked) {
                    checkbox.disabled = true;
                } else {
                    checkbox.closest(".language").remove();
                }
            });
            await sendRequests(modal, languages);
        } else {
            modal.querySelectorAll("input[type='checkbox']").forEach((checkbox: HTMLInputElement) => {
                checkbox.disabled = false;
            });
        }
        stopButtonLoading('#translate-button')
    });
}

function addListenerToButtonTranslateLater(modal: HTMLElement) {
    const buttonTranslateLater = modal.querySelector('#closing-button');
    buttonTranslateLater.addEventListener('click', (e) => {
        e.preventDefault();
        removeModal(modal);
    });
}

function injectFunctionTranslationModal(modal: HTMLElement) {
    if (!modal.querySelector('#translate-button')) {
        return;
    }
    addListenerToCheckboxes(modal);
    displayCountCheckedToButton(modal);
    addListenerToButtonTranslate(modal);
    addListenerToButtonTranslateLater(modal);
    addListenerToButtonDebug(modal);
    initTooltips();

}

function addListenerToButtonDebug(modal: HTMLElement) {
    const debugButton = modal.querySelector('#debug-button');
    if (!debugButton) {
        return;
    }
    const handleClickDebugButton = async (e: MouseEvent) => {
        e.preventDefault();
        const code = prompt("Un code vous a été fournis par notre équipe, veuillez le copier et le coller ici :");
        if (!code) {
            return;
        }
        setButtonLoading(debugButton);
        await tsmHandleRequestResponse(await fetch(`${window.tsmVariables.url}editor_debug&post_id=${getQuery("post")}&code=${code}`), () => {

        }, async (fetchResponse) => {
            const data = await fetchResponse.json();
            if (data.success) {
                TSM_Notification.show(data.data.title, data.data.message, data.data.logo, "success");
            }
        });
        stopButtonLoading(debugButton);
    }
    debugButton.addEventListener('click', handleClickDebugButton);
}

function addListenerToCheckboxes(modal: HTMLElement) {
    const handleOnChange = (checkbox: HTMLInputElement) => {
        displayCountCheckedToButton(modal);
        const column = checkbox.closest(".language").querySelector(".right-column");
        if (checkbox.checked) {
            column.querySelector(":scope > .notice").classList.add("hidden");
            column.querySelector(".traduire-sans-migraine-step").classList.remove("hidden");
        } else {
            column.querySelector(".traduire-sans-migraine-step").classList.add("hidden");
            column.querySelector(":scope > .notice").classList.remove("hidden");
        }
    };
    const allCheckboxes = getCheckboxesList(modal);
    const globalCheckbox = modal.querySelector("#global-languages");
    if (!(globalCheckbox instanceof HTMLInputElement)) {
        return;
    }
    const updateDisplayGlobalCheckbox = () => {
        const checkedCheckboxes = getCheckboxesListChecked(modal).length;
        globalCheckbox.checked = (allCheckboxes.length === checkedCheckboxes);
        globalCheckbox.indeterminate = checkedCheckboxes > 0 && !globalCheckbox.checked;
    };
    globalCheckbox.addEventListener('change', () => {
        allCheckboxes.forEach((checkbox: HTMLInputElement) => {
            checkbox.checked = globalCheckbox.checked;
            handleOnChange(checkbox);
        });
    })
    allCheckboxes.forEach((checkbox: HTMLInputElement) => {
        checkbox.addEventListener('change', () => {
            handleOnChange(checkbox);
            updateDisplayGlobalCheckbox();
        });
    });
    updateDisplayGlobalCheckbox();
}

async function loadModalTraduireSansMigraine() {
    setButtonLoading("#display-traduire-sans-migraine-button");
    await tsmHandleRequestResponse(await fetch(`${window.tsmVariables.url}editor_onSave_render&post_id=${getQuery("post")}`), (response) => {
        stopButtonLoading("#display-traduire-sans-migraine-button");
    }, async (response) => {
        const data = await response.text();
        const modal = addModalToBody(data);
        injectFunctionTranslationModal(modal);
        stopButtonLoading("#display-traduire-sans-migraine-button");
    });
}

if (window.tsmVariables && window.tsmVariables._tsm_first_visit_after_translation === "true") {
    TSM_Notification.show('successTranslationFirstShowTitle', "successTranslationFirstShow", "loutre_docteur_no_shadow.png", "success", true);
}

if (window && window.wp && window.wp.data && window.wp.data.dispatch('core/editor')) {
    if (window.tsmVariables.autoOpen === "true") {
        const editor = window.wp.data.dispatch('core/editor')
        const savePost = editor.savePost
        editor.savePost = function (options: any) {
            options = options || {}

            return savePost(options)
                .then(() => {
                    if (!options.isAutosave) {
                        loadModalTraduireSansMigraine();
                    }
                })
        };
    }

    const moveButtonToHeader = () => {
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