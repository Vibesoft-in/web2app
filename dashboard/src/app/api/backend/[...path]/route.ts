import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";
import { getBackendBaseUrl } from "@/lib/backend";

async function forwardRequest(request: NextRequest, { params }: { params: { path: string[] } }) {
  try {
    const cookieStore = cookies();
    const token = cookieStore.get("auth_token")?.value;
    const backendUrl = getBackendBaseUrl();

    const path = (params.path || []).join("/");
    const search = request.nextUrl.search;
    const targetUrl = `${backendUrl}/api/${path}${search}`;

    const headers: Record<string, string> = {
      Accept: "application/json",
    };

    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }

    const contentType = request.headers.get("content-type");
    if (contentType) {
      headers["Content-Type"] = contentType;
    }

    const options: RequestInit = {
      method: request.method,
      headers,
    };

    if (["POST", "PUT", "PATCH", "DELETE"].includes(request.method)) {
      const bodyText = await request.text();
      if (bodyText) {
        options.body = bodyText;
      }
    }

    const backendResponse = await fetch(targetUrl, options);
    const responseContentType = backendResponse.headers.get("content-type") || "";

    if (responseContentType.includes("application/json")) {
      const data = await backendResponse.json();
      return NextResponse.json(data, { status: backendResponse.status });
    }

    const textData = await backendResponse.text();
    return new NextResponse(textData, {
      status: backendResponse.status,
      headers: { "Content-Type": responseContentType },
    });
  } catch (error: any) {
    return NextResponse.json(
      {
        error: {
          code: "PROXY_ERROR",
          message: error?.message || "Failed to communicate with API backend",
        },
      },
      { status: 502 }
    );
  }
}

export const GET = forwardRequest;
export const POST = forwardRequest;
export const PUT = forwardRequest;
export const PATCH = forwardRequest;
export const DELETE = forwardRequest;
