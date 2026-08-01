import {createPopper} from "@popperjs/core";

interface Dictionary {
  [key: string]: string;
}

document.addEventListener("DOMContentLoaded", () => {
    let username = document.querySelector<HTMLMetaElement>('meta[name="username"]')?.content;
    let addCollectionItemTooltipElement = document.querySelector<HTMLElement>("#add-collection-item-tooltip")!;
    let removeCollectionTooltipElement = document.querySelector<HTMLElement>("#remove-collection-tooltip")!;
    let collections: Dictionary | null = null;
    let activeAddCollectionItemEntry: DOMStringMap | null = null;
    let activeRemoveCollectionEntry: DOMStringMap | null = null;

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
        let collectionId = selectElement?.selectedOptions.item(0)?.value ?? null;

        if (collectionId === null) {
            return;
        }

        fetch(`/users/${username}/collections/${collectionId}/add-item`, {
            method: "POST",
            body: JSON.stringify({
                type: activeAddCollectionItemEntry.type,
                item: activeAddCollectionItemEntry.item
            })
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
        }).then(() => {
            document.location.href = `/users/${username}/collections?flash=success&action=remove&name=${encodeURIComponent(collectionName ?? "")}`;
        });
    });

    document.querySelector("#remove-collection-tooltip-cancel")?.addEventListener("click", () => {
        removeCollectionTooltipElement.style.display = "none";
        activeRemoveCollectionEntry = null;
    });
});
