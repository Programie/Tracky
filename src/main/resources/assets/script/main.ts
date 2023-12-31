import "./common";
import "./history";
import "./library-management";
import "./view";

document.addEventListener("DOMContentLoaded", () => {
    let lazyBackgroundObserver = new IntersectionObserver((entries) => {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                let element = entry.target as HTMLElement;
                element.style.backgroundImage = `url(${element.dataset.imageUrl})`;
                lazyBackgroundObserver.unobserve(element);
            }
        });
    });

    document.querySelectorAll(".lazy-background").forEach(function (lazyBackground) {
        lazyBackgroundObserver.observe(lazyBackground);
    });
});