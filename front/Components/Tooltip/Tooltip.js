function initTooltips() {
    console.log("initTooltips");
    const tooltips = document.querySelectorAll('.traduire-sans-migraine-tooltip');
    tooltips.forEach(tooltip => {
        console.log(tooltip);
        if (tooltip.dataset.initilized === "true") {
            return;
        }
        tooltip.dataset.initilized = "true";
        const tooltipContent = tooltip.querySelector('.traduire-sans-migraine-tooltip-content');
        tooltip.addEventListener('mouseenter', () => {
            tooltipContent.classList.add("traduire-sans-migraine-tooltip-content--visible");
        });
        tooltip.addEventListener('mouseleave', async (e) => {
            await new Promise(resolve => setTimeout(resolve, 100));
            // check if we are still out of the tooltip
            if (tooltip.matches(':hover')) {
                return;
            }
            tooltipContent.classList.remove("traduire-sans-migraine-tooltip-content--visible");
        });
    });
}