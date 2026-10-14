"use client";

import React from "react";
import { ShoppingBag, Heart, Search, Home, User, Battery, Wifi, Signal, ChevronRight } from "lucide-react";

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
  appName = "Store App",
  config,
  products = [],
  categories = [],
}: PhoneFrameProps) {
  const theme = config?.theme;
  const colors = theme?.colors || {};
  const primaryColor = colors.primary || "#6366F1";
  const backgroundColor = colors.background || "#FFFFFF";
  const surfaceColor = colors.surface || "#F9FAFB";
  const textColor = colors.text || "#111827";
  const radius = theme?.radius ?? 12;
  const font = theme?.font || "Inter";

  const displayProducts =
    products.length > 0
      ? products.slice(0, 4)
      : [
          {
            id: "1",
            title: "Classic Cotton Hoodie",
            price: 1999,
            sale_price: 1499,
            images: ["https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=500&q=80"],
          },
          {
            id: "2",
            title: "Minimalist Leather Watch",
            price: 3499,
            sale_price: null,
            images: ["https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80"],
          },
          {
            id: "3",
            title: "Wireless Sport Earbuds",
            price: 2499,
            sale_price: 1899,
            images: ["https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=500&q=80"],
          },
          {
            id: "4",
            title: "Everyday Canvas Sneakers",
            price: 2999,
            sale_price: null,
            images: ["https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&q=80"],
          },
        ];

  const displayCategories =
    categories.length > 0
      ? categories.slice(0, 5)
      : [
          { id: 1, name: "Trending" },
          { id: 2, name: "Apparel" },
          { id: 3, name: "Footwear" },
          { id: 4, name: "Accessories" },
        ];

  return (
    <div
      data-testid="phone-frame"
      className="relative mx-auto w-[320px] sm:w-[340px] h-[660px] bg-slate-950 rounded-[44px] p-3 shadow-2xl border-4 border-slate-800 ring-1 ring-slate-700/50 flex flex-col select-none"
    >
      {/* Device Outer Frame Speaker Notch */}
      <div className="absolute top-4 left-1/2 -translate-x-1/2 w-28 h-5 bg-slate-900 rounded-full z-30 flex items-center justify-center space-x-2">
        <div className="w-2.5 h-2.5 rounded-full bg-slate-950 border border-slate-800" />
        <div className="w-10 h-1 bg-slate-800 rounded-full" />
      </div>

      {/* Internal Phone Screen */}
      <div
        className="w-full h-full rounded-[34px] overflow-hidden flex flex-col relative"
        style={{
          backgroundColor,
          color: textColor,
          fontFamily: font,
        }}
      >
        {/* Status Bar */}
        <div className="h-10 pt-2 px-6 flex items-center justify-between text-[11px] font-semibold opacity-80 z-20">
          <span>9:41</span>
          <div className="flex items-center space-x-1.5">
            <Signal className="h-3 w-3" />
            <Wifi className="h-3 w-3" />
            <Battery className="h-3.5 w-3.5" />
          </div>
        </div>

        {/* App Header */}
        <div
          className="px-4 py-2.5 flex items-center justify-between border-b"
          style={{ borderColor: "rgba(0,0,0,0.06)" }}
        >
          <div className="flex items-center space-x-2">
            <div
              className="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-sm"
              style={{ backgroundColor: primaryColor }}
            >
              {appName.charAt(0) || "W"}
            </div>
            <span
              data-testid="phone-app-name"
              className="font-bold text-sm tracking-tight truncate max-w-[150px]"
            >
              {appName}
            </span>
          </div>

          <div className="flex items-center space-x-2.5">
            <button className="p-1 rounded-full hover:bg-black/5">
              <Search className="h-4 w-4" />
            </button>
            <button className="p-1 rounded-full hover:bg-black/5 relative">
              <ShoppingBag className="h-4 w-4" />
              <span
                className="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full text-[9px] font-bold text-white flex items-center justify-center"
                style={{ backgroundColor: primaryColor }}
              >
                2
              </span>
            </button>
          </div>
        </div>

        {/* Scrollable Screen Content */}
        <div className="flex-1 overflow-y-auto p-3.5 space-y-4 no-scrollbar">
          {/* Hero Banner */}
          <div
            className="w-full h-32 rounded-2xl p-4 flex flex-col justify-end text-white relative overflow-hidden shadow-md"
            style={{
              borderRadius: `${radius}px`,
              background: `linear-gradient(135deg, ${primaryColor}, #1e1b4b)`,
            }}
          >
            <div className="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-white/20 backdrop-blur-sm">
              NEW SEASON
            </div>
            <p className="text-xs font-medium text-white/80">Exclusive Drop</p>
            <h4 className="text-base font-extrabold leading-tight">Summer Collection</h4>
            <p className="text-[10px] text-white/90 mt-0.5 font-medium">Up to 40% OFF this week</p>
          </div>

          {/* Categories Pill Rail */}
          <div className="flex items-center space-x-2 overflow-x-auto pb-1 no-scrollbar">
            {displayCategories.map((cat, idx) => (
              <div
                key={cat.id}
                className="px-3 py-1 rounded-full text-[11px] font-medium whitespace-nowrap shadow-xs transition-colors flex-shrink-0 cursor-pointer"
                style={{
                  borderRadius: `${radius}px`,
                  backgroundColor: idx === 0 ? primaryColor : surfaceColor,
                  color: idx === 0 ? "#ffffff" : textColor,
                  border: idx === 0 ? "none" : "1px solid rgba(0,0,0,0.06)",
                }}
              >
                {cat.name}
              </div>
            ))}
          </div>

          {/* Section Heading */}
          <div className="flex items-center justify-between pt-1">
            <span className="font-bold text-xs uppercase tracking-wider text-muted-foreground/80">Featured Products</span>
            <span className="text-[11px] font-semibold flex items-center cursor-pointer" style={{ color: primaryColor }}>
              View all <ChevronRight className="h-3 w-3 ml-0.5" />
            </span>
          </div>

          {/* Products Grid */}
          <div className="grid grid-cols-2 gap-2.5">
            {displayProducts.map((p) => {
              const img = p.images?.[0] || "https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80";
              return (
                <div
                  key={p.id}
                  className="rounded-xl overflow-hidden shadow-xs border flex flex-col justify-between group transition-transform"
                  style={{
                    borderRadius: `${radius}px`,
                    backgroundColor: surfaceColor,
                    borderColor: "rgba(0,0,0,0.06)",
                  }}
                >
                  <div className="h-24 w-full bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={img} alt={p.title} className="w-full h-full object-cover" />
                    <button className="absolute top-1.5 right-1.5 p-1 rounded-full bg-white/80 dark:bg-black/60 shadow-xs">
                      <Heart className="h-3 w-3 text-rose-500" />
                    </button>
                  </div>

                  <div className="p-2 space-y-1">
                    <p className="text-[11px] font-semibold truncate leading-tight">{p.title}</p>
                    <div className="flex items-baseline space-x-1.5">
                      <span className="text-xs font-extrabold" style={{ color: primaryColor }}>
                        ₹{p.sale_price ?? p.price}
                      </span>
                      {p.sale_price && (
                        <span className="text-[10px] text-muted-foreground line-through">₹{p.price}</span>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Bottom Navigation Bar */}
        <div
          className="h-14 px-6 flex items-center justify-between border-t z-20"
          style={{
            backgroundColor: surfaceColor,
            borderColor: "rgba(0,0,0,0.06)",
          }}
        >
          <div className="flex flex-col items-center space-y-0.5 cursor-pointer" style={{ color: primaryColor }}>
            <Home className="h-4 w-4" />
            <span className="text-[9px] font-bold">Home</span>
          </div>

          <div className="flex flex-col items-center space-y-0.5 text-muted-foreground cursor-pointer">
            <Search className="h-4 w-4" />
            <span className="text-[9px] font-medium">Search</span>
          </div>

          <div className="flex flex-col items-center space-y-0.5 text-muted-foreground cursor-pointer">
            <Heart className="h-4 w-4" />
            <span className="text-[9px] font-medium">Wishlist</span>
          </div>

          <div className="flex flex-col items-center space-y-0.5 text-muted-foreground cursor-pointer">
            <User className="h-4 w-4" />
            <span className="text-[9px] font-medium">Profile</span>
          </div>
        </div>
      </div>
    </div>
  );
}
