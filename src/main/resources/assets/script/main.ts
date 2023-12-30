import "./common";
import "./history";
import "./library-management";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".add-view").forEach((element: HTMLElement) => {
        element.addEventListener("click", () => {
            let dataSet = element.dataset;

            switch (dataSet.type) {
                case "episode":
                    fetch(`/shows/${dataSet.showId}/seasons/${dataSet.season}/episodes/${dataSet.episode}/views`, {
                        method: "POST"
                    }).then(() => {
                        document.location.reload();
                    });
                    break;

                case "movie":
                    fetch(`/movies/${dataSet.movieId}/views`, {
                        method: "POST"
                    }).then(() => {
                        document.location.reload();
                    });
                    break;
            }
        });
    });

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