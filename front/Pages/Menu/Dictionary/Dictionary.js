function initDictionaryModal(modal) {
    const langTo = modal.querySelector("input[name='langTo']").value;
    function addEventListenerToFormAdd(row) {
        const inputLangFrom = row.querySelector("select[name='langFrom']");
        const inputEntry = row.querySelector("input[name='entry']");
        const inputResult = row.querySelector("input[name='result']");
        const buttonAdd = row.querySelector("button[id='add']");
        buttonAdd.addEventListener("click", async (e) => {
            e.preventDefault();
            const langFrom = inputLangFrom.value;
            const entry = inputEntry.value.trim();
            const translatedResult = inputResult.value.trim();
            if (!langFrom || !entry || !translatedResult || !entry.length || !translatedResult.length) {
                return;
            }
            inputEntry.value = entry;
            inputResult.value = translatedResult;
            setButtonLoading(buttonAdd);
            await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}add_word_to_dictionary`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    langFrom,
                    entry,
                    result: translatedResult,
                    langTo,
                    wp_nonce: buttonAdd.getAttribute("data-wp-nonce"),
                }),
            }), () => {}, async (response) => {
                const data = await response.json();
                if (data.success) {
                    const newRowHTML = data.data.newRow;
                    const rowUpdatedHTML = data.data.updatedRow;
                    const newRow = document.createElement("form");
                    newRow.innerHTML = newRowHTML;
                    newRow.classList.add("row-dictionary");
                    const rowUpdated = document.createElement("form");
                    rowUpdated.innerHTML = rowUpdatedHTML;
                    rowUpdated.classList.add("row-dictionary");
                    row.parentNode.append(rowUpdated);
                    row.parentNode.append(newRow);
                    row.remove();
                    addEventListenerToFormUpdate(rowUpdated);
                    addEventListenerToFormAdd(newRow);
                    Notification.show(data.data.title, data.data.message, data.data.logo, "success");
                }
            });
            stopButtonLoading(buttonAdd);
        });
    }
    function addEventListenerToFormUpdate(row) {
        const inputId = row.querySelector("input[name='_id']");
        const inputLangFrom = row.querySelector("select[name='langFrom']");
        const inputEntry = row.querySelector("input[name='entry']");
        const inputResult = row.querySelector("input[name='result']");
        const buttonUpdate = row.querySelector("button[id='update']");
        const buttonDelete = row.querySelector("button[id='delete']");

        buttonUpdate.addEventListener("click", async (e) => {
            e.preventDefault();
            const langFrom = inputLangFrom.value;
            const entry = inputEntry.value.trim();
            const translatedResult = inputResult.value.trim();
            if (!langFrom || !entry || !translatedResult || !entry.length || !translatedResult.length) {
                return;
            }
            inputEntry.value = entry;
            inputResult.value = translatedResult;
            setButtonLoading(buttonUpdate);
            await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}update_word_to_dictionary`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    id: inputId.value,
                    langFrom,
                    entry,
                    result: translatedResult,
                    wp_nonce: buttonUpdate.getAttribute("data-wp-nonce"),
                }),
            }), () => {}, async (response) => {
                const data = await response.json();
                if (data.success) {
                    Notification.show(data.data.title, data.data.message, data.data.logo, "success");
                }
            });
            stopButtonLoading(buttonUpdate);
        });
        buttonDelete.addEventListener("click", async (e) => {
            e.preventDefault();
            setButtonLoading(buttonDelete);
            await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}delete_word_to_dictionary&id=${inputId.value}&wp_nonce=${buttonDelete.getAttribute("data-wp-nonce")}`, {
                method: "DELETE",
            }), () => {}, async (response) => {
                const data = await response.json();
                if (data.success) {
                    Notification.show(data.data.title, data.data.message, data.data.logo, "success");
                    row.remove();
                }
            });
            if (buttonDelete) {
                stopButtonLoading(buttonDelete);
            }
        });
    }

    const rows = modal.querySelectorAll("form.row-dictionary");
    rows.forEach(row => {
        const inputId = row.querySelector("input[name='_id']");
        if (!inputId) {
            addEventListenerToFormAdd(row);
        } else {
            addEventListenerToFormUpdate(row);
        }
    });
}

async function loadAndDisplayDictionary(language) {
    await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}render_dictionary&language=${language}`), (response) => {

    }, async (response) => {
        const data = await response.text();
        const modal = addModalToBody(data);
        initDictionaryModal(modal);
    });
}