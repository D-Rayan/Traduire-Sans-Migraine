import {CustomWindow} from "../../../Main/WindowTSM";

declare let window: CustomWindow;

(() => {

    const buttonSave = document.querySelector("#save-settings");

    if (buttonSave && buttonSave instanceof HTMLElement) {
        buttonSave.addEventListener("click", async () => {
            const settings = new FormData();
            document.querySelector(".settings").querySelectorAll("input, select").forEach(input => {
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }
                settings.append(input.id, input.checked ? "true" : "false");
            });
            setButtonLoading("#save-settings");
            const fetchResponse = await fetch(`${window.tsmVariables.url}update_settings`, {
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
    if (buttonUpgrade && buttonUpgrade instanceof HTMLElement) {
        buttonUpgrade.addEventListener("click", async () => {
            const href = buttonUpgrade.dataset.href;
            window.open(href, "_blank");
        });
    }

    initTooltips();
})();