import "@popperjs/core";
import "bootstrap";

import {Tooltip} from "bootstrap";

import "./popper";
import "../style/main.scss";
import "../style/image-modal.scss";
import "../images/app-icon.svg";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[title]").forEach(element => {
        let tooltip = new Tooltip(element);

        element.addEventListener("click", () => {
            tooltip.hide();
        });
    });

    document.querySelectorAll<HTMLImageElement>(".fullscreen-image").forEach((element) => {
        element.addEventListener("click", () => {
            let imageModal = document.querySelector("#image-modal") as HTMLElement;
            let imageElement = imageModal.querySelector(".image-modal-img") as HTMLElement;

            imageModal.classList.add("show");
            imageElement.style.backgroundImage = `url(${element.src})`;

            document.body.style.overflow = "hidden";
        });
    });

    document.querySelectorAll<HTMLElement>(".image-modal").forEach((element) => {
        element.addEventListener("click", () => {
            (element.closest(".image-modal") as HTMLElement).classList.remove("show");
            document.body.style.overflow = "";
        });
    });
});
