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

function removeViews(url: string) {
    fetch(url, {
        method: "DELETE"
    }).then(() => {
        document.location.reload();
    });
}

document.addEventListener("DOMContentLoaded", () => {
    let addViewTooltipElement: HTMLElement = document.querySelector("#add-view-tooltip");
    let removeSeasonViewTooltipElement: HTMLElement = document.querySelector("#season-remove-view-tooltip");
    let activeAddViewEntry: DOMStringMap = null;
    let activeRemoveSeasonViewEntry: DOMStringMap = null;

    document.querySelectorAll(".add-view").forEach((buttonElement: HTMLElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, addViewTooltipElement, {
                placement: "bottom"
            });

            addViewTooltipElement.style.display = "block";
            activeAddViewEntry = buttonElement.dataset;

            useNow();
        });
    });

    document.querySelectorAll(".remove-view").forEach((buttonElement: HTMLElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, removeSeasonViewTooltipElement, {
                placement: "bottom"
            });

            removeSeasonViewTooltipElement.style.display = "block";
            activeRemoveSeasonViewEntry = buttonElement.dataset;
        });
    });

    document.querySelector("#add-view-tooltip-form")?.addEventListener("submit", (event) => {
        event.preventDefault();

        if (activeAddViewEntry === null) {
            return;
        }

        let dateTimeElement = document.querySelector("#add-view-tooltip-datetime") as HTMLInputElement;
        let dateTime = new Date(Date.parse(dateTimeElement.value));

        switch (activeAddViewEntry.type) {
            case "episode":
                addView(`/shows/${activeAddViewEntry.showId}/seasons/${activeAddViewEntry.season}/episodes/${activeAddViewEntry.episode}/views`, dateTime);
                break;

            case "season":
                addView(`/shows/${activeAddViewEntry.showId}/seasons/${activeAddViewEntry.season}/views`, dateTime);
                break;

            case "movie":
                addView(`/movies/${activeAddViewEntry.movieId}/views`, dateTime);
                break;
        }
    });

    document.querySelector("#season-remove-view-tooltip-confirm")?.addEventListener("click", () => {
        if (activeRemoveSeasonViewEntry === null) {
            return;
        }

        switch (activeRemoveSeasonViewEntry.type) {
            case "season":
                removeViews(`/shows/${activeRemoveSeasonViewEntry.showId}/seasons/${activeRemoveSeasonViewEntry.season}/views/all`);
                break;
        }
    });

    document.querySelector("#season-remove-view-tooltip-cancel")?.addEventListener("click", () => {
        removeSeasonViewTooltipElement.style.display = "none";
        activeRemoveSeasonViewEntry = null;
    });

    document.querySelector("#add-view-tooltip-now")?.addEventListener("click", () => {
        useNow();
    });

    document.querySelector("#add-view-tooltip-cancel")?.addEventListener("click", () => {
        addViewTooltipElement.style.display = "none";
        activeAddViewEntry = null;
    });
});
