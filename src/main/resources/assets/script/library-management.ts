import {createPopper} from '@popperjs/core';

document.addEventListener("DOMContentLoaded", () => {
    let deleteItemTooltipElement: HTMLElement = document.querySelector("#library-management-delete-item");
    let activeDeleteItem: DOMStringMap = null;

    document.querySelectorAll(".library-management-delete-item-button").forEach((buttonElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, deleteItemTooltipElement, {
                placement: "bottom"
            });

            deleteItemTooltipElement.style.display = "block";
            activeDeleteItem = (buttonElement.closest("tr") as HTMLElement)?.dataset;
        });
    });

    document.querySelector("#library-management-delete-item-confirm")?.addEventListener("click", () => {
        if (activeDeleteItem === null) {
            return;
        }

        switch (activeDeleteItem.type) {
            case "show":
                fetch(`/shows/${activeDeleteItem.item}`, {
                    method: "DELETE"
                }).then(() => {
                    document.location.reload();
                });
                break;

            case "movie":
                fetch(`/movies/${activeDeleteItem.item}`, {
                    method: "DELETE"
                }).then(() => {
                    document.location.reload();
                });
                break;
        }
    });

    document.querySelector("#library-management-delete-item-cancel")?.addEventListener("click", () => {
        deleteItemTooltipElement.style.display = "none";
        activeDeleteItem = null;
    });
});