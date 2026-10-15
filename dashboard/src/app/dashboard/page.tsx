"use client";

import React, { useState, useMemo } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api-client";
import { useAuth } from "@/components/providers/AuthProvider";
import {
  Home,
  Smartphone,
  Download,
  Settings,
  Search,
  Bell,
  LogOut,
  Zap,
  Plus,
  BookOpen,
  Check,
  Clock,
  ArrowRight,
  MoreVertical,
  ShoppingBag,
  Shirt,
  ShoppingCart,
  Shield,
  FileCode,
  X,
} from "lucide-react";

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

// Crisp inline SVGs for Android and Apple logos matching the screenshot badges
function AndroidIcon({ className = "h-3.5 w-3.5" }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
      <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9992.4482.9992.9993s-.4482.9997-.9992.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 0 0-.1521-.5676.416.416 0 0 0-.5676.1521l-2.0223 3.503C15.5802 8.4116 13.844 8.0003 12 8.0003c-1.8445 0-3.5807.4113-5.1373.9494L4.8404 5.4467a.4161.4161 0 0 0-.5677-.1521.4157.4157 0 0 0-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396" />
    </svg>
  );
}

function AppleIcon({ className = "h-3.5 w-3.5" }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
      <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.63-.78 1.06-1.86.94-2.95-1 .04-2.19.67-2.88 1.48-.61.71-1.14 1.83-1 2.91 1.12.09 2.31-.66 2.94-1.44z" />
    </svg>
  );
}

export default function DashboardPage() {
  const { user, logout } = useAuth();

  // Navigation tab state: "dashboard" | "apps" | "builds" | "settings"
  const [activeTab, setActiveTab] = useState<"dashboard" | "apps" | "builds" | "settings">("dashboard");

  // Interactive UI states
  const [searchQuery, setSearchQuery] = useState("");
  const [showSearchModal, setShowSearchModal] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [activeMenuAppId, setActiveMenuAppId] = useState<string | null>(null);

  // Fallback demo user if unauthenticated for quick preview
  const currentUser = useMemo(() => {
    return (
      user || {
        id: 1,
        name: "qwert",
        email: "qwert@webintoapp.com",
        role: "developer",
        tenant_id: 1,
      }
    );
  }, [user]);

  const { data: appsResponse } = useQuery({
    queryKey: ["apps-list"],
    queryFn: () => apiFetch<{ data: AppItem[] }>("/api/backend/apps"),
    enabled: !!user,
  });

  const apiApps = useMemo(() => appsResponse?.data || [], [appsResponse]);

  // Default reference showcase apps matching the user's provided design
  const showcaseApps = useMemo(() => {
    return [
      {
        id: "demo-1",
        ulid: apiApps[0]?.ulid || "01HQWRTSTORE9999",
        name: apiApps[0]?.name || "QWRT Store",
        package_name: apiApps[0]?.package_name || "com.qwrt.store",
        iconType: "bag" as const,
        iconBg: "bg-purple-600/25 border-purple-500/30 text-purple-300",
        platforms: ["android", "ios"] as ("android" | "ios")[],
        status: "Ready" as const,
        updated: "2 hours ago",
        setup_step: apiApps[0]?.setup_step || "theme",
      },
      {
        id: "demo-2",
        ulid: apiApps[1]?.ulid || "01HFASHIONHUB999",
        name: apiApps[1]?.name || "Fashion Hub",
        package_name: apiApps[1]?.package_name || "com.fashion.hub",
        iconType: "shirt" as const,
        iconBg: "bg-pink-600/25 border-pink-500/30 text-pink-300",
        platforms: ["android", "ios"] as ("android" | "ios")[],
        status: "Building" as const,
        updated: "5 hours ago",
        setup_step: apiApps[1]?.setup_step || "build",
      },
      {
        id: "demo-3",
        ulid: "01HDEMOSHOP00000",
        name: "Demo Shop",
        package_name: "com.demo.shop",
        iconType: "cart" as const,
        iconBg: "bg-blue-600/25 border-blue-500/30 text-blue-300",
        platforms: ["android"] as ("android" | "ios")[],
        status: "Ready" as const,
        updated: "1 day ago",
        setup_step: "connect",
      },
    ];
  }, [apiApps]);

  // Combined app items for tables and searches
  const displayApps = useMemo(() => {
    if (apiApps.length >= 3) {
      return apiApps.map((a, idx) => ({
        id: String(a.id),
        ulid: a.ulid,
        name: a.name,
        package_name: a.package_name,
        iconType: (idx % 3 === 0 ? "bag" : idx % 3 === 1 ? "shirt" : "cart") as "bag" | "shirt" | "cart",
        iconBg:
          idx % 3 === 0
            ? "bg-purple-600/25 border-purple-500/30 text-purple-300"
            : idx % 3 === 1
            ? "bg-pink-600/25 border-pink-500/30 text-pink-300"
            : "bg-blue-600/25 border-blue-500/30 text-blue-300",
        platforms: ["android", "ios"] as ("android" | "ios")[],
        status: a.status === "building" ? "Building" : "Ready",
        updated: "Just now",
        setup_step: a.setup_step,
      }));
    }
    return showcaseApps;
  }, [apiApps, showcaseApps]);

  const filteredApps = useMemo(() => {
    if (!searchQuery.trim()) return displayApps;
    const q = searchQuery.toLowerCase();
    return displayApps.filter(
      (app) => app.name.toLowerCase().includes(q) || app.package_name.toLowerCase().includes(q)
    );
  }, [displayApps, searchQuery]);

  // Mock builds list for the Builds & Downloads tab
  const buildsList = [
    {
      id: "b-12",
      appName: "QWRT Store",
      version: "v1.2.0 (Build 12)",
      platform: "Android APK & AAB",
      status: "Successful",
      date: "2 hours ago",
      downloadUrl: "#",
      size: "24.8 MB",
    },
    {
      id: "b-11",
      appName: "Fashion Hub",
      version: "v1.1.4 (Build 11)",
      platform: "Android APK",
      status: "Building",
      date: "5 hours ago",
      downloadUrl: null,
      size: "In progress...",
    },
    {
      id: "b-10",
      appName: "Demo Shop",
      version: "v1.0.0 (Build 10)",
      platform: "Android APK",
      status: "Successful",
      date: "1 day ago",
      downloadUrl: "#",
      size: "22.1 MB",
    },
    {
      id: "b-09",
      appName: "QWRT Store",
      version: "v1.1.0 (Build 9)",
      platform: "Android APK",
      status: "Successful",
      date: "3 days ago",
      downloadUrl: "#",
      size: "24.2 MB",
    },
  ];

  const userInitial = (currentUser?.name || "Q").charAt(0).toUpperCase();

  return (
    <div className="min-h-screen bg-[#090B12] text-[#F8FAFC] flex flex-col font-sans selection:bg-[#635BFF]/30 selection:text-white">
      {/* ============================================================== */}
      {/* TOP HEADER BAR (Approx 76px)                                  */}
      {/* ============================================================== */}
      <header className="h-[76px] border-b border-[#252C3A] bg-[#090B12] sticky top-0 z-40 px-6 sm:px-8 flex items-center justify-between">
        {/* Left: Brand Logo */}
        <div className="flex items-center space-x-3">
          <Link href="/dashboard" className="flex items-center space-x-2 group">
            <span className="text-2xl font-extrabold tracking-tight text-[#F8FAFC] group-hover:opacity-90 transition-opacity">
              Web<span className="text-[#635BFF]">IntoApp</span>
            </span>
          </Link>
        </div>

        {/* Right: Search, Notifications & User Avatar */}
        <div className="flex items-center space-x-3.5">
          {/* Search Button */}
          <button
            onClick={() => setShowSearchModal(true)}
            title="Search apps or builds"
            className="h-10 w-10 rounded-full bg-[#151A27] border border-[#252C3A] flex items-center justify-center text-[#A5B4CF] hover:text-[#F8FAFC] hover:border-[#333C4E] hover:bg-[#1A2234] transition-all shadow-sm"
          >
            <Search className="h-4 w-4" />
          </button>

          {/* Notifications Button */}
          <div className="relative">
            <button
              onClick={() => setShowNotifications(!showNotifications)}
              title="Notifications"
              className="h-10 w-10 rounded-full bg-[#151A27] border border-[#252C3A] flex items-center justify-center text-[#A5B4CF] hover:text-[#F8FAFC] hover:border-[#333C4E] hover:bg-[#1A2234] transition-all relative shadow-sm"
            >
              <Bell className="h-4 w-4" />
              {/* Reference Red Notification Dot */}
              <span className="absolute top-2.5 right-2.5 h-2 w-2 rounded-full bg-[#EF4444] ring-2 ring-[#0C1019]" />
            </button>

            {/* Notifications Dropdown */}
            {showNotifications && (
              <div className="absolute right-0 mt-2 w-80 rounded-2xl bg-[#111620] border border-[#252C3A] shadow-2xl p-4 z-50 animate-in fade-in zoom-in-95 duration-150">
                <div className="flex items-center justify-between pb-3 border-b border-[#252C3A]">
                  <span className="text-sm font-semibold text-[#F8FAFC]">Notifications</span>
                  <span className="text-[10px] font-medium bg-[#635BFF]/15 border border-[#635BFF]/30 text-[#A5B4CF] px-2 py-0.5 rounded-full">
                    2 New
                  </span>
                </div>
                <div className="space-y-3 pt-3">
                  <div className="p-2.5 rounded-xl bg-[#151A27] border border-[#252C3A] flex items-start gap-3">
                    <div className="h-2 w-2 rounded-full bg-[#22C55E] mt-1.5 shrink-0" />
                    <div>
                      <p className="text-xs font-medium text-[#F8FAFC]">Build #12 ready for download</p>
                      <p className="text-[11px] text-[#A5B4CF] mt-0.5">QWRT Store Android release APK compiled successfully.</p>
                      <span className="text-[10px] text-[#6B7A99] mt-1 block">2 hours ago</span>
                    </div>
                  </div>
                  <div className="p-2.5 rounded-xl bg-[#151A27] border border-[#252C3A] flex items-start gap-3">
                    <div className="h-2 w-2 rounded-full bg-[#3B82F6] mt-1.5 shrink-0 animate-pulse" />
                    <div>
                      <p className="text-xs font-medium text-[#F8FAFC]">Build in progress</p>
                      <p className="text-[11px] text-[#A5B4CF] mt-0.5">Fashion Hub build worker is generating assets.</p>
                      <span className="text-[10px] text-[#6B7A99] mt-1 block">5 hours ago</span>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* User Profile Avatar */}
          <div className="relative">
            <button
              onClick={() => setShowProfileMenu(!showProfileMenu)}
              className="h-10 w-10 rounded-full bg-[#635BFF] text-[#F8FAFC] font-bold text-sm flex items-center justify-center shadow-md shadow-[#635BFF]/30 hover:scale-105 active:scale-95 transition-all"
            >
              {userInitial}
            </button>

            {/* Profile Dropdown */}
            {showProfileMenu && (
              <div className="absolute right-0 mt-2 w-56 rounded-2xl bg-[#111620] border border-[#252C3A] shadow-2xl p-3 z-50 animate-in fade-in zoom-in-95 duration-150">
                <div className="px-3 py-2 border-b border-[#252C3A]">
                  <p className="text-sm font-semibold text-[#F8FAFC]">{currentUser.name}</p>
                  <p className="text-xs text-[#A5B4CF] truncate">{currentUser.email}</p>
                </div>
                <div className="py-2 space-y-1">
                  <button
                    onClick={() => {
                      setActiveTab("settings");
                      setShowProfileMenu(false);
                    }}
                    className="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27] flex items-center gap-2 transition-colors"
                  >
                    <Settings className="h-3.5 w-3.5 text-[#8B5CF6]" />
                    Account Settings
                  </button>
                  <button
                    onClick={() => {
                      setActiveTab("builds");
                      setShowProfileMenu(false);
                    }}
                    className="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27] flex items-center gap-2 transition-colors"
                  >
                    <Download className="h-3.5 w-3.5 text-[#8B5CF6]" />
                    Builds & Downloads
                  </button>
                </div>
                <div className="pt-2 border-t border-[#252C3A]">
                  <button
                    onClick={() => logout()}
                    className="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 flex items-center gap-2 transition-colors"
                  >
                    <LogOut className="h-3.5 w-3.5" />
                    Sign out
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* ============================================================== */}
      {/* BODY CONTAINER (SIDEBAR + MAIN CONTENT) */}
      {/* ============================================================== */}
      <div className="flex-1 flex overflow-hidden">
        {/* Left Sidebar Navigation (Approx 282px width) */}
        <aside className="w-[282px] border-r border-[#252C3A] bg-[#0C1019] p-5 flex flex-col justify-between shrink-0 hidden md:flex">
          <div className="space-y-2">
            {/* Dashboard Nav Item (Active with Purple Gradient) */}
            <button
              onClick={() => setActiveTab("dashboard")}
              className={`w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl font-semibold text-sm transition-all ${
                activeTab === "dashboard"
                  ? "bg-gradient-to-r from-[#4F46E5] to-[#635BFF] text-white shadow-lg shadow-[#635BFF]/25"
                  : "text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27]"
              }`}
            >
              <Home className="h-5 w-5" />
              <span>Dashboard</span>
            </button>

            {/* My Mobile Apps Nav Item */}
            <button
              onClick={() => setActiveTab("apps")}
              className={`w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl font-medium text-sm transition-all ${
                activeTab === "apps"
                  ? "bg-gradient-to-r from-[#4F46E5] to-[#635BFF] text-white shadow-lg shadow-[#635BFF]/25"
                  : "text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27]"
              }`}
            >
              <Smartphone className="h-5 w-5" />
              <span>My Mobile Apps</span>
            </button>

            {/* Builds & Downloads Nav Item */}
            <button
              onClick={() => setActiveTab("builds")}
              className={`w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl font-medium text-sm transition-all ${
                activeTab === "builds"
                  ? "bg-gradient-to-r from-[#4F46E5] to-[#635BFF] text-white shadow-lg shadow-[#635BFF]/25"
                  : "text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27]"
              }`}
            >
              <Download className="h-5 w-5" />
              <span>Builds & Downloads</span>
            </button>
          </div>

          {/* Bottom Settings Nav Item */}
          <div className="pt-4 border-t border-[#252C3A]">
            <button
              onClick={() => setActiveTab("settings")}
              className={`w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl font-medium text-sm transition-all ${
                activeTab === "settings"
                  ? "bg-gradient-to-r from-[#4F46E5] to-[#635BFF] text-white shadow-lg shadow-[#635BFF]/25"
                  : "text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27]"
              }`}
            >
              <Settings className="h-5 w-5" />
              <span>Settings</span>
            </button>
          </div>
        </aside>

        {/* Main Content Viewport */}
        <main className="flex-1 overflow-y-auto px-6 sm:px-8 py-8 bg-[#090B12]">
          <div className="max-w-7xl mx-auto space-y-6">
            {/* TAB 1: MAIN DASHBOARD VIEW (MATCHING THE SCREENSHOT) */}
            {activeTab === "dashboard" && (
              <>
                {/* Title and Subtitle */}
                <div>
                  <h1 className="text-3xl font-extrabold text-[#F8FAFC] tracking-tight">Dashboard</h1>
                  <p className="text-sm text-[#A5B4CF] mt-1">Manage your apps and builds in one place.</p>
                </div>

                {/* Main 2-Column Grid */}
                <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                  {/* Left Column (Main Stage - approx 65-70%) */}
                  <div className="xl:col-span-8 space-y-6">
                    {/* Welcome Banner Card */}
                    <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#14182E] via-[#161B36] to-[#12162B] border border-[#2D3352] p-6 sm:p-7 shadow-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                      {/* Decorative ambient gradient glows */}
                      <div className="absolute -right-8 -top-8 w-56 h-56 bg-[#635BFF]/15 rounded-full blur-3xl pointer-events-none" />
                      <div className="absolute right-36 -bottom-10 w-44 h-44 bg-[#8B5CF6]/10 rounded-full blur-2xl pointer-events-none" />

                      <div className="flex items-center gap-4 relative z-10">
                        <div className="h-16 w-16 rounded-full bg-[#635BFF] flex items-center justify-center text-[#F8FAFC] text-2xl font-bold shadow-lg shadow-[#635BFF]/30 ring-4 ring-[#635BFF]/20 shrink-0">
                          {userInitial}
                        </div>
                        <div>
                          <h2 className="text-xl sm:text-2xl font-bold text-[#F8FAFC] tracking-tight">
                            Welcome back, {currentUser.name}
                          </h2>
                          <p className="text-xs sm:text-sm text-[#A5B4CF] mt-1">
                            Manage your mobile apps, track builds, and grow your business.
                          </p>
                        </div>
                      </div>

                      {/* Sign Out Button */}
                      <button
                        onClick={() => logout()}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-[#F8FAFC] bg-[#151A27] border border-[#252C3A] hover:bg-[#1C2333] hover:border-[#333C4E] transition-all shadow-sm shrink-0 self-start sm:self-auto relative z-10"
                      >
                        <LogOut className="h-4 w-4 text-[#A5B4CF]" />
                        <span>Sign out</span>
                      </button>
                    </div>

                    {/* 3 Stat Cards Row */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
                      {/* Stat 1: Mobile Apps */}
                      <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-lg flex items-center gap-4 hover:border-[#333C4E] transition-all">
                        <div className="h-12 w-12 rounded-xl bg-[#0E1C38] border border-[#19325D] text-[#3B82F6] flex items-center justify-center shrink-0">
                          <Smartphone className="h-6 w-6" />
                        </div>
                        <div>
                          <p className="text-xs font-medium text-[#A5B4CF]">Mobile Apps</p>
                          <h3 className="text-2xl font-black text-[#F8FAFC] mt-0.5 leading-none">{displayApps.length}</h3>
                          <p className="text-[11px] text-[#6B7A99] mt-1">Total apps created</p>
                        </div>
                      </div>

                      {/* Stat 2: Successful Builds */}
                      <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-lg flex items-center gap-4 hover:border-[#333C4E] transition-all">
                        <div className="h-12 w-12 rounded-xl bg-[#0C261A] border border-[#154730] text-[#22C55E] flex items-center justify-center shrink-0">
                          <Download className="h-6 w-6" />
                        </div>
                        <div>
                          <p className="text-xs font-medium text-[#A5B4CF]">Successful Builds</p>
                          <h3 className="text-2xl font-black text-[#F8FAFC] mt-0.5 leading-none">12</h3>
                          <p className="text-[11px] text-[#6B7A99] mt-1">Completed builds</p>
                        </div>
                      </div>

                      {/* Stat 3: Pending Builds */}
                      <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-lg flex items-center gap-4 hover:border-[#333C4E] transition-all">
                        <div className="h-12 w-12 rounded-xl bg-[#281C0F] border border-[#4D3519] text-[#F59E0B] flex items-center justify-center shrink-0">
                          <Clock className="h-6 w-6" />
                        </div>
                        <div>
                          <p className="text-xs font-medium text-[#A5B4CF]">Pending Builds</p>
                          <h3 className="text-2xl font-black text-[#F8FAFC] mt-0.5 leading-none">1</h3>
                          <p className="text-[11px] text-[#6B7A99] mt-1">Builds in queue</p>
                        </div>
                      </div>
                    </div>

                    {/* Recent Activity Table Card */}
                    <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl">
                      <div className="flex items-center justify-between pb-4 border-b border-[#252C3A]">
                        <div>
                          <h3 className="text-base font-bold text-[#F8FAFC] tracking-wide">Recent Activity</h3>
                          <p className="text-xs text-[#A5B4CF] mt-0.5">Your latest apps and build activity</p>
                        </div>
                        <button
                          onClick={() => setActiveTab("apps")}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#A5B4CF] bg-[#151A27] border border-[#252C3A] hover:text-[#F8FAFC] hover:border-[#333C4E] transition-all"
                        >
                          <span>View All</span>
                          <ArrowRight className="h-3.5 w-3.5" />
                        </button>
                      </div>

                      {/* Activity Table */}
                      <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                          <thead>
                            <tr className="border-b border-[#252C3A] text-[11px] font-semibold text-[#7E8B9B] uppercase tracking-wider">
                              <th className="pb-3 pr-4 font-semibold">App Name</th>
                              <th className="pb-3 px-4 font-semibold">Platform</th>
                              <th className="pb-3 px-4 font-semibold">Status</th>
                              <th className="pb-3 px-4 font-semibold">Updated</th>
                              <th className="pb-3 pl-4 font-semibold text-right">Actions</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-[#1D2433] text-sm">
                            {displayApps.slice(0, 3).map((app, index) => (
                              <tr key={app.id} className="hover:bg-[#151A27]/60 transition-colors group">
                                {/* App Name Column */}
                                <td className="py-3.5 pr-4">
                                  <div className="flex items-center gap-3">
                                    <div
                                      className={`h-10 w-10 rounded-xl flex items-center justify-center shrink-0 shadow-md ${
                                        index === 0
                                          ? "bg-[#635BFF] text-white"
                                          : index === 1
                                          ? "bg-[#EC4899] text-white"
                                          : "bg-[#2563EB] text-white"
                                      }`}
                                    >
                                      {index === 0 && <ShoppingBag className="h-5 w-5" />}
                                      {index === 1 && <Shirt className="h-5 w-5" />}
                                      {index === 2 && <ShoppingCart className="h-5 w-5" />}
                                      {index > 2 && <ShoppingBag className="h-5 w-5" />}
                                    </div>
                                    <div>
                                      <Link
                                        href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}
                                        className="font-bold text-[#F8FAFC] hover:text-[#8B5CF6] transition-colors block text-sm"
                                      >
                                        {app.name}
                                      </Link>
                                      <span className="text-xs font-mono text-[#7E8B9B] block mt-0.5">
                                        {app.package_name}
                                      </span>
                                    </div>
                                  </div>
                                </td>

                                {/* Platform Badges Column */}
                                <td className="py-3.5 px-4 whitespace-nowrap">
                                  <div className="flex items-center gap-2">
                                    {app.platforms.includes("android") && (
                                      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#151A27] border border-[#252C3A] text-xs font-medium text-[#A5B4CF]">
                                        <span className="text-[#22C55E]">
                                          <AndroidIcon className="h-3.5 w-3.5" />
                                        </span>
                                        <span>Android</span>
                                      </span>
                                    )}
                                    {app.platforms.includes("ios") && (
                                      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#151A27] border border-[#252C3A] text-xs font-medium text-[#A5B4CF]">
                                        <AppleIcon className="h-3.5 w-3.5 text-white" />
                                        <span>iOS</span>
                                      </span>
                                    )}
                                  </div>
                                </td>

                                {/* Status Column */}
                                <td className="py-3.5 px-4 whitespace-nowrap">
                                  {app.status === "Ready" ? (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#0C261A] border border-[#164730] text-[#22C55E]">
                                      <span className="h-1.5 w-1.5 rounded-full bg-[#22C55E]" />
                                      Ready
                                    </span>
                                  ) : (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#0E1C38] border border-[#1A3360] text-[#3B82F6]">
                                      <span className="h-1.5 w-1.5 rounded-full bg-[#3B82F6] animate-pulse" />
                                      Building
                                    </span>
                                  )}
                                </td>

                                {/* Updated Column */}
                                <td className="py-3.5 px-4 whitespace-nowrap text-xs text-[#A5B4CF]">{app.updated}</td>

                                {/* Actions Column */}
                                <td className="py-3.5 pl-4 text-right relative">
                                  <button
                                    onClick={() =>
                                      setActiveMenuAppId(activeMenuAppId === app.id ? null : app.id)
                                    }
                                    title="More options"
                                    className="p-1.5 rounded-lg text-[#7E8B9B] hover:text-[#F8FAFC] hover:bg-[#151A27] transition-colors inline-block"
                                  >
                                    <MoreVertical className="h-4 w-4" />
                                  </button>

                                  {/* Three Dots Context Menu */}
                                  {activeMenuAppId === app.id && (
                                    <div className="absolute right-2 top-12 w-48 rounded-xl bg-[#111620] border border-[#252C3A] shadow-xl py-1.5 z-30 text-left animate-in fade-in zoom-in-95 duration-100">
                                      <Link
                                        href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}
                                        className="block px-3 py-2 text-xs text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27]"
                                      >
                                        Open Setup Wizard
                                      </Link>
                                      <button
                                        onClick={() => {
                                          setActiveTab("builds");
                                          setActiveMenuAppId(null);
                                        }}
                                        className="w-full text-left px-3 py-2 text-xs text-[#A5B4CF] hover:text-[#F8FAFC] hover:bg-[#151A27]"
                                      >
                                        View Build Logs
                                      </button>
                                      <Link
                                        href={`/dashboard/apps/${app.ulid}/setup/build`}
                                        className="block px-3 py-2 text-xs text-[#8B5CF6] hover:bg-[#151A27]"
                                      >
                                        Queue New Release
                                      </Link>
                                    </div>
                                  )}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>

                  {/* Right Column (Utility Side Panels - approx 30-35%) */}
                  <div className="xl:col-span-4 space-y-6">
                    {/* Quick Actions Card */}
                    <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl space-y-5">
                      <div className="flex items-start gap-3">
                        <div className="h-10 w-10 rounded-xl bg-[#201838] border border-[#3B2A63] flex items-center justify-center text-[#8B5CF6] shrink-0">
                          <Zap className="h-5 w-5" />
                        </div>
                        <div>
                          <h3 className="text-base font-bold text-[#F8FAFC] tracking-wide">Quick Actions</h3>
                          <p className="text-xs text-[#A5B4CF] mt-0.5">Get started with common tasks.</p>
                        </div>
                      </div>

                      <div className="space-y-3 pt-1">
                        <Link href="/dashboard/apps/new" className="block">
                          <button className="w-full bg-gradient-to-r from-[#635BFF] to-[#7C3AED] hover:from-[#544BEE] hover:to-[#6D2ED8] active:scale-[0.98] text-white font-semibold py-3.5 px-4 rounded-xl shadow-lg shadow-[#635BFF]/30 flex items-center justify-center gap-2 transition-all text-sm">
                            <Plus className="h-4 w-4" />
                            <span>Create New App</span>
                          </button>
                        </Link>

                        <button
                          onClick={() => setActiveTab("builds")}
                          className="w-full bg-[#151A27] hover:bg-[#1A2234] text-[#F8FAFC] border border-[#252C3A] hover:border-[#333C4E] font-semibold py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.98] text-sm shadow-sm"
                        >
                          <Download className="h-4 w-4 text-[#A5B4CF]" />
                          <span>View Builds</span>
                        </button>
                      </div>
                    </div>

                    {/* Getting Started Card */}
                    <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl space-y-5">
                      <div className="flex items-start gap-3">
                        <div className="h-10 w-10 rounded-xl bg-[#201838] border border-[#3B2A63] flex items-center justify-center text-[#8B5CF6] shrink-0">
                          <BookOpen className="h-5 w-5" />
                        </div>
                        <div>
                          <h3 className="text-base font-bold text-[#F8FAFC] tracking-wide">Getting Started</h3>
                          <p className="text-xs text-[#A5B4CF] mt-0.5">2 of 3 tasks complete</p>
                        </div>
                      </div>

                      {/* Progress Bar & Percentage */}
                      <div className="flex items-center gap-3 pt-1">
                        <div className="flex-1 h-2 rounded-full bg-[#151A27] border border-[#252C3A] overflow-hidden">
                          <div className="h-full rounded-full bg-gradient-to-r from-[#635BFF] to-[#8B5CF6] w-[67%]" />
                        </div>
                        <span className="text-xs font-bold text-[#F8FAFC]">67%</span>
                      </div>

                      {/* Tasks Checklist */}
                      <div className="space-y-3.5 pt-2">
                        <div className="flex items-center gap-3 text-[#7E8B9B]">
                          <div className="h-5 w-5 rounded-full bg-[#635BFF] flex items-center justify-center text-white shrink-0 shadow-sm">
                            <Check className="h-3 w-3 stroke-[3]" />
                          </div>
                          <span className="text-xs font-medium line-through">
                            Create your first app
                          </span>
                        </div>

                        <div className="flex items-center gap-3 text-[#7E8B9B]">
                          <div className="h-5 w-5 rounded-full bg-[#635BFF] flex items-center justify-center text-white shrink-0 shadow-sm">
                            <Check className="h-3 w-3 stroke-[3]" />
                          </div>
                          <span className="text-xs font-medium line-through">
                            Build your app
                          </span>
                        </div>

                        <div className="flex items-center gap-3 text-[#F8FAFC]">
                          <div className="h-5 w-5 rounded-full border-2 border-[#3A455A] flex items-center justify-center shrink-0" />
                          <span className="text-xs font-medium">Download and publish</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </>
            )}

            {/* TAB 2: MY MOBILE APPS VIEW */}
            {activeTab === "apps" && (
              <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div>
                    <h1 className="text-3xl font-extrabold text-[#F8FAFC] tracking-tight">My Mobile Apps</h1>
                    <p className="text-sm text-[#A5B4CF] mt-1">
                      Configure, brand, preview, and build your white-label native store apps.
                    </p>
                  </div>
                  <Link href="/dashboard/apps/new">
                    <button className="bg-gradient-to-r from-[#635BFF] to-[#7C3AED] hover:from-[#544BEE] hover:to-[#6D2ED8] text-white font-semibold py-2.5 px-4.5 rounded-xl shadow-lg shadow-[#635BFF]/25 flex items-center gap-2 text-xs transition-all active:scale-95">
                      <Plus className="h-4 w-4" />
                      <span>Create New App</span>
                    </button>
                  </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {displayApps.map((app) => (
                    <div
                      key={app.id}
                      className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 flex flex-col justify-between hover:border-[#635BFF]/40 transition-all hover:shadow-lg hover:shadow-[#635BFF]/10"
                    >
                      <div className="space-y-4">
                        <div className="flex items-start justify-between">
                          <div className="flex items-center gap-3">
                            <div className={`h-11 w-11 rounded-xl border flex items-center justify-center ${app.iconBg}`}>
                              {app.iconType === "bag" && <ShoppingBag className="h-5 w-5" />}
                              {app.iconType === "shirt" && <Shirt className="h-5 w-5" />}
                              {app.iconType === "cart" && <ShoppingCart className="h-5 w-5" />}
                            </div>
                            <div>
                              <h3 className="font-bold text-[#F8FAFC] text-base">{app.name}</h3>
                              <p className="text-xs font-mono text-[#A5B4CF] mt-0.5">{app.package_name}</p>
                            </div>
                          </div>
                        </div>

                        <div className="flex items-center gap-2 pt-2">
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#151A27] border border-[#252C3A] text-xs font-medium text-[#A5B4CF]">
                            <AndroidIcon className="h-3.5 w-3.5 text-[#22C55E]" />
                            <span>Android</span>
                          </span>
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#151A27] border border-[#252C3A] text-xs font-medium text-[#A5B4CF]">
                            <AppleIcon className="h-3.5 w-3.5 text-white" />
                            <span>iOS</span>
                          </span>
                        </div>
                      </div>

                      <div className="pt-5 border-t border-[#252C3A] mt-5 space-y-3">
                        <div className="flex items-center justify-between text-xs text-[#A5B4CF]">
                          <span>Setup Step</span>
                          <span className="font-semibold text-[#F8FAFC] uppercase tracking-wider text-[11px]">
                            {app.setup_step || "Platform"}
                          </span>
                        </div>
                        <Link href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}>
                          <button className="w-full bg-[#151A27] hover:bg-[#1A2234] text-[#F8FAFC] border border-[#252C3A] hover:border-[#333C4E] font-semibold py-2.5 px-4 rounded-xl flex items-center justify-between transition-colors text-xs">
                            <span>Continue Setup</span>
                            <ArrowRight className="h-3.5 w-3.5" />
                          </button>
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* TAB 3: BUILDS & DOWNLOADS VIEW */}
            {activeTab === "builds" && (
              <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div>
                    <h1 className="text-3xl font-extrabold text-[#F8FAFC] tracking-tight">Builds & Downloads</h1>
                    <p className="text-sm text-[#A5B4CF] mt-1">
                      Monitor ongoing compilation jobs and download generated APK / AAB bundles.
                    </p>
                  </div>
                  <button
                    onClick={() => setActiveTab("dashboard")}
                    className="bg-[#151A27] hover:bg-[#1A2234] text-[#A5B4CF] hover:text-[#F8FAFC] font-semibold py-2 px-4 rounded-xl border border-[#252C3A] flex items-center gap-2 text-xs transition-colors"
                  >
                    <ArrowRight className="h-3.5 w-3.5 rotate-180" />
                    <span>Back to Dashboard</span>
                  </button>
                </div>

                <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="border-b border-[#252C3A] text-[11px] font-semibold text-[#7E8B9B] uppercase tracking-wider">
                        <th className="pb-3 pr-4 font-semibold">Build ID & App</th>
                        <th className="pb-3 px-4 font-semibold">Version</th>
                        <th className="pb-3 px-4 font-semibold">Target</th>
                        <th className="pb-3 px-4 font-semibold">Status</th>
                        <th className="pb-3 px-4 font-semibold">Created</th>
                        <th className="pb-3 pl-4 font-semibold text-right">Artifact</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#1D2433] text-sm">
                      {buildsList.map((b) => (
                        <tr key={b.id} className="hover:bg-[#151A27]/60 transition-colors">
                          <td className="py-3.5 pr-4">
                            <div className="font-semibold text-[#F8FAFC]">{b.appName}</div>
                            <span className="text-xs text-[#7E8B9B] font-mono">{b.id}</span>
                          </td>
                          <td className="py-3.5 px-4 text-xs font-mono text-[#A5B4CF]">{b.version}</td>
                          <td className="py-3.5 px-4 text-xs text-[#A5B4CF]">{b.platform}</td>
                          <td className="py-3.5 px-4">
                            {b.status === "Successful" ? (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#0C261A] border border-[#164730] text-[#22C55E]">
                                <span className="h-1.5 w-1.5 rounded-full bg-[#22C55E]" />
                                Successful
                              </span>
                            ) : (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#0E1C38] border border-[#1A3360] text-[#3B82F6]">
                                <span className="h-1.5 w-1.5 rounded-full bg-[#3B82F6] animate-pulse" />
                                In Progress
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-4 text-xs text-[#A5B4CF]">{b.date}</td>
                          <td className="py-3.5 pl-4 text-right">
                            {b.downloadUrl ? (
                              <a
                                href={b.downloadUrl}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-[#635BFF] to-[#7C3AED] hover:from-[#544BEE] hover:to-[#6D2ED8] text-white text-xs font-semibold transition-all shadow-md shadow-[#635BFF]/25"
                              >
                                <Download className="h-3.5 w-3.5" />
                                <span>Download ({b.size})</span>
                              </a>
                            ) : (
                              <span className="text-xs text-[#7E8B9B] italic">Compiling...</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* TAB 4: SETTINGS VIEW */}
            {activeTab === "settings" && (
              <div className="space-y-6">
                <div>
                  <h1 className="text-3xl font-extrabold text-[#F8FAFC] tracking-tight">Account & Tenant Settings</h1>
                  <p className="text-sm text-[#A5B4CF] mt-1">
                    Manage your credentials, API tokens, and subscription tiers.
                  </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl space-y-4">
                    <h3 className="font-bold text-[#F8FAFC] text-base flex items-center gap-2">
                      <Shield className="h-5 w-5 text-[#8B5CF6]" />
                      <span>Tenant Profile</span>
                    </h3>
                    <div className="space-y-3 pt-2">
                      <div>
                        <label className="text-xs font-medium text-[#A5B4CF]">Account Name</label>
                        <p className="text-sm font-semibold text-[#F8FAFC] mt-0.5">{currentUser.name}</p>
                      </div>
                      <div>
                        <label className="text-xs font-medium text-[#A5B4CF]">Email Address</label>
                        <p className="text-sm font-semibold text-[#F8FAFC] mt-0.5">{currentUser.email}</p>
                      </div>
                      <div>
                        <label className="text-xs font-medium text-[#A5B4CF]">Tenant ID</label>
                        <p className="text-xs font-mono text-[#8B5CF6] mt-0.5">tenant_{currentUser.tenant_id || 1}</p>
                      </div>
                    </div>
                  </div>

                  <div className="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl space-y-4">
                    <h3 className="font-bold text-[#F8FAFC] text-base flex items-center gap-2">
                      <FileCode className="h-5 w-5 text-[#8B5CF6]" />
                      <span>Build Runner Keys</span>
                    </h3>
                    <p className="text-xs text-[#A5B4CF]">
                      Automate Flutter app compilation using our secure webhook integration API.
                    </p>
                    <div className="p-3 rounded-xl bg-[#151A27] border border-[#252C3A] flex items-center justify-between">
                      <span className="text-xs font-mono text-[#F8FAFC]">wia_live_key_998492048</span>
                      <button
                        onClick={() => alert("API Key copied to clipboard")}
                        className="text-xs text-[#8B5CF6] hover:text-[#A78BFA] font-semibold"
                      >
                        Copy
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </main>
      </div>

      {/* ============================================================== */}
      {/* QUICK SEARCH MODAL */}
      {/* ============================================================== */}
      {showSearchModal && (
        <div className="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-start justify-center pt-24 px-4 animate-in fade-in duration-150">
          <div className="w-full max-w-xl rounded-2xl bg-[#111620] border border-[#252C3A] shadow-2xl overflow-hidden">
            <div className="p-4 border-b border-[#252C3A] flex items-center gap-3">
              <Search className="h-5 w-5 text-[#A5B4CF]" />
              <input
                type="text"
                autoFocus
                placeholder="Search apps by name or package..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-transparent text-sm text-[#F8FAFC] focus:outline-none placeholder:text-[#6B7A99]"
              />
              <button
                onClick={() => setShowSearchModal(false)}
                className="text-[#A5B4CF] hover:text-[#F8FAFC] p-1 rounded-lg hover:bg-[#151A27]"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            <div className="max-h-72 overflow-y-auto p-2 divide-y divide-[#1D2433]">
              {filteredApps.length === 0 ? (
                <div className="p-6 text-center text-xs text-[#A5B4CF]">No matching apps found.</div>
              ) : (
                filteredApps.map((app) => (
                  <Link
                    key={app.id}
                    href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}
                    onClick={() => setShowSearchModal(false)}
                    className="flex items-center justify-between p-3 rounded-xl hover:bg-[#151A27] transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div className={`h-8 w-8 rounded-lg border flex items-center justify-center ${app.iconBg}`}>
                        {app.iconType === "bag" && <ShoppingBag className="h-4 w-4" />}
                        {app.iconType === "shirt" && <Shirt className="h-4 w-4" />}
                        {app.iconType === "cart" && <ShoppingCart className="h-4 w-4" />}
                      </div>
                      <div>
                        <p className="text-xs font-semibold text-[#F8FAFC]">{app.name}</p>
                        <p className="text-[11px] font-mono text-[#A5B4CF]">{app.package_name}</p>
                      </div>
                    </div>
                    <ArrowRight className="h-3.5 w-3.5 text-[#A5B4CF]" />
                  </Link>
                ))
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
