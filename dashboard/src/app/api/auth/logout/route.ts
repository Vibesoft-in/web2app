import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import { getBackendBaseUrl } from "@/lib/backend";

export async function POST() {
  try {
    const cookieStore = cookies();
    const token = cookieStore.get("auth_token")?.value;
    const backendUrl = getBackendBaseUrl();

    if (token) {
      await fetch(`${backendUrl}/api/auth/logout`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      }).catch(() => {});
    }

    const res = NextResponse.json({
      message: "Logged out successfully",
    });

    res.cookies.delete("auth_token");

    return res;
  } catch {
    const res = NextResponse.json({ message: "Logged out" });
    res.cookies.delete("auth_token");
    return res;
  }

}
