import "./common";
import "./history";
import "./library-management";
import "./view";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".add-item-to-library").forEach((element: HTMLElement) => {
        element.addEventListener("click", () => {
            let dataSet = element.dataset;

            fetch("/library/add", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: dataSet.id,
                    type: dataSet.type
                })
            }).then(() => {
                document.location.reload();
            });
        });
    });

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