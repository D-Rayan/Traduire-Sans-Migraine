function initTooltips() {
    console.log("initTooltips");
    const tooltips = document.querySelectorAll('.traduire-sans-migraine-tooltip');
    tooltips.forEach(tooltip => {
        if (tooltip.dataset.initilized === "true") {
            return;
        }
        tooltip.dataset.initilized = "true";
        const realContent = tooltip.querySelector('.traduire-sans-migraine-tooltip-hoverable');
        const tooltipContent = tooltip.querySelector('.traduire-sans-migraine-tooltip-content');
        realContent.addEventListener('mouseenter', () => {
            tooltipContent.classList.add("traduire-sans-migraine-tooltip-content--visible");
        });
        const functionToRemoveTooltip = async () => {
            await new Promise(resolve => setTimeout(resolve, 100));
            // check if we are still out of the tooltip
            if (realContent.matches(':hover') || tooltipContent.matches(':hover')) {
                return;
            }
            tooltipContent.classList.remove("traduire-sans-migraine-tooltip-content--visible");
        }
        realContent.addEventListener('mouseleave', functionToRemoveTooltip);
        tooltipContent.addEventListener('mouseleave', functionToRemoveTooltip);
    });
}