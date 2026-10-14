import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import { getBackendBaseUrl } from "@/lib/backend";

export async function GET() {
  try {
    const cookieStore = cookies();
    const token = cookieStore.get("auth_token")?.value;
    const backendUrl = getBackendBaseUrl();

    if (!token) {
      return NextResponse.json({ user: null }, { status: 401 });
    }

    const response = await fetch(`${backendUrl}/api/auth/me`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    });

    const data = await response.json();

    if (!response.ok) {
      const res = NextResponse.json({ user: null }, { status: 401 });
      res.cookies.delete("auth_token");
      return res;
    }

    return NextResponse.json(data);
  } catch {
    return NextResponse.json(
      { error: { code: "SERVER_ERROR", message: "Failed to verify session" } },
      { status: 500 }
    );
  }

}
