import {Toast} from "bootstrap";

export function createToast(title: string, body: string, color: string = null) {
    let containerElement = document.querySelector("#toast-container");

    let toastElement = document.createElement("div");
    toastElement.className = "toast";
    containerElement.appendChild(toastElement);

    let toastHeaderElement = document.createElement("div");
    toastHeaderElement.className = "toast-header";
    if (color !== null) {
        toastHeaderElement.classList.add(`text-bg-${color}`);
    }
    toastElement.appendChild(toastHeaderElement);

    let titleElement = document.createElement("strong");
    titleElement.className = "me-auto";
    titleElement.innerText = title;
    toastHeaderElement.appendChild(titleElement);

    let closeButtonElement = document.createElement("button");
    closeButtonElement.type = "button";
    closeButtonElement.className = "btn-close";
    closeButtonElement.dataset.bsDismiss = "toast";
    toastHeaderElement.appendChild(closeButtonElement);

    let toastBodyElement = document.createElement("div");
    toastBodyElement.className = "toast-body";
    toastBodyElement.innerText = body;
    toastElement.appendChild(toastBodyElement);

    toastElement.addEventListener("hidden.bs.toast", () => {
        containerElement.removeChild(toastElement);
    });

    Toast.getOrCreateInstance(toastElement).show();
}