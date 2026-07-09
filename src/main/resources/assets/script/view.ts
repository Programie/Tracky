import {createPopper} from "@popperjs/core";

function useNow() {
    let dateTimeElement = document.querySelector("#add-view-tooltip-datetime") as HTMLInputElement;

    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    dateTimeElement.value = now.toISOString().slice(0, 16);
}

function addView(url: string, date: Date) {
    return fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            "timestamp": date.toISOString()
        })
    });
}

function addEpisodeView(url: string, date: Date, entry: DOMStringMap): void {
    addView(url, date)
        .then((response) => {
            if (!response.ok) {
                throw new Error("Unable to add episode view");
            }

            return response.text();
        })
        .then((html) => {
            let lastWatchedElement = document.querySelector(
                `.view-last-watched[data-type="episode"][data-show-id="${entry.showId}"][data-season="${entry.season}"][data-episode="${entry.episode}"]`
            );

            if (lastWatchedElement !== null) {
                lastWatchedElement.innerHTML = html;
            }
        });
}

function addViewAndReload(url: string, date: Date): void {
    addView(url, date).then((response) => {
        if (response.ok) {
            document.location.reload();
        }
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
    let addViewTooltipElement = document.querySelector<HTMLElement>("#add-view-tooltip")!;
    let removeSeasonViewTooltipElement = document.querySelector<HTMLElement>("#season-remove-view-tooltip")!;
    let activeAddViewEntry: DOMStringMap | null = null;
    let activeRemoveSeasonViewEntry: DOMStringMap | null = null;

    document.querySelectorAll<HTMLElement>(".add-view").forEach((buttonElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, addViewTooltipElement, {
                placement: "bottom"
            });

            addViewTooltipElement.style.display = "block";
            activeAddViewEntry = buttonElement.dataset;

            useNow();
        });
    });

    document.querySelectorAll<HTMLElement>(".remove-view").forEach((buttonElement) => {
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
                addEpisodeView(`/shows/${activeAddViewEntry.showId}/seasons/${activeAddViewEntry.season}/episodes/${activeAddViewEntry.episode}/views`, dateTime, activeAddViewEntry);
                break;

            case "season":
                addViewAndReload(`/shows/${activeAddViewEntry.showId}/seasons/${activeAddViewEntry.season}/views`, dateTime);
                break;

            case "movie":
                addViewAndReload(`/movies/${activeAddViewEntry.movieId}/views`, dateTime);
                break;
        }

        // Hide the modal
        addViewTooltipElement.style.display = "none";
        activeAddViewEntry = null;
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
