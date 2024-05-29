
function getCheckboxesList() {
    return document.querySelectorAll("input[type='checkbox']:not([id='all-posts']):not([disabled])")
}

function getCheckboxesListChecked() {
    return document.querySelectorAll("input[type='checkbox']:not([id='all-posts']):not([disabled]):checked")
}


const buttonTranslate = document.querySelector('#traduire-sans-migraine-bulk-translate');

function displayCountCheckedToButton() {
    const checkedCheckboxes = getCheckboxesListChecked().length;
    if (!buttonTranslate) {
        return;
    }
    if (checkedCheckboxes > 1) {
        buttonTranslate.innerHTML = buttonTranslate.dataset.defaultPlural.replace("%var%", checkedCheckboxes.toString());
    } else if (checkedCheckboxes === 1) {
        buttonTranslate.innerHTML = buttonTranslate.dataset.defaultSingular;
    } else {
        buttonTranslate.innerHTML = buttonTranslate.dataset.defaultNone;
    }
    buttonTranslate.disabled = (checkedCheckboxes === 0);
}


const allCheckboxes = getCheckboxesList();
const globalCheckbox = document.querySelector("#all-posts");
const handleOnChange = (checkbox) => {
    displayCountCheckedToButton();

};
const updateDisplayGlobalCheckbox = () => {
    const checkedCheckboxes = getCheckboxesListChecked().length;
    globalCheckbox.checked = (allCheckboxes.length === checkedCheckboxes);
    globalCheckbox.indeterminate = checkedCheckboxes > 0 && !globalCheckbox.checked;
    displayCountCheckedToButton();
};
globalCheckbox.addEventListener('change', () => {
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = globalCheckbox.checked;
        handleOnChange(checkbox);
    });
})


let lastChecked = null;
allCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        handleOnChange(checkbox);
        updateDisplayGlobalCheckbox();
    });
    checkbox.addEventListener('click', (e) => {
        if (e.shiftKey) {
            let inBetween = false;
            allCheckboxes.forEach(checkbox => {
                if (checkbox === lastChecked || checkbox === e.target) {
                    inBetween = !inBetween;
                }
                if (inBetween) {
                    checkbox.checked = e.target.checked;
                    handleOnChange(checkbox);
                }
            });
        }
        lastChecked = checkbox;
    });
});
updateDisplayGlobalCheckbox();

buttonTranslate.addEventListener('click', async (e) => {
    e.preventDefault();
    const checkedCheckboxes = getCheckboxesListChecked();
    if (checkedCheckboxes.length === 0) {
        return;
    }
    const ids = Array.from(checkedCheckboxes).map(checkbox => checkbox.id.replace("post-", ""));
    setButtonLoading(buttonTranslate);
    await translatePosts(ids);
    stopButtonLoading(buttonTranslate);
});
