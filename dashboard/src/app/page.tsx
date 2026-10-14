import React from "react";
import Link from "next/link";
import {
  Smartphone,
  Sparkles,
  Zap,
  ShieldCheck,
  Store,
  ArrowRight,
  CheckCircle2,
  ChevronRight,
  Palette,
  BellRing,
} from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";

export const metadata = {
  title: "WebIntoApp — Convert WooCommerce & Shopify into White-Label Native Apps",
  description:
    "Turn your e-commerce store into a high-converting iOS and Android app in under 10 minutes. Live catalog sync, push notifications, and automated app builds.",
};

export default function HomePage() {
  return (
    <div className="min-h-screen bg-background text-foreground flex flex-col selection:bg-primary/20 selection:text-primary">
      {/* Navigation */}
      <nav className="border-b border-border/40 backdrop-blur-xl bg-background/80 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="h-9 w-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-sm shadow-primary/20">
              <Sparkles className="h-5 w-5" />
            </div>
            <span className="font-extrabold text-lg tracking-tight bg-gradient-to-r from-foreground via-foreground/90 to-muted-foreground bg-clip-text text-transparent">
              WebIntoApp
            </span>
          </div>

          <div className="hidden md:flex items-center space-x-8 text-sm font-medium text-muted-foreground">
            <a href="#features" className="hover:text-foreground transition-colors">
              Features
            </a>
            <a href="#how-it-works" className="hover:text-foreground transition-colors">
              How It Works
            </a>
            <a href="#themes" className="hover:text-foreground transition-colors">
              Themes
            </a>
            <a href="#pricing" className="hover:text-foreground transition-colors">
              Pricing
            </a>
          </div>

          <div className="flex items-center space-x-3">
            <Link href="/login">
              <Button variant="ghost" size="sm" id="nav-login-btn">
                Sign In
              </Button>
            </Link>
            <Link href="/signup">
              <Button size="sm" id="nav-get-started-btn">
                Start Free Trial <ArrowRight className="h-3.5 w-3.5 ml-1.5" />
              </Button>
            </Link>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative pt-20 pb-24 md:pt-28 md:pb-36 overflow-hidden">
        {/* Ambient Gradient Glows */}
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-primary/20 blur-[130px] rounded-full pointer-events-none -z-10" />
        <div className="absolute top-1/3 left-1/4 w-[300px] h-[300px] bg-purple-500/15 blur-[110px] rounded-full pointer-events-none -z-10" />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
          <div className="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full border border-primary/25 bg-primary/5 text-xs font-semibold text-primary backdrop-blur-md shadow-sm">
            <Sparkles className="h-3.5 w-3.5 text-primary animate-pulse" />
            <span>Next-Gen White-Label Mobile App Builder</span>
            <ChevronRight className="h-3 w-3 text-primary/70" />
          </div>

          <h1 className="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight max-w-4xl mx-auto leading-[1.1]">
            Turn Your E-Commerce Store Into a{" "}
            <span className="bg-gradient-to-r from-primary via-purple-400 to-indigo-300 bg-clip-text text-transparent">
              Native Mobile App
            </span>
          </h1>

          <p className="text-base sm:text-xl text-muted-foreground max-w-2xl mx-auto leading-relaxed">
            Connect WooCommerce in 2 minutes. Live bidirectional sync, push notifications,
            instant checkout, and automated white-label Android APK & iOS build compilation.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
            <Link href="/signup">
              <Button size="lg" className="h-12 px-8 text-base shadow-lg shadow-primary/25" id="hero-cta-btn">
                Build Your App Now <ArrowRight className="h-4 w-4 ml-2" />
              </Button>
            </Link>
            <Link href="/dashboard">
              <Button variant="outline" size="lg" className="h-12 px-8 text-base" id="hero-dashboard-btn">
                Open Client Dashboard
              </Button>
            </Link>
          </div>

          <div className="pt-6 flex flex-wrap items-center justify-center gap-6 text-xs text-muted-foreground font-medium">
            <div className="flex items-center space-x-1.5">
              <CheckCircle2 className="h-4 w-4 text-emerald-500" />
              <span>Zero Coding Required</span>
            </div>
            <div className="flex items-center space-x-1.5">
              <CheckCircle2 className="h-4 w-4 text-emerald-500" />
              <span>Real-Time Catalog Sync</span>
            </div>
            <div className="flex items-center space-x-1.5">
              <CheckCircle2 className="h-4 w-4 text-emerald-500" />
              <span>httpOnly Secure BFF Auth</span>
            </div>
            <div className="flex items-center space-x-1.5">
              <CheckCircle2 className="h-4 w-4 text-emerald-500" />
              <span>Google Play Ready (APK/AAB)</span>
            </div>
          </div>
        </div>
      </section>

      {/* Feature Grid */}
      <section id="features" className="py-20 bg-secondary/20 border-y border-border/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div className="text-center space-y-3 max-w-2xl mx-auto">
            <Badge variant="outline" className="text-primary border-primary/30">
              Complete Feature Suite
            </Badge>
            <h2 className="text-3xl font-extrabold tracking-tight sm:text-4xl">
              Engineered for Explosive Mobile Sales
            </h2>
            <p className="text-sm text-muted-foreground">
              Everything your customer needs for a frictionless 60-second mobile shopping experience.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="rounded-2xl border border-border/60 bg-card/70 p-6 space-y-3 shadow-md hover:border-primary/40 transition-all">
              <div className="h-10 w-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                <Store className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-lg">Instant WooCommerce Connect</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Plug your store with REST API keys. Automated DNS validation, SSRF URL guard, and real-time product verification.
              </p>
            </div>

            <div className="rounded-2xl border border-border/60 bg-card/70 p-6 space-y-3 shadow-md hover:border-primary/40 transition-all">
              <div className="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                <Palette className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-lg">Curated Theme Engine</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Choose between Modern Minimalist, Vibrant Lifestyle, and Dark Mode Luxury. Real-time phone simulator with 400ms debounce.
              </p>
            </div>

            <div className="rounded-2xl border border-border/60 bg-card/70 p-6 space-y-3 shadow-md hover:border-primary/40 transition-all">
              <div className="h-10 w-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                <BellRing className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-lg">Automated Push Messaging</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Integrated Firebase push notification pipeline. Send abandoned cart recovery alerts with up to 4x higher CTR than emails.
              </p>
            </div>

            <div className="rounded-2xl border border-border/60 bg-card/70 p-6 space-y-3 shadow-md hover:border-primary/40 transition-all">
              <div className="h-10 w-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                <Zap className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-lg">App Readiness Diagnostics</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Automated 0–100 health scoring examining catalog depth, SSL hygiene, image CDNs, and REST API response times.
              </p>
            </div>

            <div className="rounded-2xl border border-border/60 bg-card/70 p-6 space-y-3 shadow-md hover:border-primary/40 transition-all">
              <div className="h-10 w-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                <ShieldCheck className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-lg">Bank-Grade Isolation</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Strict multi-tenant partitioning, AES-256 encrypted credential vaults, and httpOnly cookie BFF session protection.
              </p>
            </div>

            <div className="rounded-2xl border border-border/60 bg-card/70 p-6 space-y-3 shadow-md hover:border-primary/40 transition-all">
              <div className="h-10 w-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                <Smartphone className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-lg">Native Flutter Compilation</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Compiles 60fps Flutter apps with smooth page transitions, native haptic feedback, and offline caching.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-border/60 py-10 mt-auto bg-card/40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-muted-foreground">
          <div className="flex items-center space-x-2">
            <span className="font-bold text-foreground">WebIntoApp</span>
            <span>— Production-grade white-label mobile app platform.</span>
          </div>
          <div className="flex items-center space-x-6">
            <Link href="/privacy" className="hover:text-foreground">
              Privacy Policy
            </Link>
            <Link href="/terms" className="hover:text-foreground">
              Terms of Service
            </Link>
            <Link href="/contact" className="hover:text-foreground">
              Support
            </Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
