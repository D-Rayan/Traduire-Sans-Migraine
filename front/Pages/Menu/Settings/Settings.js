const buttonSave = document.querySelector("#save-settings");

if (buttonSave) {
    buttonSave.addEventListener("click", async () => {
        const settings = new FormData();
        document.querySelector(".settings").querySelectorAll("input, select").forEach(input => {
            settings.append(input.id, input.checked ? true : false);
        });
        setButtonLoading("#save-settings");
        const fetchResponse = await fetch(`${tsm.url}update_settings`, {
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

const buttonLogIn = document.querySelector("#log-in");
if (buttonLogIn) {
    buttonLogIn.addEventListener("click", async () => {
        const href = buttonLogIn.dataset.href;
        window.open(href, "_blank");
    });
}

const buttonUpgrade = document.querySelector("#upgrade-quota");
if (buttonUpgrade) {
    buttonUpgrade.addEventListener("click", async () => {
        const href = buttonUpgrade.dataset.href;
        window.open(href, "_blank");
    });
}