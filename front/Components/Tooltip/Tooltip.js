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
        let copy = null;
        realContent.addEventListener('mouseenter', () => {
            if (copy) {
                return;
            }
            const rect = realContent.getBoundingClientRect();
            const positionX = rect.x + window.scrollX;
            const positionY = rect.y + rect.height + window.scrollY;
            copy = createDOMAtTheEnd(tooltipContent, positionX, positionY);
            copy.addEventListener('mouseleave', functionToRemoveTooltip);
        });
        const functionToRemoveTooltip = async () => {
            await new Promise(resolve => setTimeout(resolve, 100));
            if (!copy) {
                return;
            }
            // check if we are still out of the tooltip
            if (realContent.matches(':hover') || tooltipContent.matches(':hover') || copy.matches(':hover')) {
                return;
            }
            copy.remove();
            copy = null;
        }
        realContent.addEventListener('mouseleave', functionToRemoveTooltip);
    });
}

function createDOMAtTheEnd(div, positionX, positionY) {
    const copy = div.cloneNode(true);
    copy.id = `tooltip-${Date.now()}${Math.floor(Math.random() * 10000)}`;
    copy.style.position = "absolute";
    copy.style.left = `${positionX}px`;
    copy.style.top = `${positionY}px`;
    copy.classList.add('traduire-sans-migraine-tooltip-content--visible');
    document.body.appendChild(copy);
    return document.body.querySelector(`#${copy.id}`);
}