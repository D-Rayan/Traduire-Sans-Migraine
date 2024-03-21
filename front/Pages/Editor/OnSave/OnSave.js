function injectFunctionTranslationModal(modal) {
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
    const checkedCheckboxes = modal.querySelectorAll("input[type='checkbox']:checked");
    const buttonTranslate = modal.querySelector('#translate-button');
    if (checkedCheckboxes.length > 0) {
        buttonTranslate.disabled = false;
        buttonTranslate.classList.remove('disabled');
        buttonTranslate.innerHTML = `${buttonTranslate.dataset.default} (${checkedCheckboxes.length})`;
    } else {
        buttonTranslate.disabled = true;
        buttonTranslate.classList.add('disabled');
    }
}
function sendRequests(modal, languages) {
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
    modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            displayCountCheckedToButton(modal);
            const column = checkbox.closest(".language").querySelector(".right-column");
            if (checkbox.checked) {
                column.querySelector(":scope > .notice").classList.add("hidden");
                column.querySelector(".traduire-sans-migraine-step").classList.remove("hidden");
            } else {
                column.querySelector(".traduire-sans-migraine-step").classList.add("hidden");
                column.querySelector(":scope > .notice").classList.remove("hidden");
            }
        });
    });
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
        const checkedCheckboxes = modal.querySelectorAll("input[type='checkbox']:checked");
        const languages = [];
        checkedCheckboxes.forEach(checkbox => {
            languages.push(checkbox.id);
        });
        modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
            if (checkbox.checked) {
                checkbox.disabled = true;
            } else {
                checkbox.closest(".language").remove();
            }
        });
        setButtonLoading('#translate-button')
        await sendRequests(modal, languages);
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