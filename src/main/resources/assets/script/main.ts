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
});