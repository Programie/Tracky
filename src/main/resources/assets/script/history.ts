import {createPopper} from '@popperjs/core';

document.addEventListener("DOMContentLoaded", () => {
    let tooltipElement: HTMLElement = document.querySelector("#history-edit-tooltip");
    let activeHistoryEntry: DOMStringMap = null;

    document.querySelectorAll(".history-edit-button").forEach((buttonElement: HTMLElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, tooltipElement, {
                placement: "bottom"
            });

            tooltipElement.style.display = "block";
            activeHistoryEntry = (buttonElement.closest(".col")?.querySelector("[data-entry-id]") as HTMLElement)?.dataset;
        });
    });

    document.querySelector("#history-edit-tooltip-cancel")?.addEventListener("click", () => {
        tooltipElement.style.display = "none";
        activeHistoryEntry = null;
    });

    document.querySelector("#history-edit-tooltip-remove-this-view")?.addEventListener("click", () => {
        if (activeHistoryEntry === null) {
            return;
        }

        switch (activeHistoryEntry.entryType) {
            case "episode":
                fetch(`/shows/${activeHistoryEntry.show}/seasons/${activeHistoryEntry.season}/episodes/${activeHistoryEntry.episode}/views/${activeHistoryEntry.entryId}`, {
                    method: "DELETE"
                }).then(() => {
                    document.location.reload();
                });
                break;

            case "movie":
                fetch(`/movies/${activeHistoryEntry.movie}/views/${activeHistoryEntry.entryId}`, {
                    method: "DELETE"
                }).then(() => {
                    document.location.reload();
                });
                break;
        }
    });

    document.querySelector("#history-edit-tooltip-remove-all-views")?.addEventListener("click", () => {
        if (activeHistoryEntry === null) {
            return;
        }

        switch (activeHistoryEntry.entryType) {
            case "episode":
                fetch(`/shows/${activeHistoryEntry.show}/seasons/${activeHistoryEntry.season}/episodes/${activeHistoryEntry.episode}/views/all`, {
                    method: "DELETE"
                }).then(() => {
                    document.location.reload();
                });
                break;

            case "movie":
                fetch(`/movies/${activeHistoryEntry.movie}/views/all`, {
                    method: "DELETE"
                }).then(() => {
                    document.location.reload();
                });
                break;
        }
    });
});