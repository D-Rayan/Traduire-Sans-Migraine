function getCheckboxesList() {
    return document.querySelectorAll("input[type='checkbox']:not([id='all-posts']):not([disabled])")
}

function getCheckboxesListChecked() {
    return document.querySelectorAll("input[type='checkbox']:not([id='all-posts']):not([disabled]):checked")
}

function displayCountCheckedToButton()  {
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

const updateDisplayGlobalCheckbox = () => {
    const checkedCheckboxes = getCheckboxesListChecked().length;
    globalCheckbox.checked = (allCheckboxes.length === checkedCheckboxes);
    globalCheckbox.indeterminate = checkedCheckboxes > 0 && !globalCheckbox.checked;
    displayCountCheckedToButton();
};

async function translatePosts(ids) {
    await fetch(`${tsm.url}add_items`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ids, languageTo: document.querySelector("#languageToHidden").value }),
    });
}
async function getQueueHTML(page = undefined) {
    if (!page) {
        const pageActive = document.querySelector(".bulk-queue-pagination-item.active");
        if (pageActive) {
            page = pageActive.dataset.page;
        } else {
            page = 1;
        }
    }
    const response = await fetch(`${tsm.url}display_queue&page=${page}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    });
    return response.text();
}

let queueRefreshingInterval = null;
function initQueueRefreshing() {
    queueRefreshingInterval = setInterval(loadQueue, 2000);
}

async function loadQueue(page = undefined) {
    const queueHTML = await getQueueHTML(page);
    const bulkQueueItems = document.querySelector(".bulk-queue-items");
    const isCurrentlyDisplayed = bulkQueueItems ? bulkQueueItems.classList.contains("visible") : false;
    document.querySelector("#queue-container").innerHTML = queueHTML;
    if (isCurrentlyDisplayed && document.querySelector(".bulk-queue-items")) {
        document.querySelector(".bulk-queue-items").classList.add("visible");
    }
    addListenerToQueue();
    handleAutoRefreshQueue();
}

function handleAutoRefreshQueue() {
    if (isQueueProgressing() && !queueRefreshingInterval) {
        initQueueRefreshing();
    } else if (!isQueueProgressing() && queueRefreshingInterval) {
        stopQueueRefreshing();
    }
}
function isQueueProgressing() {
    const playButton = document.querySelector("span[data-action='play-queue']");
    const pauseButton = document.querySelector("span[data-action='pause-queue']");
    return (playButton && playButton.classList.contains("disable") && pauseButton && !pauseButton.classList.contains("disable"));
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

function addListenerToQueue() {
    initTooltips();
    addListenerToButtonDisplayQueue();
    addListenerToActionsItems();
    addListenerToPagination();
}

function addListenerToPagination() {
    document.querySelectorAll(".bulk-queue-pagination-item").forEach(item => {
        if (item.classList.contains("active") || item.classList.contains("disable")) {
            return;
        }
        item.addEventListener('click', async (e) => {
            e.preventDefault();
            const page = item.dataset.page;
            await loadQueue(page);
        });
    });
}

function addListenerToActionsItems() {
    document.querySelectorAll("span[data-action]").forEach(span => {
        if ("initialized" in span.dataset) {
            return;
        }
        span.dataset.initialized = "true";
        span.addEventListener('click', async (e) => {
            e.preventDefault();
            const action = span.dataset.action;
            if (span.classList.contains("disable")) {
                return;
            }
            span.classList.add("disable");
            let response = null;

            if (action === "remove-from-queue") {
                const postId = span.dataset.postId;
                if (!postId) {
                    return;
                }
                response = await removeItemFromQueue(postId);
            } else if (action === "play-queue") {
                response = await playQueue();
            } else if (action === "pause-queue") {
                response = await pauseQueue();
            } else if (action === "delete-queue") {
                response = await deleteQueue();
            }
            if (response) {
                if (await tsmHandleRequestResponse(response, () => {
                    return false;
                }, async (r) => {
                    const {data, success} = await response.json();
                    Notification.show(data.title, data.message, data.logo, success ? "success" : "error");
                    if (success) {
                        await loadQueue();
                    }
                    return success;
                }) === false) {
                    span.classList.remove("disable");
                }
            }
        });
    });
}

async function removeItemFromQueue(postId) {
    return fetch(`${tsm.url}remove_item&postId=${postId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
    });
}

async function playQueue() {
    return fetch(`${tsm.url}restart_queue`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
    });
}

async function pauseQueue() {
    return fetch(`${tsm.url}pause_queue`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
    });
}

async function deleteQueue() {
    return fetch(`${tsm.url}delete_queue`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
    });
}


const allCheckboxes = getCheckboxesList();
const globalCheckbox = document.querySelector("#all-posts");
const buttonTranslate = document.querySelector('#traduire-sans-migraine-bulk-translate');
globalCheckbox.addEventListener('change', () => {
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = globalCheckbox.checked;
        displayCountCheckedToButton();
    });
})


let lastChecked = null;
allCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        displayCountCheckedToButton();
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
    await loadQueue();
    stopButtonLoading(buttonTranslate);
});
addListenerToQueue();
handleAutoRefreshQueue();