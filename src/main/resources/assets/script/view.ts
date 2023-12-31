import {createPopper} from "@popperjs/core";

function useNow() {
    let dateTimeElement = document.querySelector("#add-view-tooltip-datetime") as HTMLInputElement;

    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    dateTimeElement.value = now.toISOString().slice(0, 16);
}

function addView(url: string, date: Date) {
    fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            "timestamp": date.toISOString()
        })
    }).then(() => {
        document.location.reload();
    });
}

document.addEventListener("DOMContentLoaded", () => {
    let tooltipElement: HTMLElement = document.querySelector("#add-view-tooltip");
    let activeEntry: DOMStringMap = null;

    document.querySelectorAll(".add-view").forEach((buttonElement: HTMLElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, tooltipElement, {
                placement: "bottom"
            });

            tooltipElement.style.display = "block";
            activeEntry = buttonElement.dataset;

            useNow();
        });
    });

    document.querySelector("#add-view-tooltip-form")?.addEventListener("submit", (event) => {
        event.preventDefault();

        if (activeEntry === null) {
            return;
        }

        let dateTimeElement = document.querySelector("#add-view-tooltip-datetime") as HTMLInputElement;
        let dateTime = new Date(Date.parse(dateTimeElement.value));

        switch (activeEntry.type) {
            case "episode":
                addView(`/shows/${activeEntry.showId}/seasons/${activeEntry.season}/episodes/${activeEntry.episode}/views`, dateTime);
                break;

            case "movie":
                addView(`/movies/${activeEntry.movieId}/views`, dateTime);
                break;
        }
    });

    document.querySelector("#add-view-tooltip-now")?.addEventListener("click", () => {
        useNow();
    });

    document.querySelector("#add-view-tooltip-cancel")?.addEventListener("click", () => {
        tooltipElement.style.display = "none";
        activeEntry = null;
    });
});