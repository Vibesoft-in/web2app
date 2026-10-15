"use client";

import React from "react";
import { ShoppingBag, Search, Menu, ChevronRight } from "lucide-react";
import thumbs from "./thumbs.json";

export interface PhoneFrameProps {
  appName?: string;
  config?: {
    theme?: {
      layout?: string;
      colors?: {
        primary?: string;
        secondary?: string;
        background?: string;
        surface?: string;
        text?: string;
      };
      radius?: number;
      font?: string;
      nav?: string;
    };
    home?: Array<{
      type: string;
      title?: string;
      source?: string;
      items?: Array<{ image?: string; link?: string }>;
    }>;
  } | null;
  products?: Array<{
    id: string;
    title: string;
    price: number;
    sale_price?: number | null;
    images: string[];
  }>;
  categories?: Array<{
    id: number;
    name: string;
    count?: number;
  }>;
}

export function PhoneFrame({
  appName = "qwrt",
  config,
  products = [],
  categories = [],
}: PhoneFrameProps) {
  const theme = config?.theme;
  const colors = theme?.colors || {};
  const primaryColor = colors.primary || "#6366F1";
  const backgroundColor = colors.background || "#FFFFFF";
  const textColor = colors.text || "#0F172A";
  const font = theme?.font || "Inter, system-ui, sans-serif";

  const defaultProducts = [
    {
      id: "1",
      title: "Sports Shoes",
      price: 2499,
      sale_price: null,
      images: [thumbs.sneaker || "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&q=80"],
    },
    {
      id: "2",
      title: "Smart Watch",
      price: 4999,
      sale_price: null,
      images: [thumbs.smartwatch || "https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80"],
    },
  ];

  const displayProducts = products.length > 0 ? products.slice(0, 4) : defaultProducts;

  const defaultCategories = [
    { id: 1, name: "All", icon: "" },
    { id: 2, name: "Trending", icon: "🔥" },
    { id: 3, name: "Fashion", icon: "👗" },
    { id: 4, name: "Shoes", icon: "👟" },
    { id: 5, name: "Electronics", icon: "🎧" },
  ];

  const displayCategories =
    categories.length > 0
      ? categories.slice(0, 5).map((c, i) => ({
          ...c,
          icon: defaultCategories[i]?.icon || "🏷️",
        }))
      : defaultCategories;

  const appInitial = (appName && appName.length > 0 ? appName.charAt(0) : "Q").toUpperCase();

  return (
    <div
      data-testid="phone-frame"
      style={{
        width: "360px",
        maxWidth: "100%",
        height: "710px",
        borderRadius: "48px",
        border: "10px solid #1c1d22",
        backgroundColor,
        color: textColor,
        boxShadow: "0 25px 60px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.08)",
        position: "relative",
        overflow: "hidden",
        display: "flex",
        flexDirection: "column",
        justifyContent: "space-between",
        flexShrink: 0,
        boxSizing: "border-box",
        fontFamily: font,
        userSelect: "none",
        margin: "0 auto",
      }}
    >
      {/* Dynamic Island Top Pill */}
      <div
        style={{
          position: "absolute",
          top: "10px",
          left: "50%",
          transform: "translateX(-50%)",
          width: "108px",
          height: "26px",
          backgroundColor: "#000000",
          borderRadius: "20px",
          zIndex: 50,
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          padding: "0 10px",
          boxShadow: "0 2px 6px rgba(0,0,0,0.5)",
          boxSizing: "border-box",
        }}
      >
        <span
          style={{
            width: "9px",
            height: "9px",
            borderRadius: "50%",
            backgroundColor: "#111827",
            border: "1.5px solid #1f2937",
          }}
        />
        <span
          style={{
            width: "7px",
            height: "7px",
            borderRadius: "50%",
            backgroundColor: "#059669",
          }}
        />
      </div>

      {/* Top Status Bar */}
      <div
        style={{
          padding: "12px 22px 2px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          fontSize: "13px",
          fontWeight: 700,
          color: "#0f172a",
          position: "relative",
          zIndex: 30,
          boxSizing: "border-box",
        }}
      >
        <span>9:41</span>
        <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
          {/* Signal */}
          <svg style={{ width: "14px", height: "14px" }} viewBox="0 0 24 24" fill="currentColor">
            <rect x="2" y="16" width="3" height="6" rx="1" />
            <rect x="7" y="12" width="3" height="10" rx="1" />
            <rect x="12" y="8" width="3" height="14" rx="1" />
            <rect x="17" y="4" width="3" height="18" rx="1" />
          </svg>
          {/* Wi-Fi */}
          <svg style={{ width: "14px", height: "14px" }} viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 4c3.34 0 6.38 1.38 8.56 3.61L12 19.33 3.44 11.61C5.62 9.38 8.66 8 12 8z" />
          </svg>
          {/* Battery */}
          <div
            style={{
              width: "20px",
              height: "10px",
              borderRadius: "3px",
              border: "1.5px solid currentColor",
              padding: "1px",
              display: "flex",
              alignItems: "center",
            }}
          >
            <div style={{ height: "100%", width: "70%", backgroundColor: "currentColor", borderRadius: "1px" }} />
          </div>
        </div>
      </div>

      {/* In-App Header Bar */}
      <div
        style={{
          padding: "6px 18px 8px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          boxSizing: "border-box",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
          <div
            style={{
              width: "38px",
              height: "38px",
              borderRadius: "12px",
              color: "#ffffff",
              fontSize: "18px",
              fontWeight: 800,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              backgroundColor: primaryColor,
              boxShadow: "0 2px 6px rgba(0,0,0,0.15)",
            }}
          >
            {appInitial}
          </div>
          <span
            data-testid="phone-app-name"
            style={{
              fontWeight: 700,
              fontSize: "14px",
              color: "#0f172a",
              maxWidth: "140px",
              whiteSpace: "nowrap",
              overflow: "hidden",
              textOverflow: "ellipsis",
            }}
          >
            {appName}
          </span>
        </div>

        <div style={{ display: "flex", alignItems: "center", gap: "14px" }}>
          <div style={{ position: "relative", display: "flex", alignItems: "center", justifyContent: "center" }}>
            <ShoppingBag style={{ width: "22px", height: "22px", color: "#334155" }} />
            <span
              style={{
                position: "absolute",
                top: "-6px",
                right: "-7px",
                width: "17px",
                height: "17px",
                borderRadius: "50%",
                backgroundColor: "#3b82f6",
                color: "#ffffff",
                fontSize: "10px",
                fontWeight: 700,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                boxShadow: "0 1px 3px rgba(0,0,0,0.2)",
              }}
            >
              2
            </span>
          </div>

          <button type="button" style={{ background: "transparent", border: "none", cursor: "pointer", padding: "2px" }}>
            <Menu style={{ width: "22px", height: "22px", color: "#1e293b" }} />
          </button>
        </div>
      </div>

      {/* In-App Search Bar */}
      <div
        style={{
          margin: "2px 18px 10px",
          borderRadius: "12px",
          backgroundColor: "#ffffff",
          border: "1.5px solid #e2e8f0",
          padding: "9px 14px",
          display: "flex",
          alignItems: "center",
          gap: "10px",
          boxShadow: "0 1px 2px rgba(0,0,0,0.03)",
          boxSizing: "border-box",
        }}
      >
        <Search style={{ width: "15px", height: "15px", color: "#64748b", flexShrink: 0 }} />
        <span style={{ fontSize: "13px", color: "#94a3b8", fontWeight: 500 }}>
          Search in {appName}...
        </span>
      </div>

      {/* Hero Promotional Card */}
      <div
        style={{
          margin: "0 18px 12px",
          borderRadius: "18px",
          padding: "14px 16px",
          color: "#ffffff",
          position: "relative",
          overflow: "hidden",
          boxShadow: "0 6px 16px rgba(0,0,0,0.12)",
          boxSizing: "border-box",
          background: `linear-gradient(135deg, ${primaryColor} 0%, #1e1b4b 100%)`,
        }}
      >
        <span
          style={{
            display: "inline-block",
            padding: "3px 10px",
            borderRadius: "999px",
            fontSize: "10px",
            fontWeight: 800,
            backgroundColor: "#10b981",
            color: "#ffffff",
            letterSpacing: "0.3px",
            marginBottom: "6px",
          }}
        >
          Special Offer
        </span>
        <h4 style={{ fontSize: "14px", fontWeight: 800, color: "#ffffff", margin: "0 0 3px 0", lineHeight: 1.25 }}>
          30% OFF Your First Mobile Order
        </h4>
        <p style={{ fontSize: "12px", color: "rgba(255, 255, 255, 0.9)", margin: "0 0 10px 0", fontWeight: 500 }}>
          Use code: <span style={{ fontWeight: 800, textDecoration: "underline" }}>APPSTORE</span>
        </p>

        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <button
            type="button"
            style={{
              padding: "5px 14px",
              borderRadius: "999px",
              fontSize: "11px",
              fontWeight: 700,
              backgroundColor: "#ffffff",
              color: "#0f172a",
              border: "none",
              cursor: "pointer",
              boxShadow: "0 2px 4px rgba(0,0,0,0.1)",
            }}
          >
            Shop Now &gt;
          </button>
          <div style={{ display: "flex", alignItems: "center", gap: "4px" }}>
            <span style={{ width: "7px", height: "7px", borderRadius: "50%", backgroundColor: "#ffffff" }} />
            <span style={{ width: "5px", height: "5px", borderRadius: "50%", backgroundColor: "rgba(255,255,255,0.4)" }} />
            <span style={{ width: "5px", height: "5px", borderRadius: "50%", backgroundColor: "rgba(255,255,255,0.4)" }} />
            <span style={{ width: "5px", height: "5px", borderRadius: "50%", backgroundColor: "rgba(255,255,255,0.4)" }} />
          </div>
        </div>
      </div>

      {/* Categories Horizontal Row */}
      <div
        style={{
          margin: "0 18px 12px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          boxSizing: "border-box",
        }}
      >
        {displayCategories.map((cat, idx) => (
          <div key={cat.id} style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: "4px" }}>
            <div
              style={{
                width: "42px",
                height: "42px",
                borderRadius: "50%",
                backgroundColor: idx === 0 ? primaryColor : idx === 1 ? "#fff1f2" : idx === 2 ? "#eff6ff" : idx === 3 ? "#faf5ff" : "#f8fafc",
                border: idx === 0 ? "none" : "1px solid rgba(0,0,0,0.06)",
                color: idx === 0 ? "#ffffff" : "#0f172a",
                fontSize: idx === 0 ? "12px" : "18px",
                fontWeight: 700,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                boxShadow: idx === 0 ? "0 2px 6px rgba(0,0,0,0.15)" : "none",
                cursor: "pointer",
              }}
            >
              {idx === 0 ? "All" : cat.icon}
            </div>
            <span style={{ fontSize: "11px", fontWeight: 600, color: "#475569" }}>{cat.name}</span>
          </div>
        ))}
      </div>

      {/* Featured Products Section */}
      <div style={{ margin: "0 18px 6px", boxSizing: "border-box" }}>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "6px" }}>
          <h5 style={{ fontSize: "14px", fontWeight: 800, color: "#0f172a", margin: 0 }}>Featured Products</h5>
          <span style={{ fontSize: "12px", fontWeight: 700, color: "#3b82f6", cursor: "pointer", display: "flex", alignItems: "center" }}>
            View All <ChevronRight style={{ width: "12px", height: "12px", marginLeft: "2px" }} />
          </span>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "10px" }}>
          {displayProducts.map((p) => {
            const img = p.images?.[0] || thumbs.sneaker;
            return (
              <div
                key={p.id}
                style={{
                  borderRadius: "16px",
                  backgroundColor: "#f8fafc",
                  border: "1px solid #e2e8f0",
                  padding: "10px",
                  display: "flex",
                  flexDirection: "column",
                  justifyContent: "space-between",
                  boxShadow: "0 1px 2px rgba(0,0,0,0.03)",
                  boxSizing: "border-box",
                }}
              >
                <div
                  style={{
                    width: "100%",
                    height: "90px",
                    borderRadius: "12px",
                    backgroundColor: "#ffffff",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    overflow: "hidden",
                    marginBottom: "6px",
                  }}
                >
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={img} alt={p.title} style={{ maxWidth: "100%", maxHeight: "100%", objectFit: "contain" }} />
                </div>
                <div>
                  <h6
                    style={{
                      fontSize: "12px",
                      fontWeight: 700,
                      color: "#1e293b",
                      margin: "0 0 4px 0",
                      whiteSpace: "nowrap",
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                    }}
                  >
                    {p.title}
                  </h6>
                  <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
                    <span style={{ fontSize: "13px", fontWeight: 900, color: "#0f172a" }}>
                      ₹{p.sale_price ?? p.price}
                    </span>
                    <button
                      type="button"
                      style={{
                        width: "24px",
                        height: "24px",
                        borderRadius: "7px",
                        color: "#ffffff",
                        border: "none",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        cursor: "pointer",
                        backgroundColor: primaryColor,
                      }}
                    >
                      <ShoppingBag style={{ width: "13px", height: "13px" }} />
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Bottom Home Indicator Bar */}
      <div
        style={{
          width: "120px",
          height: "4px",
          backgroundColor: "#0f172a",
          borderRadius: "999px",
          margin: "6px auto 10px",
          flexShrink: 0,
        }}
      />
    </div>
  );
}
