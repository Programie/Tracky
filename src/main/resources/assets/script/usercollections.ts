import {createPopper} from "@popperjs/core";
import {createToast} from "./toast";
import {Dictionary, tr} from "./utils";

document.addEventListener("DOMContentLoaded", () => {
    let username = document.querySelector<HTMLMetaElement>('meta[name="username"]')?.content;
    let addCollectionItemTooltipElement = document.querySelector<HTMLElement>("#add-collection-item-tooltip")!;
    let removeCollectionTooltipElement = document.querySelector<HTMLElement>("#remove-collection-tooltip")!;
    let removeCollectionItemTooltipElement = document.querySelector<HTMLElement>("#remove-collection-item-tooltip")!;
    let collections: Dictionary | null = null;
    let activeAddCollectionItemEntry: DOMStringMap | null = null;
    let activeRemoveCollectionEntry: DOMStringMap | null = null;
    let activeRemoveCollectionItemEntry: DOMStringMap | null = null;

    function loadCollections() {
        if (collections === null) {
            fetch(`/users/${username}/collections.json`)
                .then((response) => response.json())
                .then((json) => {
                    collections = json as Dictionary;

                    let selectElement = document.querySelector<HTMLSelectElement>("#add-collection-item-tooltip-collection");

                    Object.entries(collections).forEach(([id, name]) => {
                        let optionElement = document.createElement("option");
                        optionElement.value = id;
                        optionElement.textContent = name;
                        selectElement?.add(optionElement);
                    });
                });
        }
    }

    document.querySelectorAll<HTMLElement>(".add-to-collection").forEach((buttonElement) => {
        buttonElement.addEventListener("click", () => {
            loadCollections();

            createPopper(buttonElement, addCollectionItemTooltipElement, {
                placement: "bottom"
            });

            addCollectionItemTooltipElement.style.display = "block";
            activeAddCollectionItemEntry = buttonElement.dataset;
        });
    });

    document.querySelector<HTMLFormElement>("#add-collection-item-tooltip-form")?.addEventListener("submit", (event) => {
        event.preventDefault();

        if (activeAddCollectionItemEntry === null) {
            return;
        }

        let selectElement = document.querySelector<HTMLSelectElement>("#add-collection-item-tooltip-collection");
        let selectedOption = selectElement?.selectedOptions.item(0);

        if (selectedOption === null) {
            return;
        }

        let collectionId = selectedOption?.value;
        let collectionName = selectedOption?.textContent;

        if (collectionId === null) {
            return;
        }

        fetch(`/users/${username}/collections/${collectionId}/add-item`, {
            method: "POST",
            body: JSON.stringify({
                type: activeAddCollectionItemEntry.type,
                item: activeAddCollectionItemEntry.item
            })
        }).then((response) => {
            if (response.ok) {
                createToast(tr("user.collections.add-item.header"), tr("user.collections.add-item.submit-response.success", {name: collectionName ?? ""}), "success");
            } else {
                createToast(tr("user.collections.add-item.header"), tr("user.collections.add-item.submit-response.error.unknown", {name: collectionName ?? ""}), "danger");
            }
        });

        // Hide the modal
        addCollectionItemTooltipElement.style.display = "none";
        activeAddCollectionItemEntry = null;
    });

    document.querySelector("#add-collection-item-tooltip-cancel")?.addEventListener("click", () => {
        addCollectionItemTooltipElement.style.display = "none";
        activeAddCollectionItemEntry = null;
    });

    document.querySelector("#rename-collection")?.addEventListener("click", () => {
        document.querySelectorAll("#collection-name, #rename-collection").forEach((element) => element.classList.add("d-none"));
        document.querySelector("#collection-rename-form")?.classList.remove("d-none");
    });

    document.querySelector("#collection-rename-cancel")?.addEventListener("click", () => {
        document.querySelectorAll("#collection-name, #rename-collection").forEach((element) => element.classList.remove("d-none"));

        let form = document.querySelector<HTMLFormElement>("#collection-rename-form");
        form?.classList.add("d-none");
        form?.reset();
    });

    document.querySelector("#remove-collection")?.addEventListener("click", () => {
        let buttonElement = document.querySelector<HTMLButtonElement>("#remove-collection")!;

        createPopper(buttonElement, removeCollectionTooltipElement, {
            placement: "bottom"
        });

        removeCollectionTooltipElement.style.display = "block";
        activeRemoveCollectionEntry = buttonElement.dataset;
    });

    document.querySelector("#remove-collection-tooltip-confirm")?.addEventListener("click", () => {
        if (activeRemoveCollectionEntry === null) {
            return;
        }

        let collectionName = activeRemoveCollectionEntry.name;

        fetch(`/users/${username}/collections/${activeRemoveCollectionEntry.id}`, {
            method: "DELETE"
        }).then((response) => {
            document.location.href = `/users/${username}/collections?flash=${response.ok ? "success" : "error"}&action=remove&name=${encodeURIComponent(collectionName ?? "")}`;
        });
    });

    document.querySelector("#remove-collection-tooltip-cancel")?.addEventListener("click", () => {
        removeCollectionTooltipElement.style.display = "none";
        activeRemoveCollectionEntry = null;
    });

    document.querySelectorAll<HTMLButtonElement>(".remove-collection-item").forEach((buttonElement) => {
        buttonElement.addEventListener("click", () => {
            createPopper(buttonElement, removeCollectionItemTooltipElement, {
                placement: "bottom"
            });

            removeCollectionItemTooltipElement.style.display = "block";
            activeRemoveCollectionItemEntry = buttonElement.dataset;
        });
    });

    document.querySelector("#remove-collection-item-tooltip-confirm")?.addEventListener("click", () => {
        if (activeRemoveCollectionItemEntry === null) {
            return;
        }

        let collectionId = document.querySelector<HTMLElement>("#collection-items")?.dataset.id;
        let itemName = activeRemoveCollectionItemEntry.itemName;

        fetch(`/users/${username}/collections/${collectionId}/${activeRemoveCollectionItemEntry.id}`, {
            method: "DELETE"
        }).then((response) => {
            document.location.href = `/users/${username}/collections/${collectionId}?flash=${response.ok ? "success" : "error"}&action=remove-item&name=${encodeURIComponent(itemName ?? "")}`;
        });
    });

    document.querySelector("#remove-collection-item-tooltip-cancel")?.addEventListener("click", () => {
        removeCollectionItemTooltipElement.style.display = "none";
        activeRemoveCollectionItemEntry = null;
    });
});
