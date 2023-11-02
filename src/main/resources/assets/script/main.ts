import "./common";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".add-episode-view").forEach((element: HTMLElement) => {
        element.addEventListener("click", () => {
            let dataSet = element.dataset;

            fetch(`/shows/${dataSet.showId}/seasons/${dataSet.season}/episodes/${dataSet.episode}/views`, {
                method: "POST"
            }).then(() => {
                document.location.reload();
            })
        });
    });
});