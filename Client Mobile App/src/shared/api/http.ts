import { API_V1_BASE_URL } from '../config/api';

type HttpMethod = 'GET' | 'POST' | 'PATCH' | 'DELETE';

type QueryValue = string | number | boolean | null | undefined;

type RequestOptions = {
  method?: HttpMethod;
  path: string;
  token?: string | null;
  body?: unknown;
  query?: Record<string, QueryValue>;
};

type ErrorPayload = {
  message?: string;
  error?: {
    code?: string;
    message?: string;
  };
  errors?: Record<string, string[]>;
};

export class ApiError extends Error {
  status: number;
  payload?: ErrorPayload;

  constructor(status: number, message: string, payload?: ErrorPayload) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

function buildUrl(path: string, query?: Record<string, QueryValue>) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  const url = new URL(`${API_V1_BASE_URL}${normalizedPath}`);

  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '') {
        return;
      }

      url.searchParams.set(key, String(value));
    });
  }

  return url.toString();
}

function resolveApiErrorMessage(payload: ErrorPayload | undefined, fallback: string) {
  if (!payload) {
    return fallback;
  }

  if (payload.error?.message) {
    return payload.error.message;
  }

  if (payload.message) {
    return payload.message;
  }

  const firstFieldErrors = payload.errors ? Object.values(payload.errors)[0] : undefined;
  const firstFieldMessage = firstFieldErrors?.[0];

  return firstFieldMessage || fallback;
}

export async function httpRequest<T>({
  method = 'GET',
  path,
  token,
  body,
  query,
}: RequestOptions): Promise<T> {
  let response: Response;

  try {
    response = await fetch(buildUrl(path, query), {
      method,
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError(0, 'Не удалось подключиться к API. Проверьте адрес сервера и сеть.');
  }

  const text = await response.text();
  let payload: ErrorPayload | T | undefined;

  if (text) {
    try {
      payload = JSON.parse(text) as ErrorPayload | T;
    } catch {
      payload = undefined;
    }
  }

  if (!response.ok) {
    const errorPayload = payload as ErrorPayload | undefined;

    throw new ApiError(
      response.status,
      resolveApiErrorMessage(errorPayload, 'Ошибка сети. Попробуйте еще раз.'),
      errorPayload,
    );
  }

  return payload as T;
}
