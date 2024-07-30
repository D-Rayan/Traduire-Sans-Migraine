function injectFunctionOnModalReasonDeactivate(modal, disableTSM) {
    const skipDisable = modal.querySelector('#skip-disable');
    if (skipDisable) {
        skipDisable.addEventListener('click', () => {
            setButtonLoading(skipDisable);
            disableTSM();
        });
    }

    const buttonSendForm = modal.querySelector('#send-reason-deactivate');
    if (buttonSendForm) {
        updateButtonState(buttonSendForm);
        document.querySelectorAll("input[name='reason-deactivate']").forEach((radio) => {
            radio.addEventListener('change', () => {
                updateButtonState(buttonSendForm);
            });
        });
        buttonSendForm.addEventListener('click', async () => {
            const checkedRadio = document.querySelector("input[name='reason-deactivate']:checked");
            if (!checkedRadio) {
                return;
            }
            setButtonLoading(buttonSendForm);
            const shouldDeleteConfiguration = !!modal.querySelector("input#delete-configuration:checked");
            await sendAnswerReasonDeactivate(checkedRadio.value, buttonSendForm.dataset.wp_nonce);
            disableTSM(shouldDeleteConfiguration);
        });
    }
}

function updateButtonState(button) {
    const checkedRadio = document.querySelector("input[name='reason-deactivate']:checked");
    button.disabled = !checkedRadio;
}

function sendAnswerReasonDeactivate(reason, wp_nonce, shouldDeleteConfiguration) {
    return fetch(`${tsmVariables.url}send_reasons_deactivate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reason,
            wp_nonce,
            shouldDeleteConfiguration
        })
    });
}