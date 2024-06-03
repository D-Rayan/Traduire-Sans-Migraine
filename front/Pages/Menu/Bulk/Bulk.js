
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

async function translatePosts(ids) {
    await fetch(`${tsm.url}add_items`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ids, languageTo: document.querySelector("#languageToHidden").value }),
    });
}
async function getQueueHTML() {
    const response = await fetch(`${tsm.url}display_queue`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    });
    return response.text();
}
let queueRefreshingInterval = null;
function initQueueRefreshing() {
    queueRefreshingInterval = setInterval(async () => {
        const queueHTML = await getQueueHTML();
        const isCurrentlyDisplayed = document.querySelector(".bulk-queue-items").classList.contains("visible");
        document.querySelector("#queue-container").innerHTML = queueHTML;
        if (isCurrentlyDisplayed) {
            document.querySelector(".bulk-queue-items").classList.add("visible");
        }
        addListenerToButtonDisplayQueue();
    }, 5000);
}

function addListenerToButtonDisplayQueue() {
    const buttonDisplayQueue = document.querySelector('#traduire-sans-migraine-bulk-queue-display');
    if (buttonDisplayQueue) {
        buttonDisplayQueue.addEventListener('click', async (e) => {
            e.preventDefault();
            const isCurrentlyDisplayed = document.querySelector(".bulk-queue-items").classList.contains("visible");
            if (isCurrentlyDisplayed) {
                document.querySelector(".bulk-queue-items").classList.remove("visible");
            } else {
                document.querySelector(".bulk-queue-items").classList.add("visible");
            }
        });
    }
}
function stopQueueRefreshing() {
    clearInterval(queueRefreshingInterval);
}
// initQueueRefreshing();

buttonTranslate.addEventListener('click', async (e) => {
    e.preventDefault();
    const checkedCheckboxes = getCheckboxesListChecked();
    if (checkedCheckboxes.length === 0) {
        return;
    }
    const ids = Array.from(checkedCheckboxes).map(checkbox => checkbox.id.replace("post-", ""));
    setButtonLoading(buttonTranslate);
    await translatePosts(ids);
    stopQueueRefreshing();
    initQueueRefreshing();
    stopButtonLoading(buttonTranslate);
});
addListenerToButtonDisplayQueue();