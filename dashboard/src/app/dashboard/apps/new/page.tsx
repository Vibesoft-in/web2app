"use client";

import React, { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from "@/components/ui/Card";
import { apiFetch, ClientApiError } from "@/lib/api-client";
import { ArrowLeft, CheckCircle2, ShoppingBag, Store } from "lucide-react";

const newAppSchema = z.object({
  name: z.string().min(2, "App name must be at least 2 characters").max(50, "App name must be at most 50 characters"),
  platform: z.enum(["woo", "shopify"]),
  package_name: z
    .string()
    .min(5, "Package name must be valid reverse domain format")
    .regex(/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/, "Package name must follow format (e.g. com.mybrand.app)"),
});

type NewAppFormData = z.infer<typeof newAppSchema>;

export default function NewAppPage() {
  const router = useRouter();
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors, isSubmitting },
  } = useForm<NewAppFormData>({
    resolver: zodResolver(newAppSchema),
    defaultValues: {
      name: "",
      platform: "woo",
      package_name: "com.mystore.app",
    },
  });

  const selectedPlatform = watch("platform");

  const onSubmit = async (values: NewAppFormData) => {
    setServerError(null);
    try {
      const res = await apiFetch<{ data: { ulid: string; setup_step: string } }>("/api/backend/apps", {
        method: "POST",
        body: JSON.stringify(values),
      });

      const ulid = res.data.ulid;
      // Advance to connect step
      router.push(`/dashboard/apps/${ulid}/setup/connect`);
    } catch (err: any) {
      if (err instanceof ClientApiError) {
        setServerError(err.message);
      } else {
        setServerError(err?.message || "Failed to initialize app.");
      }
    }
  };

  return (
    <div className="min-h-screen bg-background p-4 sm:p-8 flex items-center justify-center">
      <div className="w-full max-w-xl space-y-6">
        <Link
          href="/dashboard"
          className="inline-flex items-center text-xs font-semibold text-muted-foreground hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-4 w-4 mr-1.5" />
          Back to Apps
        </Link>

        <Card className="border-border/80 shadow-2xl backdrop-blur-md">
          <CardHeader>
            <CardTitle className="text-xl">Initialize New App</CardTitle>
            <CardDescription>Step 1: Choose your eCommerce store platform and mobile package name</CardDescription>
          </CardHeader>

          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-6">
              {serverError && (
                <div className="rounded-lg bg-destructive/15 border border-destructive/30 p-3 text-sm text-destructive font-medium">
                  {serverError}
                </div>
              )}

              {/* Platform selector cards */}
              <div className="space-y-2">
                <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                  Select Store Platform
                </label>
                <div className="grid grid-cols-2 gap-4">
                  <div
                    onClick={() => setValue("platform", "woo")}
                    className={`cursor-pointer rounded-xl border p-4 transition-all relative flex flex-col items-center text-center space-y-2 ${
                      selectedPlatform === "woo"
                        ? "border-primary bg-primary/10 shadow-md ring-2 ring-primary/20"
                        : "border-border hover:border-border/80 bg-card/50"
                    }`}
                  >
                    <div className="h-10 w-10 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                      <Store className="h-5 w-5" />
                    </div>
                    <div>
                      <p className="font-semibold text-sm">WooCommerce</p>
                      <p className="text-[11px] text-muted-foreground">WordPress REST API</p>
                    </div>
                    {selectedPlatform === "woo" && (
                      <CheckCircle2 className="h-4 w-4 text-primary absolute top-2 right-2" />
                    )}
                  </div>

                  <div
                    onClick={() => setValue("platform", "shopify")}
                    className={`cursor-pointer rounded-xl border p-4 transition-all relative flex flex-col items-center text-center space-y-2 ${
                      selectedPlatform === "shopify"
                        ? "border-primary bg-primary/10 shadow-md ring-2 ring-primary/20"
                        : "border-border hover:border-border/80 bg-card/50"
                    }`}
                  >
                    <div className="h-10 w-10 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                      <ShoppingBag className="h-5 w-5" />
                    </div>
                    <div>
                      <p className="font-semibold text-sm">Shopify</p>
                      <p className="text-[11px] text-muted-foreground">Storefront + Admin API</p>
                    </div>
                    {selectedPlatform === "shopify" && (
                      <CheckCircle2 className="h-4 w-4 text-primary absolute top-2 right-2" />
                    )}
                  </div>
                </div>
              </div>

              <Input
                label="App Title / Brand Name"
                placeholder="My Store Mobile App"
                error={errors.name?.message}
                helperText="Shown in app drawer and push notifications."
                {...register("name")}
              />

              <Input
                label="Android Package Name (Reverse Domain)"
                placeholder="com.mybrand.app"
                error={errors.package_name?.message}
                helperText="Unique identifier for Google Play Store (e.g. com.example.shop)."
                {...register("package_name")}
              />
            </CardContent>

            <CardFooter className="flex justify-end space-x-3 border-t border-border/50 pt-4">
              <Link href="/dashboard">
                <Button type="button" variant="outline">
                  Cancel
                </Button>
              </Link>
              <Button type="submit" loading={isSubmitting}>
                Continue to Store Connection
              </Button>
            </CardFooter>
          </form>
        </Card>
      </div>
    </div>
  );
}
