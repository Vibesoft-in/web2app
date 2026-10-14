# ==============================================================================
# Multi-stage Production Dockerfile: Next.js 14 Dashboard
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Dependencies Installer
# ------------------------------------------------------------------------------
FROM node:20-alpine AS deps
RUN apk add --no-cache libc6-compat
WORKDIR /app

# Install dependencies based on lockfile
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline --no-audit

# ------------------------------------------------------------------------------
# Stage 2: Application Builder
# ------------------------------------------------------------------------------
FROM node:20-alpine AS builder
WORKDIR /app

COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Set production build environment variables
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

# Build the Next.js standalone application
RUN npm run build

# ------------------------------------------------------------------------------
# Stage 3: Production Runner (Minimal image)
# ------------------------------------------------------------------------------
FROM node:20-alpine AS runner
WORKDIR /app

LABEL maintainer="WebIntoApp Engineering <ops@webintoapp.com>"
LABEL description="Minimal production runner for WebIntoApp Next.js Dashboard"

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV PORT=3000
ENV HOSTNAME="0.0.0.0"

# Install curl for health checks
RUN apk add --no-cache curl

# Security: Create non-root system user and group
RUN addgroup --system --gid 1001 nodejs && \
    adduser --system --uid 1001 nextjs

# Set permissions for prerender cache and runtime assets
RUN mkdir -p /app/.next && chown nextjs:nodejs /app/.next

# Copy standalone build output and static assets
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
COPY --from=builder --chown=nextjs:nodejs /app/package.json ./

USER nextjs

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://127.0.0.1:3000/ || exit 1

CMD ["node", "server.js"]
