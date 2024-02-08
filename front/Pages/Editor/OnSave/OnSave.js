function injectFunctionTranslationModal(modal) {
    modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
       checkbox.addEventListener('change', () => {
            countCheckedCheckboxes(modal);
            const column = checkbox.closest(".language").querySelector(".right-column");
            if (checkbox.checked) {
                column.querySelector(".notice").classList.add("hidden");
                column.querySelector(".traduire-sans-migraine-step").classList.remove("hidden");
            } else {
                column.querySelector(".traduire-sans-migraine-step").classList.add("hidden");
                column.querySelector(".notice").classList.remove("hidden");
            }
       });
    });
    countCheckedCheckboxes(modal);
    const buttonTranslate = modal.querySelector('#translate-button');
    buttonTranslate.addEventListener('click', async (e) => {
        e.preventDefault();
        const checkedCheckboxes = modal.querySelectorAll("input[type='checkbox']:checked");
        const languages = [];
        checkedCheckboxes.forEach(checkbox => {
            languages.push(checkbox.id);
        });
        modal.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
            if (checkbox.checked) {
                checkbox.disabled = true;
                startStep(getStepList(modal, checkbox.id));
            } else {
                checkbox.closest(".language").remove();
            }
        });
        buttonTranslate.disabled = true;
        buttonTranslate.classList.add('disabled');
        await sendRequest(modal, languages);
        buttonTranslate.disabled = false;
        buttonTranslate.classList.remove('disabled');
    });
}

function getStepList(modal, language) {
    return modal.querySelector(`.language[data-language="${language}"] .right-column .traduire-sans-migraine-step`)
}
function countCheckedCheckboxes(modal) {
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
function sendRequest(modal, languages) {
    return Promise.all(languages.map(language => {
        return new Promise(async (resolve, reject) => {
            const fetchResponse = await fetch(`${tsm.url}editor_start_translate&post_id=${getQuery("post")}&language=${language}`);
            const data = await fetchResponse.json();
            const stepList = getStepList(modal, language);
            if (!data.success || !("data" in data) || !("tokenId" in data.data)) {
                setStepAsError(stepList);
                return resolve();
            }
            goNextStep(stepList);
            const tokenId = data.data.tokenId;
            const process = async () => {
                const fetchResponse = await fetch(`${traduire-sans-migraine.url}editor_get_state_translate&tokenId=${tokenId}`);
                const data = await fetchResponse.json();
                if (!data.success || !("data" in data)) {
                    setStepAsError(stepList);
                    return resolve();
                }
                const status = data.data;
                console.log(status);
                const maxStep = Object.keys(status).reduce((acc, key) => {
                    if (+key > acc) return key;
                    return acc;
                }, 1);
                switch (status[maxStep]) {
                    case "error":
                        setStepProgress(stepList, maxStep, "error");
                        return resolve();
                    case "success":
                    case "done":
                        if (maxStep === 4) {
                            setAllStepAsSuccess(stepList);
                            return resolve();
                        }
                        setStepProgress(stepList, maxStep + 1, "current");
                        setTimeout(process, 5000);
                        break;
                    case "pending":
                        setStepProgress(stepList, maxStep, "current");
                        setTimeout(process, 5000);
                        break;
                }
            };
            process();
        });
    }));
}