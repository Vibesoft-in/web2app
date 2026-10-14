import { test, expect } from "@playwright/test";

const TEST_ULID = "01JTESTAPP1234567890123456";

test.describe("Onboarding Wizard & Preview", () => {
  test.beforeEach(async ({ page }) => {
    // Mock user authentication session
    await page.route("**/api/auth/me", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            user: { id: 1, name: "Merchant One", email: "merchant@test.com" },
            tenant: { id: "01JTESTTENANT", name: "Apex Stores" },
          },
        }),
      });
    });

    // Mock themes list
    await page.route("**/api/backend/themes", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: [
            {
              id: 1,
              name: "Modern Minimalist",
              key: "theme_a",
              defaults: {
                colors: { primary: "#6366F1", background: "#FFFFFF", surface: "#F9FAFB" },
                font: "Inter",
                radius: 12,
              },
            },
            {
              id: 2,
              name: "Vibrant Lifestyle",
              key: "theme_b",
              defaults: {
                colors: { primary: "#EC4899", background: "#FFFFFF", surface: "#FFF1F2" },
                font: "Poppins",
                radius: 16,
              },
            },
            {
              id: 3,
              name: "Dark Luxury",
              key: "theme_c",
              defaults: {
                colors: { primary: "#F59E0B", background: "#0F172A", surface: "#1E293B" },
                font: "Outfit",
                radius: 8,
              },
            },
          ],
        }),
      });
    });
  });

  test("connect step: validates URL, verifies connection, and displays diagnostics or clear error codes", async ({ page }) => {
    // Setup state where step 2 (connect) is active
    await page.route(`**/api/backend/apps/${TEST_ULID}/setup-state`, async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            app: { id: 1, ulid: TEST_ULID, name: "Glow Beauty", platform: "woo" },
            current_step: "connect",
            completed_steps: ["platform"],
            unlocked_steps: ["platform", "connect"],
            connection: null,
            build_allowed: false,
          },
        }),
      });
    });

    let verifyShouldFail = true;

    // Mock verify endpoint
    await page.route(`**/api/backend/apps/${TEST_ULID}/connection/verify`, async (route) => {
      if (verifyShouldFail) {
        return route.fulfill({
          status: 422,
          contentType: "application/json",
          body: JSON.stringify({
            error: {
              code: "INVALID_KEYS",
              message: "The Consumer Secret provided is invalid or unauthorized for WooCommerce REST API.",
            },
          }),
        });
      } else {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            data: {
              status: "verified",
              diagnostics: {
                store_name: "Glow Cosmetics Official",
                products_count: 54,
                currency: "INR",
                wc_version: "8.5.1",
              },
            },
          }),
        });
      }
    });

    await page.goto(`/dashboard/apps/${TEST_ULID}/setup/connect`);

    // Verify inputs exist and password is of type password (never plain text)
    const secretInput = page.locator('[data-testid="connect-consumer-secret"]');
    await expect(secretInput).toHaveAttribute("type", "password");

    // Scenario A: Test failed verification with explicit error code
    await page.fill('[data-testid="connect-site-url"]', "https://myglowstore.com");
    await page.fill('[data-testid="connect-consumer-key"]', "ck_badkey123");
    await page.fill('[data-testid="connect-consumer-secret"]', "cs_badsecret456");
    await page.click('[data-testid="verify-btn"]');

    const errorBox = page.locator('[data-testid="verify-error"]');
    await expect(errorBox).toBeVisible();
    await expect(errorBox).toContainText("INVALID_KEYS");

    // Scenario B: Test successful verification with real store diagnostics
    verifyShouldFail = false;
    await page.fill('[data-testid="connect-consumer-key"]', "ck_valid_123456789");
    await page.fill('[data-testid="connect-consumer-secret"]', "cs_valid_987654321");
    await page.click('[data-testid="verify-btn"]');

    const successBox = page.locator('[data-testid="verify-success"]');
    await expect(successBox).toBeVisible();
    await expect(successBox).toContainText("Connection Verified Successfully");
    await expect(successBox).toContainText("Glow Cosmetics Official");
    await expect(successBox).toContainText("54");
    await expect(successBox).toContainText("INR");
  });

  test("step-skip prevention: directly navigating to locked step redirects to current allowed step", async ({ page }) => {
    // Current allowed step is 'connect', locked steps: 'build', 'preview', etc.
    await page.route(`**/api/backend/apps/${TEST_ULID}/setup-state`, async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            app: { id: 1, ulid: TEST_ULID, name: "Glow Beauty", platform: "woo" },
            current_step: "connect",
            completed_steps: ["platform"],
            unlocked_steps: ["platform", "connect"],
            connection: null,
            build_allowed: false,
          },
        }),
      });
    });

    // Attempt direct URL jump to step 10: 'build'
    await page.goto(`/dashboard/apps/${TEST_ULID}/setup/build`);

    // Guard effect should immediately redirect back to current_step (connect)
    await page.waitForURL(`**/dashboard/apps/${TEST_ULID}/setup/connect`, { timeout: 10000 });
    expect(page.url()).toContain(`/dashboard/apps/${TEST_ULID}/setup/connect`);

    // Attempt direct URL jump to step 7: 'preview'
    await page.goto(`/dashboard/apps/${TEST_ULID}/setup/preview`);
    await page.waitForURL(`**/dashboard/apps/${TEST_ULID}/setup/connect`, { timeout: 10000 });
    expect(page.url()).toContain(`/dashboard/apps/${TEST_ULID}/setup/connect`);
  });

  test("live preview simulator: updates in real-time with 400ms debounce from draft branding config", async ({ page }) => {
    // Setup state where 'branding' and 'preview' are unlocked
    await page.route(`**/api/backend/apps/${TEST_ULID}/setup-state`, async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            app: { id: 1, ulid: TEST_ULID, name: "Glow Beauty", platform: "woo" },
            current_step: "branding",
            completed_steps: ["platform", "connect", "analysis", "features", "theme"],
            unlocked_steps: ["platform", "connect", "analysis", "features", "theme", "branding", "preview"],
            connection: { status: "verified" },
            build_allowed: false,
          },
        }),
      });
    });

    // Mock preview data with realistic catalog products
    await page.route(`**/api/backend/apps/${TEST_ULID}/preview-data`, async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            products: [
              {
                id: "woo-101",
                title: "Silk Velvet Lip Gloss",
                price: 799,
                sale_price: 599,
                images: ["https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=500&q=80"],
              },
              {
                id: "woo-102",
                title: "Hydrating Facial Mist",
                price: 1299,
                sale_price: null,
                images: ["https://images.unsplash.com/photo-1556228720-195a672e8a03?w=500&q=80"],
              },
            ],
            categories: [
              { id: 1, name: "Skincare", count: 12 },
              { id: 2, name: "Cosmetics", count: 8 },
            ],
          },
        }),
      });
    });

    let draftPutReceived = false;
    // Mock draft config PUT endpoint
    await page.route(`**/api/backend/apps/${TEST_ULID}/config/draft`, async (route) => {
      draftPutReceived = true;
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            version: 2,
            status: "draft",
          },
        }),
      });
    });

    await page.goto(`/dashboard/apps/${TEST_ULID}/setup/branding`);

    // Verify phone frame preview renders
    const phoneFrame = page.locator('[data-testid="phone-frame"]');
    await expect(phoneFrame).toBeVisible();

    const phoneAppName = page.locator('[data-testid="phone-app-name"]');
    await expect(phoneAppName).toContainText("Glow Beauty");

    // Verify real store products rendered inside simulator
    await expect(page.locator("text=Silk Velvet Lip Gloss")).toBeVisible();
    await expect(page.locator("text=Hydrating Facial Mist")).toBeVisible();

    // Change the brand name in the branding form
    const brandInput = page.locator('[data-testid="branding-name-input"]');
    await brandInput.fill("Velvet Aura");

    // Phone preview should update reactively
    await expect(phoneAppName).toContainText("Velvet Aura");

    // Wait for the 400ms debounce timer to fire the draft mutation
    await page.waitForTimeout(600);
    expect(draftPutReceived).toBe(true);
  });
});
