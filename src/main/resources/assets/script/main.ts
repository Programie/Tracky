import "./common";
import "./history";

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
});