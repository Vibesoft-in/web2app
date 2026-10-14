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

const signupSchema = z
  .object({
    name: z.string().min(2, "Name must be at least 2 characters"),
    email: z.string().email("Please enter a valid email address"),
    company_name: z.string().min(2, "Company or Store name must be at least 2 characters"),
    password: z
      .string()
      .min(10, "Password must be at least 10 characters")
      .regex(/[A-Za-z]/, "Password must contain letters")
      .regex(/[0-9]/, "Password must contain numbers"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords do not match",
    path: ["password_confirmation"],
  });

type SignupFormData = z.infer<typeof signupSchema>;

export default function SignupPage() {
  const router = useRouter();
  const { refetchUser } = useAuth();
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<SignupFormData>({
    resolver: zodResolver(signupSchema),
    defaultValues: {
      name: "",
      email: "",
      company_name: "",
      password: "",
      password_confirmation: "",
    },
  });

  const onSubmit = async (values: SignupFormData) => {
    setServerError(null);
    try {
      await apiFetch("/api/auth/register", {
        method: "POST",
        body: JSON.stringify(values),
      });

      refetchUser();
      router.push("/dashboard");
    } catch (err: any) {
      if (err instanceof ClientApiError) {
        setServerError(err.message);
      } else {
        setServerError(err?.message || "Failed to create account. Please check your information.");
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
          <h1 className="text-2xl font-bold tracking-tight">Create your account</h1>
          <p className="text-sm text-muted-foreground">Start building your branded mobile app in minutes</p>
        </div>

        <Card className="border-border/60 shadow-2xl backdrop-blur-md">
          <CardHeader className="space-y-1">
            <CardTitle>Register Organization</CardTitle>
            <CardDescription>Enter details to provision your dedicated tenant workspace</CardDescription>
          </CardHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-4">
              {serverError && (
                <div className="rounded-lg bg-destructive/15 border border-destructive/30 p-3 text-sm text-destructive font-medium">
                  {serverError}
                </div>
              )}

              <Input
                label="Full Name"
                placeholder="Jane Doe"
                data-testid="signup-name"
                error={errors.name?.message}
                {...register("name")}
              />

              <Input
                label="Store or Brand Name"
                placeholder="Awesome Apparel Co."
                data-testid="signup-company"
                error={errors.company_name?.message}
                {...register("company_name")}
              />

              <Input
                label="Work Email"
                type="email"
                placeholder="jane@awesomeapparel.com"
                data-testid="signup-email"
                error={errors.email?.message}
                {...register("email")}
              />

              <Input
                label="Password (min 10 characters with numbers & letters)"
                type="password"
                placeholder="••••••••••••"
                data-testid="signup-password"
                error={errors.password?.message}
                {...register("password")}
              />

              <Input
                label="Confirm Password"
                type="password"
                placeholder="••••••••••••"
                data-testid="signup-password-confirmation"
                error={errors.password_confirmation?.message}
                {...register("password_confirmation")}
              />
            </CardContent>

            <CardFooter className="flex flex-col space-y-3">
              <Button type="submit" className="w-full" data-testid="signup-submit" loading={isSubmitting}>
                Create Workspace & Get Started
              </Button>

              <div className="text-center text-xs text-muted-foreground">
                Already registered?{" "}
                <Link href="/login" className="text-primary hover:underline font-semibold">
                  Sign in
                </Link>
              </div>
            </CardFooter>
          </form>
        </Card>

        <div className="flex items-center justify-center space-x-2 text-xs text-muted-foreground">
          <ShieldCheck className="h-4 w-4 text-emerald-500" />
          <span>httpOnly security • Zero password plaintexts</span>
        </div>
      </div>
    </div>
  );
}
