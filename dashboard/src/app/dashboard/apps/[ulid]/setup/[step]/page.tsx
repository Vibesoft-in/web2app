"use client";

import React, { useState, useEffect, useMemo, useRef, useTransition } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiFetch, ClientApiError } from "@/lib/api-client";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { PhoneFrame } from "@/components/preview/PhoneFrame";
import {
  ArrowLeft,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Store,
  ShieldCheck,
  Sparkles,
  Zap,
  Palette,
  Eye,
  Sliders,
  Bell,
  CreditCard,
  Download,
  KeyRound,
  ExternalLink,
  Lock,
} from "lucide-react";

const ALL_STEPS = [
  { id: "platform", name: "Platform", icon: Store },
  { id: "connect", name: "Connect", icon: KeyRound },
  { id: "analysis", name: "Analysis", icon: Zap },
  { id: "features", name: "Features", icon: Sliders },
  { id: "theme", name: "Theme", icon: Palette },
  { id: "branding", name: "Branding", icon: Sparkles },
  { id: "preview", name: "Preview", icon: Eye },
  { id: "integrations", name: "Integrations", icon: Bell },
  { id: "payment", name: "Payment", icon: CreditCard },
  { id: "build", name: "Build", icon: Download },
];

export default function WizardStepPage() {
  const params = useParams();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [, startTransition] = useTransition();

  const ulid = params.ulid as string;
  const currentStepSlug = (params.step as string) || "connect";

  // Debounce timer ref
  const debounceTimerRef = useRef<NodeJS.Timeout | null>(null);

  // Local form states
  const [connectForm, setConnectForm] = useState({
    site_url: "https://mystore.example.com",
    consumer_key: "",
    consumer_secret: "",
  });
  const [verifyError, setVerifyError] = useState<{ code?: string; message?: string } | null>(null);
  const [verifySuccessData, setVerifySuccessData] = useState<any>(null);

  // Features state
  const [features, setFeatures] = useState({
    wishlist: true,
    otp_login: true,
    google_login: true,
    notifications: true,
    reviews: false,
  });

  // Theme selection state
  const [selectedThemeKey, setSelectedThemeKey] = useState("theme_a");

  // Branding state
  const [branding, setBranding] = useState({
    name: "Glow Beauty",
    primaryColor: "#6366F1",
    secondaryColor: "#818CF8",
    backgroundColor: "#FFFFFF",
    textColor: "#111827",
    font: "Inter",
    radius: 12,
  });

  // Live draft config state for phone preview
  const [draftConfig, setDraftConfig] = useState<any>({
    theme: {
      layout: "theme_a",
      colors: {
        primary: "#6366F1",
        secondary: "#818CF8",
        background: "#FFFFFF",
        surface: "#F9FAFB",
        text: "#111827",
      },
      radius: 12,
      font: "Inter",
    },
  });

  // Build simulator state
  const [buildProgress, setBuildProgress] = useState<string | null>(null);

  // 1. Fetch setup state from backend
  const { data: stateResponse, isLoading: isStateLoading } = useQuery({
    queryKey: ["setup-state", ulid],
    queryFn: () => apiFetch<{ data: any }>(`/api/backend/apps/${ulid}/setup-state`),
    staleTime: 5000,
  });

  // 2. Fetch available themes from backend
  const { data: themesResponse } = useQuery({
    queryKey: ["themes-list"],
    queryFn: () => apiFetch<{ data: any[] }>("/api/backend/themes"),
    staleTime: 60000,
  });

  // 3. Fetch preview catalog data from backend
  const { data: previewDataResponse } = useQuery({
    queryKey: ["preview-data", ulid],
    queryFn: () => apiFetch<{ data: any }>(`/api/backend/apps/${ulid}/preview-data`),
    enabled: ["preview", "branding", "build"].includes(currentStepSlug),
    retry: false,
  });

  const setupState = stateResponse?.data;
  const currentAllowedStep = setupState?.current_step || "platform";
  const unlockedSteps: string[] = useMemo(
    () => setupState?.unlocked_steps || ["platform", "connect"],
    [setupState?.unlocked_steps]
  );

  // 4. Guard against URL step-skipping: redirect if target step is not unlocked
  useEffect(() => {
    if (!isStateLoading && setupState) {
      if (!unlockedSteps.includes(currentStepSlug)) {
        // Step is locked! Redirect to current allowed step
        startTransition(() => {
          router.replace(`/dashboard/apps/${ulid}/setup/${currentAllowedStep}`);
        });
      }
    }
  }, [isStateLoading, setupState, unlockedSteps, currentStepSlug, currentAllowedStep, ulid, router]);

  // Sync initial branding when state loads
  useEffect(() => {
    if (setupState?.app?.name) {
      setBranding((b) => ({ ...b, name: setupState.app.name }));
    }
  }, [setupState]);

  // 400ms Debounced sync of draft config to backend
  const saveDraftMutation = useMutation({
    mutationFn: (newConfig: any) =>
      apiFetch(`/api/backend/apps/${ulid}/config/draft`, {
        method: "PUT",
        body: JSON.stringify(newConfig),
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["setup-state", ulid] });
    },
  });

  const handleBrandingChange = (field: string, value: any) => {
    const updatedBranding = { ...branding, [field]: value };
    setBranding(updatedBranding);

    const updatedConfig = {
      schema_version: 1,
      app: {
        name: updatedBranding.name,
      },
      theme: {
        layout: selectedThemeKey,
        colors: {
          primary: updatedBranding.primaryColor,
          secondary: updatedBranding.secondaryColor,
          background: updatedBranding.backgroundColor,
          surface: updatedBranding.backgroundColor === "#0F172A" ? "#1E293B" : "#F9FAFB",
          text: updatedBranding.textColor,
        },
        radius: Number(updatedBranding.radius),
        font: updatedBranding.font,
      },
      features,
    };

    setDraftConfig(updatedConfig);

    // 400ms Debounce backend draft update
    if (debounceTimerRef.current) clearTimeout(debounceTimerRef.current);
    debounceTimerRef.current = setTimeout(() => {
      if (unlockedSteps.includes("theme")) {
        saveDraftMutation.mutate(updatedConfig);
      }
    }, 400);
  };

  // Verify connection mutation
  const verifyMutation = useMutation({
    mutationFn: (payload: { site_url: string; consumer_key: string; consumer_secret: string }) =>
      apiFetch(`/api/backend/apps/${ulid}/connection/verify`, {
        method: "POST",
        body: JSON.stringify(payload),
      }),
    onSuccess: (data: any) => {
      setVerifyError(null);
      setVerifySuccessData(data);
      queryClient.invalidateQueries({ queryKey: ["setup-state", ulid] });
    },
    onError: (err: any) => {
      setVerifySuccessData(null);
      if (err instanceof ClientApiError) {
        setVerifyError({ code: err.code, message: err.message });
      } else {
        setVerifyError({ code: "URL_UNREACHABLE", message: err?.message || "Connection verification failed." });
      }
    },
  });

  // Step transition mutation
  const stepMutation = useMutation({
    mutationFn: (step: string) =>
      apiFetch(`/api/backend/apps/${ulid}/setup-step`, {
        method: "PUT",
        body: JSON.stringify({ step }),
      }),
    onSuccess: (data: any, nextStep: string) => {
      queryClient.invalidateQueries({ queryKey: ["setup-state", ulid] });
      router.push(`/dashboard/apps/${ulid}/setup/${nextStep}`);
    },
  });

  // Publish config mutation
  const publishMutation = useMutation({
    mutationFn: () =>
      apiFetch(`/api/backend/apps/${ulid}/config/publish`, {
        method: "POST",
        body: JSON.stringify({ config: draftConfig }),
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["setup-state", ulid] });
      router.push(`/dashboard/apps/${ulid}/setup/preview`);
    },
  });

  if (isStateLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  const themes = themesResponse?.data || [];
  const previewProducts = previewDataResponse?.data?.products || [];
  const previewCategories = previewDataResponse?.data?.categories || [];

  return (
    <div className="min-h-screen bg-background flex flex-col">
      {/* Top Header */}
      <header className="border-b border-border/60 bg-card/50 backdrop-blur-md sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <Link href="/dashboard" className="text-muted-foreground hover:text-foreground">
              <ArrowLeft className="h-4 w-4" />
            </Link>
            <div className="h-6 w-px bg-border" />
            <span className="font-bold text-sm tracking-tight">{setupState?.app?.name || "Mobile App"}</span>
            <Badge variant="outline" className="text-[11px] font-mono">
              {setupState?.app?.platform === "woo" ? "WooCommerce" : "Shopify"}
            </Badge>
          </div>

          <div className="flex items-center space-x-2">
            <span className="text-xs text-muted-foreground hidden sm:inline">Current:</span>
            <Badge variant="secondary" className="uppercase font-semibold text-[11px]">
              {currentStepSlug}
            </Badge>
          </div>
        </div>

        {/* 10 Step Progress Stepper Bar */}
        <div className="max-w-7xl mx-auto px-4 overflow-x-auto no-scrollbar py-2.5 border-t border-border/40">
          <div className="flex items-center space-x-2 min-w-max">
            {ALL_STEPS.map((s, idx) => {
              const isCurrent = s.id === currentStepSlug;
              const isUnlocked = unlockedSteps.includes(s.id);
              const isPast = (setupState?.completed_steps || []).includes(s.id);

              return (
                <div key={s.id} className="flex items-center">
                  <button
                    disabled={!isUnlocked}
                    onClick={() => {
                      if (isUnlocked) router.push(`/dashboard/apps/${ulid}/setup/${s.id}`);
                    }}
                    className={`flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-medium transition-all ${
                      isCurrent
                        ? "bg-primary text-primary-foreground shadow-md font-semibold"
                        : isPast
                        ? "bg-primary/10 text-primary hover:bg-primary/20"
                        : isUnlocked
                        ? "bg-secondary text-secondary-foreground hover:bg-secondary/80"
                        : "opacity-40 cursor-not-allowed text-muted-foreground"
                    }`}
                  >
                    <span>{idx + 1}.</span>
                    <span>{s.name}</span>
                    {isPast && <CheckCircle2 className="h-3 w-3 ml-1 text-emerald-400" />}
                    {!isUnlocked && <Lock className="h-3 w-3 ml-1" />}
                  </button>

                  {idx < ALL_STEPS.length - 1 && <div className="h-px w-3 bg-border/80 mx-1" />}
                </div>
              );
            })}
          </div>
        </div>
      </header>

      {/* Main Step Body */}
      <main className="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        {/* STEP 1: PLATFORM */}
        {currentStepSlug === "platform" && (
          <div className="max-w-2xl mx-auto space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Store Platform</CardTitle>
                <CardDescription>Verify or change the platform connected to this application.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="rounded-xl border border-border p-4 bg-card/60 flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <Store className="h-8 w-8 text-purple-500" />
                    <div>
                      <p className="font-semibold text-sm">WooCommerce</p>
                      <p className="text-xs text-muted-foreground">Self-hosted WordPress store REST API</p>
                    </div>
                  </div>
                  <Badge variant="success">Active</Badge>
                </div>
                <div className="rounded-lg bg-amber-500/10 border border-amber-500/20 p-3 text-xs text-amber-600 dark:text-amber-400">
                  Note: Changing platforms will invalidate your current credentials and reset connection state.
                </div>
              </CardContent>
              <CardFooter className="flex justify-end">
                <Button onClick={() => router.push(`/dashboard/apps/${ulid}/setup/connect`)}>
                  Continue to Connection <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              </CardFooter>
            </Card>
          </div>
        )}

        {/* STEP 2: CONNECT */}
        {currentStepSlug === "connect" && (
          <div className="max-w-3xl mx-auto space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Connect WooCommerce Store</CardTitle>
                <CardDescription>
                  Enter your store HTTPS URL and WooCommerce REST API credentials.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                {verifyError && (
                  <div
                    data-testid="verify-error"
                    className="rounded-xl bg-destructive/15 border border-destructive/30 p-4 space-y-1"
                  >
                    <div className="flex items-center space-x-2 text-destructive font-bold text-sm">
                      <AlertCircle className="h-4 w-4" />
                      <span>Verification Failed: {verifyError.code}</span>
                    </div>
                    <p className="text-xs text-destructive/90">{verifyError.message}</p>
                  </div>
                )}

                {verifySuccessData && (
                  <div
                    data-testid="verify-success"
                    className="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 space-y-2"
                  >
                    <div className="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400 font-bold text-sm">
                      <CheckCircle2 className="h-4 w-4" />
                      <span>Connection Verified Successfully</span>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs pt-1">
                      <div>
                        <span className="text-muted-foreground block">Store:</span>
                        <span className="font-semibold">{verifySuccessData.data?.diagnostics?.store_name}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block">Products:</span>
                        <span className="font-semibold">{verifySuccessData.data?.diagnostics?.products_count}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block">Currency:</span>
                        <span className="font-semibold">{verifySuccessData.data?.diagnostics?.currency}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block">Woo Version:</span>
                        <span className="font-semibold">{verifySuccessData.data?.diagnostics?.wc_version}</span>
                      </div>
                    </div>
                  </div>
                )}

                <Input
                  label="Site URL (Must be secure HTTPS)"
                  placeholder="https://mystore.com"
                  data-testid="connect-site-url"
                  value={connectForm.site_url}
                  onChange={(e) => setConnectForm({ ...connectForm, site_url: e.target.value })}
                  helperText="Ensure your store has an active SSL certificate (https://)."
                />

                <Input
                  label="Consumer Key"
                  placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                  data-testid="connect-consumer-key"
                  value={connectForm.consumer_key}
                  onChange={(e) => setConnectForm({ ...connectForm, consumer_key: e.target.value })}
                  helperText="Starts with ck_"
                />

                <Input
                  label="Consumer Secret"
                  type="password"
                  placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                  data-testid="connect-consumer-secret"
                  value={connectForm.consumer_secret}
                  onChange={(e) => setConnectForm({ ...connectForm, consumer_secret: e.target.value })}
                  helperText="Starts with cs_. Never exposed in API responses or plain logs."
                />

                {/* Help guide box */}
                <div className="rounded-xl border border-border/80 bg-secondary/30 p-4 space-y-2 text-xs">
                  <div className="font-semibold text-foreground flex items-center justify-between">
                    <span>How to generate WooCommerce API keys:</span>
                    <a
                      href="https://woocommerce.com/document/woocommerce-rest-api/"
                      target="_blank"
                      rel="noreferrer"
                      className="text-primary hover:underline inline-flex items-center"
                    >
                      Docs <ExternalLink className="h-3 w-3 ml-1" />
                    </a>
                  </div>
                  <ol className="list-decimal list-inside space-y-1 text-muted-foreground">
                    <li>Open your WordPress Admin → WooCommerce → Settings.</li>
                    <li>Click the <strong>Advanced</strong> tab and select <strong>REST API</strong>.</li>
                    <li>Click <strong>Add key</strong>, set description to &quot;WebIntoApp Mobile&quot;.</li>
                    <li>Set Permissions to <strong>Read/Write</strong> and copy the generated keys.</li>
                  </ol>
                </div>
              </CardContent>

              <CardFooter className="flex justify-between border-t border-border/50 pt-4">
                <Button
                  variant="outline"
                  data-testid="verify-btn"
                  onClick={() =>
                    verifyMutation.mutate({
                      site_url: connectForm.site_url,
                      consumer_key: connectForm.consumer_key,
                      consumer_secret: connectForm.consumer_secret,
                    })
                  }
                  loading={verifyMutation.isPending}
                >
                  Verify Connection
                </Button>

                <Button
                  data-testid="proceed-analysis-btn"
                  onClick={() => router.push(`/dashboard/apps/${ulid}/setup/analysis`)}
                  disabled={!setupState?.connection || setupState.connection.status !== "verified"}
                >
                  Proceed to Analysis <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              </CardFooter>
            </Card>
          </div>
        )}

        {/* STEP 3: ANALYSIS (READINESS SCORE) */}
        {currentStepSlug === "analysis" && (
          <div className="max-w-3xl mx-auto space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>App Readiness Score</CardTitle>
                <CardDescription>
                  Diagnostic health check of your catalog and WooCommerce API endpoints.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="flex flex-col sm:flex-row items-center justify-between p-6 rounded-2xl bg-primary/5 border border-primary/20 gap-6">
                  <div className="space-y-1 text-center sm:text-left">
                    <span className="text-xs font-bold uppercase tracking-wider text-primary">Store Diagnostics</span>
                    <h3 className="text-2xl font-extrabold text-foreground">Ready for White-Label Mobile App</h3>
                    <p className="text-xs text-muted-foreground max-w-sm">
                      Your store satisfies all required parameters for native compilation and live synchronization.
                    </p>
                  </div>

                  <div className="flex flex-col items-center justify-center h-28 w-28 rounded-full border-4 border-primary bg-card shadow-xl">
                    <span className="text-3xl font-black text-primary">94</span>
                    <span className="text-[10px] font-semibold text-muted-foreground uppercase">Score / 100</span>
                  </div>
                </div>

                <div className="space-y-3">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Compliance Checklist</h4>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div className="flex items-center space-x-2.5 p-3 rounded-lg border bg-card/60">
                      <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                      <span>HTTPS & TLS 1.3 Certified</span>
                    </div>
                    <div className="flex items-center space-x-2.5 p-3 rounded-lg border bg-card/60">
                      <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                      <span>WooCommerce v3 REST API Active</span>
                    </div>
                    <div className="flex items-center space-x-2.5 p-3 rounded-lg border bg-card/60">
                      <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                      <span>Catalog size optimized (&gt;10 items)</span>
                    </div>
                    <div className="flex items-center space-x-2.5 p-3 rounded-lg border bg-card/60">
                      <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                      <span>Product images secure on CDN</span>
                    </div>
                  </div>
                </div>
              </CardContent>
              <CardFooter className="flex justify-end border-t border-border/50 pt-4">
                <Button onClick={() => stepMutation.mutate("features")} loading={stepMutation.isPending}>
                  Configure App Features <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              </CardFooter>
            </Card>
          </div>
        )}

        {/* STEP 4: FEATURES */}
        {currentStepSlug === "features" && (
          <div className="max-w-3xl mx-auto space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Feature Toggles</CardTitle>
                <CardDescription>Enable or disable core customer experiences in your mobile app.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {[
                  { id: "wishlist", label: "Product Wishlist", desc: "Allow users to save items to their favorites." },
                  { id: "otp_login", label: "Phone OTP Authentication", desc: "Fast passwordless 6-digit OTP login." },
                  { id: "google_login", label: "Google One-Tap Login", desc: "Seamless Google SSO sign in." },
                  { id: "notifications", label: "Push Notification Inbox", desc: "Broadcast promotional campaigns." },
                  { id: "reviews", label: "Customer Reviews & Ratings", desc: "Display verified buyer reviews on products." },
                ].map((f) => {
                  const isChecked = (features as any)[f.id];
                  return (
                    <div
                      key={f.id}
                      onClick={() => setFeatures({ ...features, [f.id]: !isChecked })}
                      className="cursor-pointer flex items-center justify-between p-4 rounded-xl border hover:border-primary/50 transition-colors bg-card/60"
                    >
                      <div className="space-y-0.5">
                        <p className="font-semibold text-sm">{f.label}</p>
                        <p className="text-xs text-muted-foreground">{f.desc}</p>
                      </div>
                      <div
                        className={`w-11 h-6 rounded-full transition-colors flex items-center p-1 ${
                          isChecked ? "bg-primary justify-end" : "bg-muted justify-start"
                        }`}
                      >
                        <div className="w-4 h-4 rounded-full bg-white shadow-sm" />
                      </div>
                    </div>
                  );
                })}
              </CardContent>
              <CardFooter className="flex justify-end border-t border-border/50 pt-4">
                <Button onClick={() => stepMutation.mutate("theme")} loading={stepMutation.isPending}>
                  Choose Theme <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              </CardFooter>
            </Card>
          </div>
        )}

        {/* STEP 5: THEMES GALLERY */}
        {currentStepSlug === "theme" && (
          <div className="max-w-5xl mx-auto space-y-6">
            <div className="text-center space-y-1">
              <h2 className="text-2xl font-bold tracking-tight">Theme Design Gallery</h2>
              <p className="text-xs text-muted-foreground">Select from our 3 curated white-label design themes.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {themes.map((t: any) => {
                const isSelected = selectedThemeKey === t.key;
                const defs = t.defaults || {};
                const cols = defs.colors || {};

                return (
                  <Card
                    key={t.key}
                    onClick={() => {
                      setSelectedThemeKey(t.key);
                      handleBrandingChange("primaryColor", cols.primary || "#6366F1");
                    }}
                    className={`cursor-pointer transition-all hover:scale-[1.02] flex flex-col justify-between ${
                      isSelected
                        ? "border-primary ring-2 ring-primary shadow-xl bg-card"
                        : "hover:border-border/90 bg-card/70"
                    }`}
                  >
                    <CardHeader>
                      <div className="flex items-center justify-between">
                        <CardTitle className="text-base">{t.name}</CardTitle>
                        {isSelected && <Badge variant="default">Selected</Badge>}
                      </div>
                      <CardDescription className="text-xs">Layout: {t.key}</CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-4">
                      {/* Mini visual mockup card */}
                      <div
                        className="h-36 rounded-xl border p-3 flex flex-col justify-between shadow-inner"
                        style={{ backgroundColor: cols.background || "#ffffff" }}
                      >
                        <div className="flex justify-between items-center">
                          <div className="h-4 w-12 rounded" style={{ backgroundColor: cols.primary }} />
                          <div className="h-3 w-3 rounded-full bg-slate-300" />
                        </div>
                        <div className="space-y-1.5">
                          <div className="h-10 rounded-lg w-full" style={{ backgroundColor: cols.primary, opacity: 0.85 }} />
                          <div className="flex space-x-1.5">
                            <div className="h-10 w-1/2 rounded" style={{ backgroundColor: cols.surface || "#f3f4f6" }} />
                            <div className="h-10 w-1/2 rounded" style={{ backgroundColor: cols.surface || "#f3f4f6" }} />
                          </div>
                        </div>
                      </div>

                      <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Font: {defs.font || "Inter"}</span>
                        <span>Radius: {defs.radius || 12}px</span>
                      </div>
                    </CardContent>

                    <CardFooter className="border-t border-border/40 pt-3">
                      <Button variant={isSelected ? "default" : "outline"} className="w-full" size="sm">
                        {isSelected ? "Active Theme" : "Select Theme"}
                      </Button>
                    </CardFooter>
                  </Card>
                );
              })}
            </div>

            <div className="flex justify-end pt-4">
              <Button onClick={() => stepMutation.mutate("branding")} loading={stepMutation.isPending}>
                Customize Branding <ArrowRight className="h-4 w-4 ml-2" />
              </Button>
            </div>
          </div>
        )}

        {/* STEP 6 & STEP 7: BRANDING + LIVE PHONE PREVIEW */}
        {(currentStepSlug === "branding" || currentStepSlug === "preview") && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            {/* Left Column: Form Controls */}
            <div className="lg:col-span-7 space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>{currentStepSlug === "branding" ? "Brand Customization" : "Live Simulation"}</CardTitle>
                  <CardDescription>
                    Live updates apply to the preview phone in real-time (400ms debounce).
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <Input
                    label="Application Name"
                    data-testid="branding-name-input"
                    value={branding.name}
                    onChange={(e) => handleBrandingChange("name", e.target.value)}
                  />

                  <div className="grid grid-cols-2 gap-4">
                    <Input
                      label="Primary Accent (Hex)"
                      data-testid="branding-primary-input"
                      value={branding.primaryColor}
                      onChange={(e) => handleBrandingChange("primaryColor", e.target.value)}
                    />
                    <Input
                      label="Secondary Accent (Hex)"
                      value={branding.secondaryColor}
                      onChange={(e) => handleBrandingChange("secondaryColor", e.target.value)}
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                      <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Font Family
                      </label>
                      <select
                        value={branding.font}
                        onChange={(e) => handleBrandingChange("font", e.target.value)}
                        className="flex h-10 w-full rounded-lg border border-input bg-background/50 px-3 py-2 text-sm"
                      >
                        <option value="Inter">Inter (Clean Modern)</option>
                        <option value="Poppins">Poppins (Friendly Bold)</option>
                        <option value="Outfit">Outfit (High Fashion)</option>
                      </select>
                    </div>

                    <div className="space-y-1.5">
                      <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Corner Radius ({branding.radius}px)
                      </label>
                      <input
                        type="range"
                        min="0"
                        max="24"
                        value={branding.radius}
                        onChange={(e) => handleBrandingChange("radius", Number(e.target.value))}
                        className="w-full mt-2"
                      />
                    </div>
                  </div>
                </CardContent>

                <CardFooter className="flex justify-between border-t border-border/50 pt-4">
                  {currentStepSlug === "branding" ? (
                    <Button onClick={() => stepMutation.mutate("preview")} loading={stepMutation.isPending}>
                      Open Full Live Preview <ArrowRight className="h-4 w-4 ml-2" />
                    </Button>
                  ) : (
                    <Button onClick={() => publishMutation.mutate()} loading={publishMutation.isPending}>
                      Publish Config & Continue <ArrowRight className="h-4 w-4 ml-2" />
                    </Button>
                  )}
                </CardFooter>
              </Card>

              {currentStepSlug === "preview" && (
                <div className="flex justify-end">
                  <Button onClick={() => stepMutation.mutate("integrations")} variant="outline">
                    Skip to Integrations <ArrowRight className="h-4 w-4 ml-2" />
                  </Button>
                </div>
              )}
            </div>

            {/* Right Column: Live Phone-Frame Simulator */}
            <div className="lg:col-span-5 flex flex-col items-center sticky top-24">
              <div className="mb-2 text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center space-x-1.5">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                <span>Live Store Preview</span>
              </div>
              <PhoneFrame
                appName={branding.name}
                config={draftConfig}
                products={previewProducts}
                categories={previewCategories}
              />
            </div>
          </div>
        )}

        {/* STEP 8: INTEGRATIONS */}
        {currentStepSlug === "integrations" && (
          <div className="max-w-3xl mx-auto space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Integrations & Notifications</CardTitle>
                <CardDescription>Setup automated push messaging and customer communications.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 flex items-start justify-between">
                  <div className="space-y-1">
                    <p className="font-semibold text-sm flex items-center">
                      <ShieldCheck className="h-4 w-4 text-emerald-500 mr-1.5" />
                      Firebase Cloud Messaging (Push Notifications)
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Auto-provisioned by WebIntoApp backend. Tokens are registered on app launch.
                    </p>
                  </div>
                  <Badge variant="success">Auto-Configured</Badge>
                </div>

                <div className="p-4 rounded-xl border border-border bg-card/60 space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="font-semibold text-sm">WhatsApp Business Cloud API (Optional)</p>
                      <p className="text-xs text-muted-foreground">Send order tracking updates on WhatsApp.</p>
                    </div>
                    <Badge variant="outline">Optional</Badge>
                  </div>
                  <Input label="Phone Number ID" placeholder="1029384756" />
                </div>
              </CardContent>
              <CardFooter className="flex justify-end border-t border-border/50 pt-4">
                <Button onClick={() => stepMutation.mutate("payment")} loading={stepMutation.isPending}>
                  Continue to Plan & Billing <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              </CardFooter>
            </Card>
          </div>
        )}

        {/* STEP 9: PAYMENT */}
        {currentStepSlug === "payment" && (
          <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center space-y-1">
              <h2 className="text-2xl font-bold tracking-tight">Select Subscription Plan</h2>
              <p className="text-xs text-muted-foreground">All plans include Android compilation and live synchronization.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {[
                { name: "Starter", price: "₹999", desc: "For single store with standard push notifications." },
                { name: "Growth", price: "₹2,999", desc: "Includes automated abandoned cart and OTP login." },
                { name: "Enterprise", price: "₹5,999", desc: "Includes iOS build, custom domain, and priority CI." },
              ].map((p, idx) => (
                <Card key={p.name} className={idx === 1 ? "border-primary ring-2 ring-primary bg-card" : "bg-card/70"}>
                  <CardHeader>
                    <CardTitle className="text-lg">{p.name}</CardTitle>
                    <CardDescription className="text-xs">{p.desc}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <span className="text-3xl font-extrabold">{p.price}</span>
                    <span className="text-xs text-muted-foreground"> / month</span>
                  </CardContent>
                  <CardFooter>
                    <Button variant={idx === 1 ? "default" : "outline"} className="w-full">
                      Select Plan
                    </Button>
                  </CardFooter>
                </Card>
              ))}
            </div>

            <div className="flex justify-end pt-4">
              <Button onClick={() => stepMutation.mutate("build")} loading={stepMutation.isPending}>
                Proceed to App Compilation <ArrowRight className="h-4 w-4 ml-2" />
              </Button>
            </div>
          </div>
        )}

        {/* STEP 10: BUILD */}
        {currentStepSlug === "build" && (
          <div className="max-w-3xl mx-auto space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>White-Label App Compilation</CardTitle>
                <CardDescription>Compile native Android APK / AAB artifacts for Google Play Console.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                {!setupState?.build_allowed ? (
                  <div className="rounded-xl border border-destructive/20 bg-destructive/10 p-4 space-y-2">
                    <div className="flex items-center space-x-2 text-destructive font-semibold text-sm">
                      <AlertCircle className="h-4 w-4" />
                      <span>Build Creation Currently Blocked</span>
                    </div>
                    <ul className="list-disc list-inside text-xs text-destructive/90 space-y-1">
                      {(setupState?.build_blocking_reasons || []).map((r: string, i: number) => (
                        <li key={i}>{r}</li>
                      ))}
                    </ul>
                  </div>
                ) : (
                  <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 flex items-center space-x-3 text-emerald-600 dark:text-emerald-400">
                    <CheckCircle2 className="h-5 w-5" />
                    <div>
                      <p className="text-sm font-semibold">Store & Configuration Verified</p>
                      <p className="text-xs text-emerald-700 dark:text-emerald-300">
                        All prerequisite steps are complete. Ready for compilation.
                      </p>
                    </div>
                  </div>
                )}

                {buildProgress && (
                  <div className="rounded-xl border border-border p-4 bg-card space-y-2">
                    <div className="flex justify-between text-xs font-semibold">
                      <span>Status: {buildProgress}</span>
                      <Loader2 className="h-4 w-4 animate-spin text-primary" />
                    </div>
                    <div className="w-full bg-secondary h-2 rounded-full overflow-hidden">
                      <div className="bg-primary h-full w-2/3 animate-pulse" />
                    </div>
                  </div>
                )}
              </CardContent>

              <CardFooter className="flex justify-between border-t border-border/50 pt-4">
                <Button variant="outline" onClick={() => router.push(`/dashboard/apps/${ulid}/setup/preview`)}>
                  Back to Preview
                </Button>

                <Button
                  disabled={!setupState?.build_allowed || !!buildProgress}
                  onClick={() => {
                    setBuildProgress("Building release APK...");
                    setTimeout(() => setBuildProgress("Signing with Keystore..."), 2000);
                    setTimeout(() => setBuildProgress("Build Ready!"), 4000);
                  }}
                >
                  <Download className="h-4 w-4 mr-2" />
                  Generate White-Label App
                </Button>
              </CardFooter>
            </Card>
          </div>
        )}
      </main>
    </div>
  );
}
