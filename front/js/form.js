document.addEventListener('DOMContentLoaded', function () {
    const selectLanguage = document.querySelector('#traduire_sans_migraine_language');
    if (!selectLanguage) {
        return;
    }
    selectLanguage.addEventListener('change', () => {
        const language = selectLanguage.value;
        const rowTranslationHidden = document.querySelector('.row-translation.hidden');
        rowTranslationHidden.classList.remove("hidden");
        const link = rowTranslationHidden.querySelector("a");
        if (link) {
            link.remove();
        }
        const rowTranslation = document.querySelector('.row-translation[data-language-id="' + language + '"]');
        rowTranslation.classList.add("hidden");
        rowTranslationHidden.querySelectorAll("input").forEach((input) => {
            input.value = "";
        });
    });

    function getIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('tag_ID') || urlParams.get('post');
    }

    function getTaxonomyFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('taxonomy');
    }

    function initAutocomplete(inputText, inputId, dataLanguageId, dataType, nonce, currentLanguageId) {
        jQuery(inputText).autocomplete({
            minLength: 0,
            source: function (request, response) {
                let url = `${window.ajaxurl}?action=tsm_wc_get_${dataType}_not_linked&languageId=${dataLanguageId}&wpNonce=${nonce}&term=${request.term}&currentId=${getIdFromUrl()}&currentLanguageId=${currentLanguageId}`;
                const taxonomy = getTaxonomyFromUrl();
                if (taxonomy) {
                    url += `&taxonomy=${taxonomy}`;
                }
                
                jQuery.ajax({
                    url,
                    success: function (data) {
                        response(data.data);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        response([]);
                    }
                });
                inputId.setAttribute("value", "");
            },
            select: function (event, ui) {
                inputId.setAttribute("value", ui.item.id);
                inputText.setAttribute("value", ui.item.label);
                Object.keys(ui.item.translations).forEach((languageCode) => {
                    const label = ui.item.translations[languageCode].label;
                    const id = ui.item.translations[languageCode].id;
                    const rowTranslation = document.querySelector('.row-translation[data-language-code="' + languageCode + '"]');
                    if (rowTranslation) {
                        rowTranslation.querySelector('input[type="text"]').setAttribute("value", label);
                        rowTranslation.querySelector('input[type="hidden"]').setAttribute("value", id);
                    }
                });
                return ui.item.label;
            },
            close: function () {
                if (inputId.value === "") {
                    inputText.value = "";
                }
            },
            render: function (ul, item) {
                return jQuery("<li>")
                    .append("<div>" + item.label + "</div>")
                    .appendTo(ul);
            }
        });
    }

    // use Autocomplete on all input text from rowTranslation
    const rowTranslations = document.querySelectorAll('.row-translation');
    rowTranslations.forEach((rowTranslation) => {
        const inputText = rowTranslation.querySelector('input[type="text"]');
        const inputId = rowTranslation.querySelector('input[type="hidden"]');
        const dataLanguageId = rowTranslation.getAttribute("data-language-id");
        const dataType = rowTranslation.getAttribute("data-type");
        const nonce = rowTranslation.getAttribute("data-nonce");
        const currentLanguageId = selectLanguage.value;
        if (inputText && inputId && dataLanguageId && nonce && currentLanguageId) {
            initAutocomplete(inputText, inputId, dataLanguageId, dataType, nonce, currentLanguageId);
        }
    });
});