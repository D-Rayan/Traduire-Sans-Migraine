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
    addListenerToButtonDebug(modal);
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
async function sendRequests(modal, languages, wpNonces) {
    const response = await Promise.all(languages.map(async language => {
        const subResponse = await sendRequest(modal, language, wpNonces[language]);
        if (!subResponse) {
            return false;
        }
        const {tokenId, wpNonce} = subResponse;
        return {
            modal,
            tokenId,
            language,
            wpNonce,
        }
    }));
    if (response.every(r => r === false)) {
        return;
    }
    Notification.show("Traduction en cours", "La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site.", "loutre_docteur_no_shadow.png", "success");
    await Promise.all(response.map(({modal, tokenId, language, wpNonce}) => {
        return fetchStateTranslateUntilOver(modal, tokenId, language, wpNonce);
    }));
}

async function fetchStateTranslateUntilOver(modal, tokenId, language, wpNonce) {
    const stepDiv = getStepList(modal, language);
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_get_state_translate&tokenId=${tokenId}&wp_nonce=${wpNonce}`), () => {}, async (fetchResponse) => {
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
        const {percentage, status, html, wpNonce} = data.data;
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
        return fetchStateTranslateUntilOver(modal, tokenId, language, wpNonce);
    })
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

async function displayLogInSection(modal, callbackOnLoggedIn, wpNonce) {
    const modalContent = modal.querySelector(".traduire-sans-migraine-modal__content-body-text");
    if (modalContent.querySelector("#login-container")) {
        return;
    }
    setButtonLoading('#translate-button')
    const logInDiv = document.createElement("div");
    logInDiv.id = "login-container";
    modalContent.prepend(logInDiv);
    await renderLogInHTML(modal.querySelector("#login-container"), callbackOnLoggedIn, wpNonce);
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
            }, buttonTranslate.dataset.loggedNonce);
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
        const response = await sendRequestPrepare(modal, languages, buttonTranslate.dataset.wp_nonce);
        if (response !== false) {
            modal.querySelector("#global-languages-section").remove();
            modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
                if (checkbox.checked) {
                    checkbox.disabled = true;
                } else {
                    checkbox.closest(".language").remove();
                }
            });
            await sendRequests(modal, languages, response.wpNonce);
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

async function sendRequest(modal, language, wpNonce) {
    return tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_start_translate&post_id=${getQuery("post")}&language=${language}&wp_nonce=${wpNonce}`),
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
            return data.data;
        });
}

async function sendRequestPrepare(modal, languages, wpNonce) {
    const body = {
        post_id: getQuery("post"),
        languages,
        wp_nonce: wpNonce
    }
    return tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_prepare_translate`, {
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
        return {
            success: data.success,
            wpNonce: data.data.wpNonce
        };
    })
}



function addListenerToButtonDebug(modal) {
    const debugButton = modal.querySelector('#debug-button');
    if (!debugButton) {
        return;
    }
    const handleClickDebugButton = async (e) => {
        e.preventDefault();
        const code = prompt("Un code vous a été fournis par notre équipe, veuillez le copier et le coller ici :");
        if (!code) {
            return;
        }
        setButtonLoading(debugButton);
        await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}editor_debug&post_id=${getQuery("post")}&code=${code}&wp_nonce=${debugButton.dataset.wp_nonce}`), () => {

        }, async (fetchResponse) => {
            const data = await fetchResponse.json();
            if (data.success) {
                Notification.show(data.data.title, data.data.message, data.data.logo, "success");
            }
        });
        stopButtonLoading(debugButton);
    }
    debugButton.addEventListener('click', handleClickDebugButton);
}