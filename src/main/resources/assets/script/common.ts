import "@popperjs/core";
import "bootstrap";

import {Tooltip} from "bootstrap";

import "./popper";
import "../style/main.scss";
import "../style/image-modal.scss";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[title]").forEach(element => {
        let tooltip = new Tooltip(element);

        element.addEventListener("click", () => {
            tooltip.hide();
        });
    });

    document.querySelectorAll(".scaled-image, img").forEach((element: HTMLElement) => {
        element.addEventListener("click", () => {
            if (element.closest("a")) {
                return;
            }

            let imageUrl;

            if (element instanceof HTMLImageElement) {
                imageUrl = element.src;
            } else if (element.dataset.image) {
                imageUrl = element.dataset.image;
            } else {
                imageUrl = element.style.backgroundImage.match(/url\(["']?([^"']*)["']?\)/)[1];
            }

            if (imageUrl === null || imageUrl === undefined || imageUrl === "") {
                return;
            }

            let imageModal = document.querySelector("#image-modal") as HTMLElement;
            let imageElement = imageModal.querySelector(".image-modal-img") as HTMLElement;

            imageModal.classList.add("show");
            imageElement.style.backgroundImage = `url(${imageUrl})`;
        });
    });

    document.querySelectorAll(".image-modal").forEach((element: HTMLElement) => {
        element.addEventListener("click", () => {
            (element.closest(".image-modal") as HTMLElement).classList.remove("show");
        });
    });
});