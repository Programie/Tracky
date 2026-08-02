export interface Dictionary {
  [key: string]: string;
}

export function tr(key: string, placeholders: Dictionary = {}): string {
    let string = document.querySelector<HTMLOptionElement>(`#translations > option[value="${key}"]`)?.text ?? key;

    Object.entries(placeholders).forEach(([name, value]) => {
        string = string.replaceAll(`{${name}}`, value);
    });

    return string;
}
