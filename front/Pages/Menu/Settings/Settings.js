const buttonSave = document.querySelector("#save-settings");

if (buttonSave) {
    buttonSave.addEventListener("click", async () => {
        const settings = new FormData();
        document.querySelector(".settings").querySelectorAll("input, select").forEach(input => {
            settings.append(input.id, input.checked ? true : false);
        });
        settings.append("wp_nonce", buttonSave.dataset.wp_nonce);
        setButtonLoading("#save-settings");
        const fetchResponse = await fetch(`${tsmVariables.url}update_settings`, {
            method: "POST",
            body: settings
        });
        const data = await fetchResponse.json();
        const alert = (!data.success) ? Alert.createNode("", data.error, "error") : Alert.createNode("", data.data, "success");
        buttonSave.parentNode.appendChild(alert);
        setTimeout(() => {
            alert.parentNode.removeChild(alert);
        }, 2500);
        stopButtonLoading("#save-settings");
    });
}

const buttonUpgrade = document.querySelector("#upgrade-quota");
if (buttonUpgrade) {
    buttonUpgrade.addEventListener("click", async () => {
        const href = buttonUpgrade.dataset.href;
        window.open(href, "_blank");
    });
}

const buttonUpgradePlan = document.querySelector("#upgrade-plan-button");
if (buttonUpgradePlan) {
    buttonUpgradePlan.addEventListener("click", async () => {
        const href = buttonUpgradePlan.dataset.href;
        window.open(href, "_blank");
    });
}



const buttonsDictionary = document.querySelectorAll("#dictionary-button");
buttonsDictionary.forEach(button => {
    button.addEventListener("click", async (e) => {
        e.preventDefault();
        setButtonLoading(button);
        const language = button.getAttribute("data-language");
        await loadAndDisplayDictionary(language);
        stopButtonLoading(button);
    });
});

async function addNewLanguageFromSettings(language, wp_nonce) {
    return await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}add_new_language`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            language,
            wp_nonce
        })
    }), () => {
        return false;
    }, async (response) => {
        const data = await response.json();
        if (data.success) {
            window.location.reload();
            return true;
        }
        return false;
    });
}

const buttonAddNewLanguage = document.querySelector("#add-new-language");
if (buttonAddNewLanguage) {
    buttonAddNewLanguage.addEventListener("click", async () => {
        setButtonLoading(buttonAddNewLanguage);
        if (!await addNewLanguageFromSettings(document.querySelector("#language-selection-add").value, buttonAddNewLanguage.dataset.nonce)) {
            stopButtonLoading(buttonAddNewLanguage);
        }
    });
}

document.querySelectorAll("#add-language").forEach(button => {
    button.addEventListener("click", async () => {
        setButtonLoading(button);
        if (!await addNewLanguageFromSettings(button.dataset.language, button.dataset.nonce)) {
            stopButtonLoading(button);
        }
    });
});
initTooltips();

function addSpinnerToSelect(select) {
    const spinner = document.createElement("div");
    spinner.className = "spinner is-active";
    if (select.nextSibling) {
        select.parentNode.insertBefore(spinner, select.nextSibling);
    } else {
        select.parentNode.appendChild(spinner);
    }
}

function removeSpinnerToSelect(select) {
    select.parentNode.querySelector(".spinner").remove();
}
document.querySelectorAll(".language-settings").forEach(languageSettings => {
    const formality = languageSettings.querySelector("#formality");
    const country = languageSettings.querySelector("#country");

    if (formality) {
        const slug = formality.dataset.slug;
        const wp_nonce = formality.dataset.nonce;
        formality.addEventListener("change", async (e) => {
            const newValue = formality.value;
            formality.disabled = true;
            addSpinnerToSelect(formality);
            await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}update_language_settings`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    slug,
                    formality: newValue,
                    wp_nonce,
                })
            }), () => {
                return false;
            }, async (response) => {
                const data = await response.json();
                return data.success;
            });
            formality.disabled = false;
            removeSpinnerToSelect(formality);
        });
    }

    if (country) {
        const slug = country.dataset.slug;
        const wp_nonce = country.dataset.nonce;
        country.addEventListener("change", async (e) => {
            const newValue = country.value;
            country.disabled = true;
            addSpinnerToSelect(country);
            await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}update_language_settings`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    slug,
                    country: newValue,
                    wp_nonce,
                })
            }), () => {
                return false;
            }, async (response) => {
                const data = await response.json();
                return data.success;
            });
            removeSpinnerToSelect(country);
            country.disabled = false;
        });
    }
});

