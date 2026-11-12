const config = window.Illuminar || {};

export const basePath = (config.basePath || '/illuminar').replace(/\/$/, '');

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Build query string, arrays are sent as key[]=a&key[]=b
 */
function toQuery(params) {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach(item => query.append(`${key}[]`, item));
        } else if (value !== null && value !== undefined && value !== '') {
            query.append(key, value);
        }
    });

    return query.toString();
}

export async function fetchEntries(params, signal) {
    const response = await fetch(`${basePath}/data?${toQuery(params)}`, {
        headers: {Accept: 'application/json'},
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        throw new Error(`Illuminar: request failed with status ${response.status}`);
    }

    return response.json();
}

export async function clearEntries() {
    const response = await fetch(`${basePath}/clear`, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Illuminar: request failed with status ${response.status}`);
    }
}
