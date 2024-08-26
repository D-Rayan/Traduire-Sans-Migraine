(() => {
    const deactivateTSM = document.querySelector("tr[data-slug*='traduire-sans-migraine'] .deactivate a");
    if (deactivateTSM) {
        deactivateTSM.addEventListener("click", async (e) => {
            e.preventDefault();
            const disableTSM = (shouldDeleteConfiguration = false) => {
                if (shouldDeleteConfiguration) {
                    window.location = deactivateTSM.href + "&delete_configuration=1";
                } else {
                    window.location = deactivateTSM.href;
                }
            }
            await tsmHandleRequestResponse(await fetch(`${tsmVariables.url}plugins_reasons_deactivate_render&wp_nonce=${deactivateTSM.dataset.wpnonce}`), (response) => {
                disableTSM();
            }, async (response) => {
                const modal = await response.text();
                const modalElement = addModalToBody(modal);
                injectFunctionOnModalReasonDeactivate(modalElement, disableTSM);
            });
        });
    }
})();