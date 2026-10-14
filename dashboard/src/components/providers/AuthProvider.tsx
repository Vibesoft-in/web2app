"use client";

import React, { createContext, useContext } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api-client";
import { useRouter } from "next/navigation";

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  tenant_id: number;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  logout: () => Promise<void>;
  refetchUser: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data, isLoading, refetch } = useQuery({
    queryKey: ["auth-me"],
    queryFn: async () => {
      try {
        const res = await apiFetch<{ data: User }>("/api/auth/me");
        return res?.data || null;
      } catch {
        return null;
      }
    },
    staleTime: 1000 * 60 * 5, // 5 min
  });

  const logoutMutation = useMutation({
    mutationFn: async () => {
      await apiFetch("/api/auth/logout", { method: "POST" });
    },
    onSuccess: () => {
      queryClient.setQueryData(["auth-me"], null);
      queryClient.clear();
      router.push("/login");
    },
  });

  const user = data ?? null;

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user,
        logout: async () => {
          await logoutMutation.mutateAsync();
        },
        refetchUser: () => refetch(),
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}
