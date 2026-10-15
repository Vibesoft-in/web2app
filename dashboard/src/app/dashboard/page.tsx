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
    <div className="min-h-screen bg-[#080c14] text-slate-100 flex flex-col font-sans selection:bg-[#4f46e5]/30 selection:text-white">
      {/* ============================================================== */}
      {/* TOP HEADER BAR */}
      {/* ============================================================== */}
      <header className="h-16 border-b border-slate-800/80 bg-[#090d16]/95 backdrop-blur-md sticky top-0 z-40 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        {/* Left: Brand Logo */}
        <div className="flex items-center space-x-3">
          <Link href="/dashboard" className="flex items-center space-x-2 group">
            <span className="text-xl font-extrabold tracking-tight text-white group-hover:opacity-90 transition-opacity">
              Web<span className="text-[#6366f1]">IntoApp</span>
            </span>
          </Link>
        </div>

        {/* Right: Search, Notifications & User Avatar */}
        <div className="flex items-center space-x-3">
          {/* Search Button */}
          <button
            onClick={() => setShowSearchModal(true)}
            title="Search apps or builds"
            className="h-10 w-10 rounded-full bg-[#131929] border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
          >
            <Search className="h-4 w-4" />
          </button>

          {/* Notifications Button */}
          <div className="relative">
            <button
              onClick={() => setShowNotifications(!showNotifications)}
              title="Notifications"
              className="h-10 w-10 rounded-full bg-[#131929] border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-800 transition-colors relative"
            >
              <Bell className="h-4 w-4" />
              {/* Red Notification Dot */}
              <span className="absolute top-2.5 right-2.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-[#131929]" />
            </button>

            {/* Notifications Dropdown */}
            {showNotifications && (
              <div className="absolute right-0 mt-2 w-80 rounded-2xl bg-[#0f1422] border border-slate-800 shadow-2xl p-4 z-50 animate-in fade-in zoom-in-95 duration-150">
                <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                  <span className="text-sm font-semibold text-white">Notifications</span>
                  <span className="text-[10px] font-medium bg-[#4f46e5]/20 text-[#818cf8] px-2 py-0.5 rounded-full">
                    2 New
                  </span>
                </div>
                <div className="space-y-3 pt-3">
                  <div className="p-2.5 rounded-xl bg-[#131929] border border-slate-800 flex items-start gap-3">
                    <div className="h-2 w-2 rounded-full bg-emerald-400 mt-1.5 shrink-0" />
                    <div>
                      <p className="text-xs font-medium text-white">Build #12 ready for download</p>
                      <p className="text-[11px] text-slate-400 mt-0.5">QWRT Store Android release APK compiled successfully.</p>
                      <span className="text-[10px] text-slate-500 mt-1 block">2 hours ago</span>
                    </div>
                  </div>
                  <div className="p-2.5 rounded-xl bg-[#131929] border border-slate-800 flex items-start gap-3">
                    <div className="h-2 w-2 rounded-full bg-blue-400 mt-1.5 shrink-0 animate-pulse" />
                    <div>
                      <p className="text-xs font-medium text-white">Build in progress</p>
                      <p className="text-[11px] text-slate-400 mt-0.5">Fashion Hub build worker is generating assets.</p>
                      <span className="text-[10px] text-slate-500 mt-1 block">5 hours ago</span>
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
              className="h-10 w-10 rounded-full bg-[#6366f1] text-white font-bold text-sm flex items-center justify-center shadow-md shadow-indigo-600/30 ring-2 ring-indigo-400/20 hover:scale-105 active:scale-95 transition-all"
            >
              {userInitial}
            </button>

            {/* Profile Dropdown */}
            {showProfileMenu && (
              <div className="absolute right-0 mt-2 w-56 rounded-2xl bg-[#0f1422] border border-slate-800 shadow-2xl p-3 z-50 animate-in fade-in zoom-in-95 duration-150">
                <div className="px-3 py-2 border-b border-slate-800">
                  <p className="text-sm font-semibold text-white">{currentUser.name}</p>
                  <p className="text-xs text-slate-400 truncate">{currentUser.email}</p>
                </div>
                <div className="py-2 space-y-1">
                  <button
                    onClick={() => {
                      setActiveTab("settings");
                      setShowProfileMenu(false);
                    }}
                    className="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800/60 flex items-center gap-2"
                  >
                    <Settings className="h-3.5 w-3.5" />
                    Account Settings
                  </button>
                  <button
                    onClick={() => {
                      setActiveTab("builds");
                      setShowProfileMenu(false);
                    }}
                    className="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800/60 flex items-center gap-2"
                  >
                    <Download className="h-3.5 w-3.5" />
                    Builds & Downloads
                  </button>
                </div>
                <div className="pt-2 border-t border-slate-800">
                  <button
                    onClick={() => logout()}
                    className="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 flex items-center gap-2"
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
        {/* Left Sidebar Navigation */}
        <aside className="w-64 border-r border-slate-800/80 bg-[#090d16] p-4 flex flex-col justify-between shrink-0 hidden md:flex">
          <div className="space-y-1.5">
            {/* Dashboard Nav Item */}
            <button
              onClick={() => setActiveTab("dashboard")}
              className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-medium text-sm transition-all ${
                activeTab === "dashboard"
                  ? "bg-[#4f46e5] text-white shadow-lg shadow-indigo-600/30"
                  : "text-slate-400 hover:text-white hover:bg-[#131929]"
              }`}
            >
              <Home className="h-5 w-5" />
              <span>Dashboard</span>
            </button>

            {/* My Mobile Apps Nav Item */}
            <button
              onClick={() => setActiveTab("apps")}
              className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-medium text-sm transition-all ${
                activeTab === "apps"
                  ? "bg-[#4f46e5] text-white shadow-lg shadow-indigo-600/30"
                  : "text-slate-400 hover:text-white hover:bg-[#131929]"
              }`}
            >
              <Smartphone className="h-5 w-5" />
              <span>My Mobile Apps</span>
            </button>

            {/* Builds & Downloads Nav Item */}
            <button
              onClick={() => setActiveTab("builds")}
              className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-medium text-sm transition-all ${
                activeTab === "builds"
                  ? "bg-[#4f46e5] text-white shadow-lg shadow-indigo-600/30"
                  : "text-slate-400 hover:text-white hover:bg-[#131929]"
              }`}
            >
              <Download className="h-5 w-5" />
              <span>Builds & Downloads</span>
            </button>
          </div>

          {/* Bottom Settings Nav Item */}
          <div className="pt-4 border-t border-slate-800/80">
            <button
              onClick={() => setActiveTab("settings")}
              className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-medium text-sm transition-all ${
                activeTab === "settings"
                  ? "bg-[#4f46e5] text-white shadow-lg shadow-indigo-600/30"
                  : "text-slate-400 hover:text-white hover:bg-[#131929]"
              }`}
            >
              <Settings className="h-5 w-5" />
              <span>Settings</span>
            </button>
          </div>
        </aside>

        {/* Main Content Viewport */}
        <main className="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-8 bg-[#080c14]">
          <div className="max-w-7xl mx-auto space-y-6">
            {/* TAB 1: MAIN DASHBOARD VIEW (MATCHING THE SCREENSHOT) */}
            {activeTab === "dashboard" && (
              <>
                {/* Title and Subtitle */}
                <div>
                  <h1 className="text-3xl font-extrabold text-white tracking-tight">Dashboard</h1>
                  <p className="text-sm text-slate-400 mt-1">Manage your apps and builds in one place.</p>
                </div>

                {/* Main 2-Column Grid */}
                <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                  {/* Left Column (Main Stage) */}
                  <div className="xl:col-span-8 space-y-6">
                    {/* Welcome Banner Card */}
                    <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#171b36] via-[#151932] to-[#12162b] border border-indigo-900/40 p-6 sm:p-7 shadow-xl shadow-indigo-950/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                      <div className="flex items-center gap-4.5">
                        <div className="h-16 w-16 rounded-full bg-[#5b51d8] flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-indigo-700/30 ring-4 ring-indigo-500/15 shrink-0">
                          {userInitial}
                        </div>
                        <div>
                          <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
                            Welcome back, {currentUser.name}
                          </h2>
                          <p className="text-xs sm:text-sm text-slate-300 mt-1">
                            Manage your mobile apps, track builds, and grow your business.
                          </p>
                        </div>
                      </div>

                      {/* Sign Out Button */}
                      <button
                        onClick={() => logout()}
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-slate-200 bg-[#131929]/80 border border-slate-700/70 hover:bg-slate-800 hover:text-white transition-all shadow-sm shrink-0 self-start sm:self-auto"
                      >
                        <LogOut className="h-4 w-4" />
                        <span>Sign out</span>
                      </button>
                    </div>

                    {/* 3 Stat Cards Row */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      {/* Stat 1: Mobile Apps */}
                      <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-5 shadow-md flex items-center gap-4 hover:border-slate-700/80 transition-all">
                        <div className="h-12 w-12 rounded-xl bg-[#0c1a3a] border border-blue-500/30 flex items-center justify-center text-blue-400 shrink-0">
                          <Smartphone className="h-6 w-6" />
                        </div>
                        <div>
                          <p className="text-xs font-medium text-slate-400">Mobile Apps</p>
                          <h3 className="text-2xl font-bold text-white mt-0.5">{displayApps.length}</h3>
                          <p className="text-xs text-slate-500 mt-0.5">Total apps created</p>
                        </div>
                      </div>

                      {/* Stat 2: Successful Builds */}
                      <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-5 shadow-md flex items-center gap-4 hover:border-slate-700/80 transition-all">
                        <div className="h-12 w-12 rounded-xl bg-[#0a231b] border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                          <Download className="h-6 w-6" />
                        </div>
                        <div>
                          <p className="text-xs font-medium text-slate-400">Successful Builds</p>
                          <h3 className="text-2xl font-bold text-white mt-0.5">12</h3>
                          <p className="text-xs text-slate-500 mt-0.5">Completed builds</p>
                        </div>
                      </div>

                      {/* Stat 3: Pending Builds */}
                      <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-5 shadow-md flex items-center gap-4 hover:border-slate-700/80 transition-all">
                        <div className="h-12 w-12 rounded-xl bg-[#281a0b] border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                          <Clock className="h-6 w-6" />
                        </div>
                        <div>
                          <p className="text-xs font-medium text-slate-400">Pending Builds</p>
                          <h3 className="text-2xl font-bold text-white mt-0.5">1</h3>
                          <p className="text-xs text-slate-500 mt-0.5">Builds in queue</p>
                        </div>
                      </div>
                    </div>

                    {/* Recent Activity Table Card */}
                    <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 shadow-md">
                      <div className="flex items-center justify-between">
                        <div>
                          <h3 className="text-lg font-bold text-white">Recent Activity</h3>
                          <p className="text-xs text-slate-400 mt-0.5">Your latest apps and build activity</p>
                        </div>
                        <button
                          onClick={() => setActiveTab("apps")}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 bg-[#131929] border border-slate-700/60 hover:text-white hover:border-slate-600 transition-all"
                        >
                          <span>View All</span>
                          <ArrowRight className="h-3.5 w-3.5" />
                        </button>
                      </div>

                      {/* Activity Table */}
                      <div className="mt-5 overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                          <thead>
                            <tr className="border-b border-slate-800/80 text-xs font-medium text-slate-400">
                              <th className="pb-3 pr-4 font-medium">App Name</th>
                              <th className="pb-3 px-4 font-medium">Platform</th>
                              <th className="pb-3 px-4 font-medium">Status</th>
                              <th className="pb-3 px-4 font-medium">Updated</th>
                              <th className="pb-3 pl-4 font-medium text-right">Actions</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-slate-800/40 text-sm">
                            {displayApps.slice(0, 3).map((app) => (
                              <tr key={app.id} className="hover:bg-slate-800/20 transition-colors group">
                                {/* App Name Column */}
                                <td className="py-4 pr-4">
                                  <div className="flex items-center gap-3">
                                    <div
                                      className={`h-10 w-10 rounded-xl border flex items-center justify-center shrink-0 ${app.iconBg}`}
                                    >
                                      {app.iconType === "bag" && <ShoppingBag className="h-5 w-5" />}
                                      {app.iconType === "shirt" && <Shirt className="h-5 w-5" />}
                                      {app.iconType === "cart" && <ShoppingCart className="h-5 w-5" />}
                                    </div>
                                    <div>
                                      <Link
                                        href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}
                                        className="font-semibold text-white hover:text-indigo-400 transition-colors block"
                                      >
                                        {app.name}
                                      </Link>
                                      <span className="text-xs font-mono text-slate-400 block mt-0.5">
                                        {app.package_name}
                                      </span>
                                    </div>
                                  </div>
                                </td>

                                {/* Platform Badges Column */}
                                <td className="py-4 px-4 whitespace-nowrap">
                                  <div className="flex items-center gap-2">
                                    {app.platforms.includes("android") && (
                                      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#131929] border border-slate-700/60 text-xs font-medium text-slate-200">
                                        <span className="text-[#22c55e]">
                                          <AndroidIcon className="h-3.5 w-3.5" />
                                        </span>
                                        <span>Android</span>
                                      </span>
                                    )}
                                    {app.platforms.includes("ios") && (
                                      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#131929] border border-slate-700/60 text-xs font-medium text-slate-200">
                                        <AppleIcon className="h-3.5 w-3.5 text-white" />
                                        <span>iOS</span>
                                      </span>
                                    )}
                                  </div>
                                </td>

                                {/* Status Column */}
                                <td className="py-4 px-4 whitespace-nowrap">
                                  {app.status === "Ready" ? (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-[#0a231b] border border-emerald-500/30 text-emerald-400">
                                      <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                                      Ready
                                    </span>
                                  ) : (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-[#0c1a3a] border border-blue-500/30 text-blue-400">
                                      <span className="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse" />
                                      Building
                                    </span>
                                  )}
                                </td>

                                {/* Updated Column */}
                                <td className="py-4 px-4 whitespace-nowrap text-xs text-slate-400">{app.updated}</td>

                                {/* Actions Column */}
                                <td className="py-4 pl-4 text-right relative">
                                  <button
                                    onClick={() =>
                                      setActiveMenuAppId(activeMenuAppId === app.id ? null : app.id)
                                    }
                                    title="More options"
                                    className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors inline-block"
                                  >
                                    <MoreVertical className="h-4 w-4" />
                                  </button>

                                  {/* Three Dots Context Menu */}
                                  {activeMenuAppId === app.id && (
                                    <div className="absolute right-2 top-12 w-48 rounded-xl bg-[#0b0f1a] border border-slate-800 shadow-xl py-1.5 z-30 text-left animate-in fade-in zoom-in-95 duration-100">
                                      <Link
                                        href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}
                                        className="block px-3 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-800/60"
                                      >
                                        Open Setup Wizard
                                      </Link>
                                      <button
                                        onClick={() => {
                                          setActiveTab("builds");
                                          setActiveMenuAppId(null);
                                        }}
                                        className="w-full text-left px-3 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-800/60"
                                      >
                                        View Build Logs
                                      </button>
                                      <Link
                                        href={`/dashboard/apps/${app.ulid}/setup/build`}
                                        className="block px-3 py-2 text-xs text-indigo-400 hover:bg-slate-800/60"
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

                  {/* Right Column (Side Panels) */}
                  <div className="xl:col-span-4 space-y-6">
                    {/* Quick Actions Card */}
                    <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 shadow-md">
                      <div className="h-11 w-11 rounded-xl bg-[#1a1c3d] border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <Zap className="h-5 w-5" />
                      </div>
                      <h3 className="text-base font-bold text-white mt-3">Quick Actions</h3>
                      <p className="text-xs text-slate-400 mt-1">Get started with common tasks.</p>

                      <div className="mt-5 space-y-3">
                        <Link href="/dashboard/apps/new" className="block">
                          <button className="w-full bg-[#4f46e5] hover:bg-[#4338ca] text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 transition-all active:scale-[0.99] text-sm">
                            <Plus className="h-4 w-4" />
                            <span>Create New App</span>
                          </button>
                        </Link>

                        <button
                          onClick={() => setActiveTab("builds")}
                          className="w-full bg-[#131929] hover:bg-slate-800 text-slate-200 border border-slate-700/60 font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.99] text-sm"
                        >
                          <Download className="h-4 w-4" />
                          <span>View Builds</span>
                        </button>
                      </div>
                    </div>

                    {/* Getting Started Card */}
                    <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 shadow-md">
                      <div className="flex items-start gap-3">
                        <div className="h-11 w-11 rounded-xl bg-[#1a1c3d] border border-indigo-500/30 flex items-center justify-center text-indigo-400 shrink-0">
                          <BookOpen className="h-5 w-5" />
                        </div>
                        <div>
                          <h3 className="text-base font-bold text-white">Getting Started</h3>
                          <p className="text-xs text-slate-400 mt-0.5">2 of 3 tasks complete</p>
                        </div>
                      </div>

                      {/* Progress Bar */}
                      <div className="flex items-center gap-3 mt-5">
                        <div className="flex-1 h-2 rounded-full bg-slate-800 overflow-hidden">
                          <div className="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 w-[67%]" />
                        </div>
                        <span className="text-xs font-bold text-slate-300">67%</span>
                      </div>

                      {/* Tasks Checklist */}
                      <div className="space-y-3.5 mt-5">
                        <div className="flex items-center gap-3 text-slate-300">
                          <div className="h-5 w-5 rounded-full bg-[#4f46e5] flex items-center justify-center text-white shrink-0">
                            <Check className="h-3 w-3 stroke-[3]" />
                          </div>
                          <span className="text-xs font-medium line-through decoration-slate-500/50">
                            Create your first app
                          </span>
                        </div>

                        <div className="flex items-center gap-3 text-slate-300">
                          <div className="h-5 w-5 rounded-full bg-[#4f46e5] flex items-center justify-center text-white shrink-0">
                            <Check className="h-3 w-3 stroke-[3]" />
                          </div>
                          <span className="text-xs font-medium line-through decoration-slate-500/50">
                            Build your app
                          </span>
                        </div>

                        <div className="flex items-center gap-3 text-slate-400">
                          <div className="h-5 w-5 rounded-full border-2 border-slate-600 flex items-center justify-center shrink-0" />
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
                    <h1 className="text-3xl font-extrabold text-white tracking-tight">My Mobile Apps</h1>
                    <p className="text-sm text-slate-400 mt-1">
                      Configure, brand, preview, and build your white-label native store apps.
                    </p>
                  </div>
                  <Link href="/dashboard/apps/new">
                    <button className="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-semibold py-2.5 px-5 rounded-xl shadow-lg shadow-indigo-600/30 flex items-center gap-2 text-sm">
                      <Plus className="h-4 w-4" />
                      <span>Create New App</span>
                    </button>
                  </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {displayApps.map((app) => (
                    <div
                      key={app.id}
                      className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 flex flex-col justify-between hover:border-indigo-500/40 transition-all hover:shadow-xl hover:shadow-indigo-950/20"
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
                              <h3 className="font-bold text-white text-base">{app.name}</h3>
                              <p className="text-xs font-mono text-slate-400 mt-0.5">{app.package_name}</p>
                            </div>
                          </div>
                        </div>

                        <div className="flex items-center gap-2 pt-2">
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#131929] border border-slate-700/60 text-xs font-medium text-slate-200">
                            <AndroidIcon className="h-3.5 w-3.5 text-[#22c55e]" />
                            <span>Android</span>
                          </span>
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#131929] border border-slate-700/60 text-xs font-medium text-slate-200">
                            <AppleIcon className="h-3.5 w-3.5 text-white" />
                            <span>iOS</span>
                          </span>
                        </div>
                      </div>

                      <div className="pt-6 border-t border-slate-800/80 mt-6 space-y-3">
                        <div className="flex items-center justify-between text-xs text-slate-400">
                          <span>Setup Step</span>
                          <span className="font-semibold text-slate-200 uppercase tracking-wider text-[11px]">
                            {app.setup_step || "Platform"}
                          </span>
                        </div>
                        <Link href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}>
                          <button className="w-full bg-[#131929] hover:bg-[#1a2238] text-white border border-slate-700/60 font-medium py-2 px-4 rounded-xl flex items-between justify-between transition-colors text-xs">
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
                    <h1 className="text-3xl font-extrabold text-white tracking-tight">Builds & Downloads</h1>
                    <p className="text-sm text-slate-400 mt-1">
                      Monitor ongoing compilation jobs and download generated APK / AAB bundles.
                    </p>
                  </div>
                  <button
                    onClick={() => setActiveTab("dashboard")}
                    className="bg-[#131929] hover:bg-slate-800 text-slate-300 font-semibold py-2 px-4 rounded-xl border border-slate-700/60 flex items-center gap-2 text-xs"
                  >
                    <ArrowRight className="h-3.5 w-3.5 rotate-180" />
                    <span>Back to Dashboard</span>
                  </button>
                </div>

                <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 shadow-md overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="border-b border-slate-800/80 text-xs font-medium text-slate-400">
                        <th className="pb-3 pr-4 font-medium">Build ID & App</th>
                        <th className="pb-3 px-4 font-medium">Version</th>
                        <th className="pb-3 px-4 font-medium">Target</th>
                        <th className="pb-3 px-4 font-medium">Status</th>
                        <th className="pb-3 px-4 font-medium">Created</th>
                        <th className="pb-3 pl-4 font-medium text-right">Artifact</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800/40 text-sm">
                      {buildsList.map((b) => (
                        <tr key={b.id} className="hover:bg-slate-800/20 transition-colors">
                          <td className="py-4 pr-4">
                            <div className="font-semibold text-white">{b.appName}</div>
                            <span className="text-xs text-slate-500 font-mono">{b.id}</span>
                          </td>
                          <td className="py-4 px-4 text-xs font-mono text-slate-300">{b.version}</td>
                          <td className="py-4 px-4 text-xs text-slate-300">{b.platform}</td>
                          <td className="py-4 px-4">
                            {b.status === "Successful" ? (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#0a231b] border border-emerald-500/30 text-emerald-400">
                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                                Successful
                              </span>
                            ) : (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#0c1a3a] border border-blue-500/30 text-blue-400">
                                <span className="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse" />
                                In Progress
                              </span>
                            )}
                          </td>
                          <td className="py-4 px-4 text-xs text-slate-400">{b.date}</td>
                          <td className="py-4 pl-4 text-right">
                            {b.downloadUrl ? (
                              <a
                                href={b.downloadUrl}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#4f46e5] hover:bg-[#4338ca] text-white text-xs font-medium transition-all shadow-md shadow-indigo-600/20"
                              >
                                <Download className="h-3.5 w-3.5" />
                                <span>Download ({b.size})</span>
                              </a>
                            ) : (
                              <span className="text-xs text-slate-500 italic">Compiling...</span>
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
                  <h1 className="text-3xl font-extrabold text-white tracking-tight">Account & Tenant Settings</h1>
                  <p className="text-sm text-slate-400 mt-1">
                    Manage your credentials, API tokens, and subscription tiers.
                  </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 space-y-4">
                    <h3 className="font-bold text-white text-base flex items-center gap-2">
                      <Shield className="h-5 w-5 text-indigo-400" />
                      <span>Tenant Profile</span>
                    </h3>
                    <div className="space-y-3 pt-2">
                      <div>
                        <label className="text-xs font-medium text-slate-400">Account Name</label>
                        <p className="text-sm font-semibold text-white mt-0.5">{currentUser.name}</p>
                      </div>
                      <div>
                        <label className="text-xs font-medium text-slate-400">Email Address</label>
                        <p className="text-sm font-semibold text-white mt-0.5">{currentUser.email}</p>
                      </div>
                      <div>
                        <label className="text-xs font-medium text-slate-400">Tenant ID</label>
                        <p className="text-xs font-mono text-indigo-400 mt-0.5">tenant_{currentUser.tenant_id || 1}</p>
                      </div>
                    </div>
                  </div>

                  <div className="rounded-2xl bg-[#0f1422] border border-slate-800/80 p-6 space-y-4">
                    <h3 className="font-bold text-white text-base flex items-center gap-2">
                      <FileCode className="h-5 w-5 text-indigo-400" />
                      <span>Build Runner Keys</span>
                    </h3>
                    <p className="text-xs text-slate-400">
                      Automate Flutter app compilation using our secure webhook integration API.
                    </p>
                    <div className="p-3 rounded-xl bg-[#131929] border border-slate-800 flex items-center justify-between">
                      <span className="text-xs font-mono text-slate-300">wia_live_key_998492048</span>
                      <button
                        onClick={() => alert("API Key copied to clipboard")}
                        className="text-xs text-indigo-400 hover:text-indigo-300 font-medium"
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
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-start justify-center pt-24 px-4 animate-in fade-in duration-150">
          <div className="w-full max-w-xl rounded-2xl bg-[#0f1422] border border-slate-800 shadow-2xl overflow-hidden">
            <div className="p-4 border-b border-slate-800 flex items-center gap-3">
              <Search className="h-5 w-5 text-slate-400" />
              <input
                type="text"
                autoFocus
                placeholder="Search apps by name or package..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-transparent text-sm text-white focus:outline-none placeholder:text-slate-500"
              />
              <button
                onClick={() => setShowSearchModal(false)}
                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            <div className="max-h-72 overflow-y-auto p-2 divide-y divide-slate-800/40">
              {filteredApps.length === 0 ? (
                <div className="p-6 text-center text-xs text-slate-400">No matching apps found.</div>
              ) : (
                filteredApps.map((app) => (
                  <Link
                    key={app.id}
                    href={`/dashboard/apps/${app.ulid}/setup/${app.setup_step || "connect"}`}
                    onClick={() => setShowSearchModal(false)}
                    className="flex items-center justify-between p-3 rounded-xl hover:bg-slate-800/40 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div className={`h-8 w-8 rounded-lg border flex items-center justify-center ${app.iconBg}`}>
                        {app.iconType === "bag" && <ShoppingBag className="h-4 w-4" />}
                        {app.iconType === "shirt" && <Shirt className="h-4 w-4" />}
                        {app.iconType === "cart" && <ShoppingCart className="h-4 w-4" />}
                      </div>
                      <div>
                        <p className="text-xs font-semibold text-white">{app.name}</p>
                        <p className="text-[11px] font-mono text-slate-400">{app.package_name}</p>
                      </div>
                    </div>
                    <ArrowRight className="h-3.5 w-3.5 text-slate-400" />
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
