import { appConfig } from '../config';

/**
 * Error thrown for any non-2xx API response.
 * `errors` holds Laravel validation messages keyed by field (422 responses).
 */
export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }

    get isValidationError() {
        return this.status === 422;
    }
}

function buildUrl(path, query) {
    const url = new URL(`${appConfig.apiBaseUrl}${path}`, window.location.origin);

    Object.entries(query ?? {}).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value);
        }
    });

    return url;
}

async function request(method, path, { query, body } = {}) {
    let response;

    try {
        response = await fetch(buildUrl(path, query), {
            method,
            headers: {
                Accept: 'application/json',
                ...(body !== undefined && { 'Content-Type': 'application/json' }),
            },
            body: body !== undefined ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw new ApiError('Unable to reach the server. Check your connection and try again.', 0);
    }

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(payload?.message ?? `Request failed (${response.status}).`, response.status, payload?.errors ?? {});
    }

    return payload;
}

export const http = {
    get: (path, query) => request('GET', path, { query }),
    post: (path, body) => request('POST', path, { body }),
    put: (path, body) => request('PUT', path, { body }),
    delete: (path) => request('DELETE', path),
};
