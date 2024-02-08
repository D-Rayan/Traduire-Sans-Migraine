function renderLoadingIndicator() {
    return `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; background: none; display: block; shape-rendering: auto;" width="200px" height="200px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
<path d="M10 50A40 40 0 0 0 90 50A40 42 0 0 1 10 50" fill="#000000" stroke="none">
  <animateTransform attributeName="transform" type="rotate" dur="1.25s" repeatCount="indefinite" keyTimes="0;1" values="0 50 51;360 50 51"></animateTransform>
</path></svg>`;
}

function renderSuccessIndicator() {
    return `<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9 12L10.5 13.5V13.5C10.7761 13.7761 11.2239 13.7761 11.5 13.5V13.5L15 10" stroke="#323232" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>`;
}

function renderErrorIndicator() {
    return `<svg width="800px" height="800px" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.5 16V14.5M12.5 9V13M20.5 12.5C20.5 16.9183 16.9183 20.5 12.5 20.5C8.08172 20.5 4.5 16.9183 4.5 12.5C4.5 8.08172 8.08172 4.5 12.5 4.5C16.9183 4.5 20.5 8.08172 20.5 12.5Z" stroke="#121923" stroke-width="1.2"/>
</svg>`;
}

function startStep(stepList) {
    setStepProgress(stepList, 1, "current");
}

function setStepAsSuccess(stepList) {
    const current = stepList.querySelector("li.current");
    if (!current) {
        return;
    }
    current.classList.replace("current", "success");
    current.querySelector("span").innerHTML = renderSuccessIndicator();
}

function setAllStepAsSuccess(stepList) {
    stepList.querySelectorAll("li").forEach(li => {
        li.classList.add("success");
        li.classList.remove("current");
        li.querySelector("span").innerHTML = renderSuccessIndicator();
    });
}
function setStepAsError(stepList) {
    const current = stepList.querySelector("li.current");
    if (!current) {
        return;
    }
    current.classList.replace("current", "error");
    current.querySelector("span").innerHTML = renderErrorIndicator();
}

function setStepCurrent(stepList, stepNumber) {
    setStepProgress(stepList, stepNumber, "current");
}

function goNextStep(stepList) {
    const current = stepList.querySelector("li.current");
    if (!current) {
        return;
    }
    const next = current.nextElementSibling;
    if (!next) {
        setStepAsSuccess(stepList);
        return;
    }
    current.classList.replace("current", "success");
    current.querySelector("span").innerHTML = renderSuccessIndicator();
    next.classList.add("current");
    next.querySelector("span").innerHTML = renderLoadingIndicator();
}


function setStepProgress(stepList, stepNumber, state) {
    const indexStepNumber = stepNumber - 1;
    stepList.querySelectorAll("li").forEach((li, index) => {
        if (index < indexStepNumber) {
            li.classList.add("success");
            li.querySelector("span").innerHTML = renderSuccessIndicator();
        } else if (index === indexStepNumber) {
            li.classList.remove("success", "current", "error");
            if (state === "error") {
                li.classList.add("error");
                li.querySelector("span").innerHTML = renderErrorIndicator();
            } else {
                li.classList.add("current");
                li.querySelector("span").innerHTML = renderLoadingIndicator();
            }
        } else {
            li.classList.remove("success", "current", "error");
            li.querySelector("span").innerHTML = index + 1;
        }
    });
}