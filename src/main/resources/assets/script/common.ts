import "@popperjs/core";
import "bootstrap";

import {Tooltip} from "bootstrap";

import "./popper";
import "../style/main.scss";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[title]").forEach(element => {
        let tooltip = new Tooltip(element);

        element.addEventListener("click", () => {
            tooltip.hide();
        });
    });
});