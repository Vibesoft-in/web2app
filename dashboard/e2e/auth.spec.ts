import { test, expect } from "@playwright/test";

test.describe("Authentication Flows", () => {
  test("signup validation and successful registration via BFF route handler", async ({ page }) => {
    // Mock the backend registration endpoint called by Next.js BFF handler
    await page.route("**/api/auth/register", async (route) => {
      const json = await route.request().postDataJSON();
      if (!json.email || !json.password) {
        return route.fulfill({
          status: 422,
          contentType: "application/json",
          body: JSON.stringify({ error: { code: "VALIDATION_ERROR", message: "Email and password are required" } }),
        });
      }
      return route.fulfill({
        status: 201,
        contentType: "application/json",
        headers: {
          "Set-Cookie": "auth_token=sanctum_mock_token_12345; Path=/; HttpOnly; SameSite=Lax",
        },
        body: JSON.stringify({
          data: {
            user: { id: 1, name: json.name, email: json.email },
            tenant: { id: "01JTESTTENANT", name: json.company_name },
          },
        }),
      });
    });

    // Mock /api/auth/me to return user
    await page.route("**/api/auth/me", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            user: { id: 1, name: "Test Merchant", email: "merchant@test.com" },
            tenant: { id: "01JTESTTENANT", name: "Test Store" },
          },
        }),
      });
    });

    // Mock /api/backend/apps list for dashboard
    await page.route("**/api/backend/apps", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });

    await page.goto("/signup");

    // Check page title and headings
    await expect(page.locator("h1")).toContainText("Create your account");

    // Test password too short validation
    await page.fill('[data-testid="signup-name"]', "Test Merchant");
    await page.fill('[data-testid="signup-company"]', "Apex Store");
    await page.fill('[data-testid="signup-email"]', "merchant@test.com");
    await page.fill('[data-testid="signup-password"]', "short");
    await page.fill('[data-testid="signup-password-confirmation"]', "short");
    await page.click('[data-testid="signup-submit"]');

    await expect(page.locator("text=Password must be at least 10 characters")).toBeVisible();

    // Now fill valid password >= 10 chars with numbers & letters
    await page.fill('[data-testid="signup-password"]', "SecurePass123!");
    await page.fill('[data-testid="signup-password-confirmation"]', "SecurePass123!");
    await page.click('[data-testid="signup-submit"]');

    // Should navigate to dashboard
    await page.waitForURL("**/dashboard", { timeout: 10000 });
    expect(page.url()).toContain("/dashboard");
  });

  test("login page renders with masked password and submits to BFF", async ({ page }) => {
    await page.route("**/api/auth/login", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        headers: {
          "Set-Cookie": "auth_token=sanctum_mock_token_12345; Path=/; HttpOnly; SameSite=Lax",
        },
        body: JSON.stringify({
          data: {
            user: { id: 1, email: "owner@mystore.com" },
          },
        }),
      });
    });

    await page.route("**/api/auth/me", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            user: { id: 1, name: "Store Owner", email: "owner@mystore.com" },
          },
        }),
      });
    });

    await page.route("**/api/backend/apps", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });

    await page.goto("/login");

    const passwordInput = page.locator('[data-testid="login-password"]');
    await expect(passwordInput).toHaveAttribute("type", "password");

    await page.fill('[data-testid="login-email"]', "owner@mystore.com");
    await page.fill('[data-testid="login-password"]', "ValidPassword123!");
    await page.click('[data-testid="login-submit"]');

    await page.waitForURL("**/dashboard", { timeout: 10000 });
    expect(page.url()).toContain("/dashboard");
  });
});
