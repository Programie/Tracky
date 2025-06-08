import {createPopper} from "@popperjs/core";
import * as DateRangePicker from "daterangepicker";
import { DateOrString } from "daterangepicker";
import * as moment from "moment";
import { tr } from "./utils";

function configureDateRangePicker() {
    let dateRangeContainer = document.querySelector("#history-date-selection") as HTMLElement;
    if (dateRangeContainer === null) {
        return;
    }

    let rangesMap: { [name: string]: [DateOrString, DateOrString] } = {
        "date-ranges.last-7-days": [moment().subtract(6, "days"), moment()],
        "date-ranges.last-30-days": [moment().subtract(29, "days"), moment()],
        "date-ranges.this-month": [moment().startOf("month"), moment().endOf("month")],
        "date-ranges.last-month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],
        "date-ranges.this-year": [moment().startOf("year"), moment().endOf("year")],
        "date-ranges.last-year": [moment().subtract(1, "year").startOf("year"), moment().subtract(1, "year").endOf("year")],
        "date-ranges.all": [null, null]
    };

    let ranges: { [name: string]: [DateOrString, DateOrString] } = {};

    Object.entries(rangesMap).forEach(([key, range]: [string, [DateOrString, DateOrString]]) => {
        ranges[tr(key)] = range;
    });

    new DateRangePicker(dateRangeContainer, {
        startDate: moment(dateRangeContainer.dataset.startdate),
        endDate: moment(dateRangeContainer.dataset.enddate),
        opens: "right",
        ranges: ranges,
        alwaysShowCalendars: true,
        cancelButtonClasses: "btn btn-sm btn-secondary",
        locale: {
            customRangeLabel: tr("date-ranges.custom"),
            applyLabel: tr("modal.ok"),
            cancelLabel: tr("modal.cancel")
        }
    }, (startDate, endDate) => {
        let searchParams = new URLSearchParams(document.location.search);

        searchParams.delete("page");

        if (startDate.isValid() && endDate.isValid()) {
            searchParams.set("startdate", startDate.format("YYYY-MM-DD"));
            searchParams.set("enddate", endDate.format("YYYY-MM-DD"));
        } else {
            searchParams.delete("startdate");
            searchParams.delete("enddate");
        }

        document.location.search = searchParams.toString();
    });
}

document.addEventListener("DOMContentLoaded", () => {
    let tooltipElement: HTMLElement = document.querySelector("#history-edit-tooltip");
    let activeHistoryEntry: DOMStringMap = null;

    configureDateRangePicker();

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
