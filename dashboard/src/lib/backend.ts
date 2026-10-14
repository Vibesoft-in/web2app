import { cookies } from "next/headers";

const BACKEND_URL = process.env.BACKEND_API_URL || "http://127.0.0.1:8000";

export class ApiError extends Error {
  constructor(
    public status: number,
    public data: any,
    message?: string
  ) {
    super(message || data?.error?.message || data?.message || "An API error occurred");
    this.name = "ApiError";
  }
}

/**
 * Perform a server-side request to the Laravel backend using httpOnly cookie auth.
 */
export async function backendFetch(endpoint: string, options: RequestInit = {}) {
  const cookieStore = cookies();
  const token = cookieStore.get("auth_token")?.value;

  const url = `${BACKEND_URL.replace(/\/$/, "")}/${endpoint.replace(/^\//, "")}`;

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (!(options.body instanceof FormData) && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const response = await fetch(url, {
    ...options,
    headers,
    cache: "no-store",
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
    throw new ApiError(response.status, data);
  }

  return data;
}

export function getBackendBaseUrl(): string {
  return BACKEND_URL;
}
