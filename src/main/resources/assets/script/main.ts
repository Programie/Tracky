import "./common";
import "./history";
import "./library-management";
import "./view";

// @ts-ignore
import missingImagePoster from "../images/missing-image-poster.svg";
// @ts-ignore
import missingImageWide from "../images/missing-image-wide.svg";

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".season-dropdown").forEach((dropdown) => {
        dropdown.addEventListener("shown.bs.dropdown", () => {
            let menu = dropdown.querySelector(".dropdown-menu");
            let activeItem = menu?.querySelector(".active");

            activeItem?.scrollIntoView({
                block: "center",
                behavior: "instant"
            });
        });
    });

    document.querySelectorAll("img.image-poster").forEach((element) => {
        element.addEventListener("error", () => {
            (element as HTMLImageElement).src = missingImagePoster;
        });
    });

    document.querySelectorAll("img.image-wide").forEach((element) => {
        element.addEventListener("error", () => {
            (element as HTMLImageElement).src = missingImageWide;
        });
    });
});
