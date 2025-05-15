export function tr(key: string): string {
    let element = document.querySelector(`#translations > option[value="${key}"]`) as HTMLOptionElement;

    if (element === null) {
        return key;
    }

    return element.text;
}
