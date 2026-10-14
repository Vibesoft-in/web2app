import { NextResponse } from "next/server";
import { getBackendBaseUrl } from "@/lib/backend";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const backendUrl = getBackendBaseUrl();

    const response = await fetch(`${backendUrl}/api/auth/register`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(body),
    });

    const data = await response.json();

    if (!response.ok) {
      return NextResponse.json(data, { status: response.status });
    }

    const token = data?.data?.token;
    const user = data?.data?.user;

    const res = NextResponse.json({
      message: data.message || "Registration successful",
      user,
    });

    if (token) {
      res.cookies.set("auth_token", token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        sameSite: "lax",
        path: "/",
        maxAge: 60 * 60 * 24 * 7, // 7 days
      });
    }

    return res;
  } catch (error: any) {
    return NextResponse.json(
      {
        error: {
          code: "SERVER_ERROR",
          message: error?.message || "Internal server error during registration",
        },
      },
      { status: 500 }
    );
  }
}
