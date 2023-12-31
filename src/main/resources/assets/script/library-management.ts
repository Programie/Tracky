import {createPopper} from '@popperjs/core';
import {createToast} from "./toast";
import {tr} from "./utils";

function deleteItem(url: string) {
    fetch(url, {
        method: "DELETE"
    }).then(async (response) => {
        if (response.ok) {
            document.location.reload();
        } else {
            createToast(tr("library-management.delete-item-failed"), tr(`library-management.errors.${await (await response.json())["error"]}`), "danger");
        }
    }, (reason) => {
        createToast(tr("library-management.delete-item-failed"), reason, "danger");
    });
}

document.addEventListener("DOMContentLoaded", () => {
    let deleteItemTooltipElement: HTMLElement = document.querySelector("#library-management-delete-item");
    let activeDeleteItem: DOMStringMap = null;

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
                deleteItem(`/shows/${activeDeleteItem.item}`);
                break;

            case "movie":
                deleteItem(`/movies/${activeDeleteItem.item}`);
                break;
        }
    });

    document.querySelector("#library-management-delete-item-cancel")?.addEventListener("click", () => {
        deleteItemTooltipElement.style.display = "none";
        activeDeleteItem = null;
    });
});