"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from "@/components/ui/Card";
import { apiFetch, ClientApiError } from "@/lib/api-client";
import { useAuth } from "@/components/providers/AuthProvider";
import { Sparkles, ShieldCheck } from "lucide-react";

const loginSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
  password: z.string().min(1, "Password is required"),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const router = useRouter();
  const { refetchUser } = useAuth();
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const onSubmit = async (values: LoginFormData) => {
    setServerError(null);
    try {
      await apiFetch("/api/auth/login", {
        method: "POST",
        body: JSON.stringify(values),
      });

      refetchUser();
      router.push("/dashboard");
    } catch (err: any) {
      if (err instanceof ClientApiError) {
        setServerError(err.message);
      } else {
        setServerError(err?.message || "Failed to sign in. Please verify your credentials.");
      }
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center p-4 bg-gradient-to-br from-background via-background/95 to-slate-900/60">
      <div className="w-full max-w-md space-y-6">
        <div className="text-center space-y-2">
          <div className="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-primary/10 text-primary border border-primary/20 mb-2">
            <Sparkles className="h-6 w-6" />
          </div>
          <h1 className="text-2xl font-bold tracking-tight">WebIntoApp</h1>
          <p className="text-sm text-muted-foreground">Sign in to your white-label app builder dashboard</p>
        </div>

        <Card className="border-border/60 shadow-2xl backdrop-blur-md">
          <CardHeader className="space-y-1">
            <CardTitle>Welcome back</CardTitle>
            <CardDescription>Enter your credentials to access your apps</CardDescription>
          </CardHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-4">
              {serverError && (
                <div className="rounded-lg bg-destructive/15 border border-destructive/30 p-3 text-sm text-destructive font-medium">
                  {serverError}
                </div>
              )}

              <Input
                label="Email Address"
                type="email"
                placeholder="owner@yourstore.com"
                data-testid="login-email"
                error={errors.email?.message}
                {...register("email")}
              />

              <Input
                label="Password"
                type="password"
                placeholder="••••••••••••"
                data-testid="login-password"
                error={errors.password?.message}
                {...register("password")}
              />
            </CardContent>

            <CardFooter className="flex flex-col space-y-3">
              <Button type="submit" className="w-full" data-testid="login-submit" loading={isSubmitting}>
                Sign in to Dashboard
              </Button>

              <div className="text-center text-xs text-muted-foreground">
                Don&apos;t have an account yet?{" "}
                <Link href="/signup" className="text-primary hover:underline font-semibold">
                  Create account
                </Link>
              </div>
            </CardFooter>
          </form>
        </Card>

        <div className="flex items-center justify-center space-x-2 text-xs text-muted-foreground">
          <ShieldCheck className="h-4 w-4 text-emerald-500" />
          <span>httpOnly secure authentication • Bank-grade tenant isolation</span>
        </div>
      </div>
    </div>
  );
}
