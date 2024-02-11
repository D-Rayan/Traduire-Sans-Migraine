function initTooltips() {
    const tooltips = document.querySelectorAll('.traduire-sans-migraine-tooltip');
    tooltips.forEach(tooltip => {
        if (tooltip.dataset.initilized) {
            return;
        }
        tooltip.dataset.initilized = true;
        const tooltipContent = tooltip.querySelector('.traduire-sans-migraine-tooltip-content');
        tooltip.addEventListener('mouseenter', () => {
            tooltipContent.classList.add("traduire-sans-migraine-tooltip-content--visible");
        });
        tooltip.addEventListener('mouseleave', () => {
            tooltipContent.classList.remove("traduire-sans-migraine-tooltip-content--visible");
        });
    });
}