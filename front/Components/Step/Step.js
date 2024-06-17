function setStep({
    percentage,
    html,
    div,
    status = "progress"}) {
    const indicatorPercentage = div.querySelector(".indicator-percentage");
    const indicatorHtml = div.querySelector(".indicator-text");
    const progressBar = div.querySelector(".progress-bar");
    const progressBarFill = progressBar.querySelector(".progress-bar-fill");
    indicatorHtml.innerHTML = html;
    indicatorPercentage.innerText = `${percentage}%`;
    progressBarFill.style.width = `${percentage}%`;
    progressBarFill.classList.remove("progress-bar-fill--progress", "progress-bar-fill--done", "progress-bar-fill--error");
    progressBarFill.classList.add(`progress-bar-fill--${status}`);
}