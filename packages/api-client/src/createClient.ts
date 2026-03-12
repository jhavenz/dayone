export interface DayOneClientConfig {
  baseUrl: string;
  token?: string;
  productSlug: string;
  headers?: Record<string, string>;
}

export interface DayOneClient {
  get<T = unknown>(path: string, options?: RequestInit): Promise<T>;
  post<T = unknown>(path: string, body?: unknown, options?: RequestInit): Promise<T>;
  put<T = unknown>(path: string, body?: unknown, options?: RequestInit): Promise<T>;
  delete<T = unknown>(path: string, options?: RequestInit): Promise<T>;
}

export function createClient(config: DayOneClientConfig): DayOneClient {
  const { baseUrl, token, productSlug, headers: customHeaders = {} } = config;

  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Product': productSlug,
    ...customHeaders,
  };

  if (token) {
    defaultHeaders['Authorization'] = `Bearer ${token}`;
  }

  async function request<T>(method: string, path: string, body?: unknown, options?: RequestInit): Promise<T> {
    const url = `${baseUrl.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
    const response = await fetch(url, {
      method,
      headers: { ...defaultHeaders, ...options?.headers as Record<string, string> },
      body: body ? JSON.stringify(body) : undefined,
      ...options,
    });

    if (!response.ok) {
      throw new Error(`DayOne API error: ${response.status} ${response.statusText}`);
    }

    return response.json() as Promise<T>;
  }

  return {
    get: <T = unknown>(path: string, options?: RequestInit) => request<T>('GET', path, undefined, options),
    post: <T = unknown>(path: string, body?: unknown, options?: RequestInit) => request<T>('POST', path, body, options),
    put: <T = unknown>(path: string, body?: unknown, options?: RequestInit) => request<T>('PUT', path, body, options),
    delete: <T = unknown>(path: string, options?: RequestInit) => request<T>('DELETE', path, undefined, options),
  };
}
