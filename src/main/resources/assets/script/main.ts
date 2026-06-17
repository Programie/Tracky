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

    document.querySelectorAll(".season-dropdown").forEach((dropdown) => {
        dropdown.addEventListener("shown.bs.dropdown", () => {
            let menu = dropdown.querySelector(".dropdown-menu");
            let activeItem = menu?.querySelector(".active");

            activeItem?.scrollIntoView({
                block: "center",
                behavior: "instant"
            });
        });
    });
});
