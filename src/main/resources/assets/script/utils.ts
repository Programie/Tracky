export function tr(key: string): string {
    return document.querySelector<HTMLOptionElement>(`#translations > option[value="${key}"]`)?.text ?? key;
}
