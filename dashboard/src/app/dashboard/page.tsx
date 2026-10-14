"use client";

import React from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api-client";
import { useAuth } from "@/components/providers/AuthProvider";
import { Button } from "@/components/ui/Button";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Plus, Smartphone, ArrowRight, Store, LogOut, Loader2, Sparkles, AlertCircle } from "lucide-react";

interface AppItem {
  id: number;
  ulid: string;
  name: string;
  platform: "woo" | "shopify";
  package_name: string;
  setup_step: string;
  status: "draft" | "verified" | "configured" | "paid" | "building" | "live";
  created_at: string;
}

export default function DashboardPage() {
  const router = useRouter();
  const { user, isLoading: isAuthLoading, logout } = useAuth();

  const {
    data: appsResponse,
    isLoading: isAppsLoading,
    error,
  } = useQuery({
    queryKey: ["apps-list"],
    queryFn: () => apiFetch<{ data: AppItem[] }>("/api/backend/apps"),
    enabled: !isAuthLoading && !!user,
  });

  if (isAuthLoading || (isAppsLoading && !appsResponse)) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (!user && !isAuthLoading) {
    router.push("/login");
    return null;
  }

  const apps = appsResponse?.data || [];

  return (
    <div className="min-h-screen bg-background">
      {/* Top Navbar */}
      <header className="border-b border-border/60 bg-card/50 backdrop-blur-md sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="h-9 w-9 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
              <Sparkles className="h-5 w-5" />
            </div>
            <span className="font-bold text-lg tracking-tight">WebIntoApp</span>
            <Badge variant="outline" className="hidden sm:inline-flex text-[10px] tracking-wide uppercase">
              Client Portal
            </Badge>
          </div>

          <div className="flex items-center space-x-4">
            <div className="text-right hidden sm:block">
              <p className="text-xs font-semibold text-foreground">{user?.name}</p>
              <p className="text-[11px] text-muted-foreground">{user?.email}</p>
            </div>
            <Button variant="ghost" size="sm" onClick={() => logout()} title="Sign out">
              <LogOut className="h-4 w-4 mr-2" />
              Sign out
            </Button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight">Your Mobile Apps</h1>
            <p className="text-sm text-muted-foreground mt-1">
              Configure, brand, preview, and build your white-label store apps.
            </p>
          </div>

          <Link href="/dashboard/apps/new">
            <Button className="w-full sm:w-auto shadow-lg shadow-primary/20">
              <Plus className="h-4 w-4 mr-2" />
              Create New App
            </Button>
          </Link>
        </div>

        {error && (
          <div className="rounded-xl border border-destructive/20 bg-destructive/10 p-4 flex items-center space-x-3 text-destructive">
            <AlertCircle className="h-5 w-5 flex-shrink-0" />
            <p className="text-sm font-medium">Failed to load apps. Please refresh or verify your connection.</p>
          </div>
        )}

        {/* Empty State */}
        {!isAppsLoading && apps.length === 0 && (
          <Card className="border-dashed border-2 border-border/80 p-12 text-center bg-card/30">
            <div className="mx-auto h-16 w-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary mb-4">
              <Smartphone className="h-8 w-8" />
            </div>
            <CardTitle className="text-xl">No mobile apps yet</CardTitle>
            <CardDescription className="max-w-md mx-auto mt-2 mb-6">
              Convert your existing WooCommerce or Shopify store into an ultra-fast Flutter Android & iOS app.
            </CardDescription>
            <Link href="/dashboard/apps/new">
              <Button size="lg" className="shadow-lg shadow-primary/25">
                <Plus className="h-5 w-5 mr-2" />
                Start Onboarding Wizard
              </Button>
            </Link>
          </Card>
        )}

        {/* Apps Grid */}
        {apps.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {apps.map((app) => (
              <Card
                key={app.ulid}
                className="hover:border-primary/50 transition-all hover:shadow-xl hover:shadow-primary/5 flex flex-col justify-between"
              >
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div className="space-y-1">
                      <CardTitle className="text-lg">{app.name}</CardTitle>
                      <CardDescription className="text-xs font-mono">{app.package_name}</CardDescription>
                    </div>
                    <Badge variant={app.platform === "woo" ? "secondary" : "default"}>
                      <Store className="h-3 w-3 mr-1" />
                      {app.platform === "woo" ? "WooCommerce" : "Shopify"}
                    </Badge>
                  </div>
                </CardHeader>

                <CardContent className="space-y-3">
                  <div className="flex items-center justify-between text-xs text-muted-foreground pt-2 border-t border-border/50">
                    <span>Status</span>
                    <Badge
                      variant={
                        app.status === "live" || app.status === "configured"
                          ? "success"
                          : app.status === "verified"
                          ? "default"
                          : "secondary"
                      }
                    >
                      {app.status}
                    </Badge>
                  </div>

                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Current Wizard Step</span>
                    <span className="font-semibold text-foreground uppercase tracking-wider text-[11px]">
                      {app.setup_step}
                    </span>
                  </div>

                  <div className="pt-2">
                    <Link href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step}`}>
                      <Button variant="outline" className="w-full justify-between group">
                        <span>Continue Setup</span>
                        <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                      </Button>
                    </Link>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
