export class ClientApiError extends Error {
  constructor(
    public status: number,
    public code: string,
    public data: any,
    message: string
  ) {
    super(message);
    this.name = "ClientApiError";
  }
}

/**
 * Fetch wrapper for client-side React components communicating with the Next.js BFF.
 */
export async function apiFetch<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (!(options.body instanceof FormData) && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  const contentType = response.headers.get("content-type") || "";
  let data: any = null;

  if (contentType.includes("application/json")) {
    try {
      data = await response.json();
    } catch {
      data = null;
    }
  } else {
    data = await response.text();
  }

  if (!response.ok) {
    const code = data?.error?.code || data?.code || "REQUEST_FAILED";
    const message =
      data?.error?.message ||
      data?.message ||
      (typeof data === "string" ? data : `Request failed with status ${response.status}`);

    throw new ClientApiError(response.status, code, data, message);
  }

  return data as T;
}
