function getCheckboxesListChecked(modal) {
    return modal.querySelectorAll("input[type='checkbox']:not([id='global-languages']):not([disabled]):checked")
}

function getCheckboxesList(modal) {
    return modal.querySelectorAll("input[type='checkbox']:not([id='global-languages']):not([disabled])")
}

function injectFunctionTranslationModal(modal) {
    if (!modal.querySelector('#translate-button')) {
        return;
    }
    addListenerToCheckboxes(modal);
    displayCountCheckedToButton(modal);
    addListenerToButtonTranslate(modal);
    addListenerToButtonTranslateLater(modal);
    initTooltips();
}

function getStepList(modal, language) {
    return modal.querySelector(`.language[data-language="${language}"] .right-column .traduire-sans-migraine-step`)
}
function displayCountCheckedToButton(modal) {
    const checkedCheckboxes = getCheckboxesListChecked(modal);
    const buttonTranslate = modal.querySelector('#translate-button');
    if (!buttonTranslate) {
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
async function sendRequests(modal, languages) {
    return Promise.all(languages.map(async language => {
        const tokenId = await sendRequest(modal, language);
        if (!tokenId) {
            return;
        }
        await fetchStateTranslateUntilOver(modal, tokenId, language);
    }));
}

async function fetchStateTranslateUntilOver(modal, tokenId, language) {
    const stepDiv = getStepList(modal, language);
    const fetchResponse = await fetch(`${tsm.url}editor_get_state_translate&tokenId=${tokenId}`);
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
}

function addListenerToCheckboxes(modal) {
    const handleOnChange = (checkbox) => {
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
    if (!globalCheckbox) {
        return;
    }
    const updateDisplayGlobalCheckbox = () => {
        const checkedCheckboxes = getCheckboxesListChecked(modal).length;
        globalCheckbox.checked = (allCheckboxes.length === checkedCheckboxes);
        globalCheckbox.indeterminate = checkedCheckboxes > 0 && !globalCheckbox.checked;
    };
    globalCheckbox.addEventListener('change', () => {
        allCheckboxes.forEach(checkbox => {
            checkbox.checked = globalCheckbox.checked;
            handleOnChange(checkbox);
        });
    })
    allCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            handleOnChange(checkbox);
            updateDisplayGlobalCheckbox();
        });
    });
    updateDisplayGlobalCheckbox();
}

async function displayLogInSection(modal, callbackOnLoggedIn) {
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

function removeLogInSection(modal) {
    const logInDiv = modal.querySelector("#login-container");
    if (logInDiv) {
        logInDiv.remove();
    }
}

function addListenerToButtonTranslate(modal) {
    const buttonTranslate = modal.querySelector('#translate-button');
    buttonTranslate.addEventListener('click', async (e) => {
        e.preventDefault();
        if (buttonTranslate.dataset.logged === "false") {
            await displayLogInSection(modal,() => {
                removeLogInSection(modal);
                buttonTranslate.dataset.logged = "true";
                buttonTranslate.click();
            });
            return;
        }
        const checkedCheckboxes = getCheckboxesListChecked(modal);
        const languages = [];
        checkedCheckboxes.forEach(checkbox => {
            languages.push(checkbox.id);
        });
        modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
            checkbox.disabled = true;
        });
        setButtonLoading('#translate-button')
        const prepareIsSuccess = await sendRequestPrepare(modal, languages);
        if (prepareIsSuccess) {
            modal.querySelector("#global-languages-section").remove();
            modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
                if (checkbox.checked) {
                    checkbox.disabled = true;
                } else {
                    checkbox.closest(".language").remove();
                }
            });
            switchSuggestionsMessages(modal);
            await sendRequests(modal, languages);
        } else {
            modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
                checkbox.disabled = false;
            });
        }
        stopButtonLoading('#translate-button')
    });
}

function addListenerToButtonTranslateLater(modal) {
    const buttonTranslateLater = modal.querySelector('#closing-button');
    buttonTranslateLater.addEventListener('click', (e) => {
        e.preventDefault();
        removeModal(modal);
    });
}

async function sendRequest(modal, language) {
    const fetchResponse = await fetch(`${tsm.url}editor_start_translate&post_id=${getQuery("post")}&language=${language}`);
    const data = await fetchResponse.json();
    const stepDiv = getStepList(modal, language);
    if (!data.success || !("data" in data) || !("tokenId" in data.data)) {
        setStep({
            percentage: 100,
            div: stepDiv,
            status: "error",
            html: typeof data.error === "string" ? data.error : data.error.message.join("<br>"),
        });
        return false;
    }
    return data.data.tokenId;
}

async function sendRequestPrepare(modal, languages) {
    const body = {
        post_id: getQuery("post"),
        languages
    }
    const fetchResponse = await fetch(`${tsm.url}editor_prepare_translate`, {
        method: "POST",
        body: JSON.stringify(body),
    });
    const data = await fetchResponse.json();
    if (!data.success) {
        const alert = Alert.createNode("", data.error, "error");
        modal.querySelector(".traduire-sans-migraine-modal__content-body-text").prepend(alert);
    }
    return data.success;
}

function switchSuggestionsMessages(modal) {
    const suggestions = modal.querySelectorAll(".traduire-sans-migraine-suggestion");
    suggestions.forEach(suggestion => {
        if (suggestion.classList.contains("hidden")) {
            suggestion.classList.remove("hidden");
        } else {
            suggestion.classList.add("hidden");
        }
    });
}