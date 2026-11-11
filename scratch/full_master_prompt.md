<USER_REQUEST>
# TOPUPERS â€” MASTER IMPLEMENTATION PROMPT
## Currency Switcher + Payment Methods + Automatic/Manual Exchange Rate

Act as a senior PHP/MySQL developer, payment integration engineer, security engineer, and code reviewer.

I am developing an existing game top-up website called **TopUpers** using PHP, MySQL, Bootstrap, and Antigravity Pro. The project may be running locally through Laragon or XAMPP.

Your task is to inspect and carefully enhance my existing project. Do not rebuild the website from scratch or replace working features unnecessarily.

## 1. CRITICAL RULE: INSPECT BEFORE EDITING

**Phase 1 must be inspection and planning only.**

Before modifying anything:

1. Inspect the complete existing project structure.
2. Identify the PHP entry points, header/navigation, product pages, product pricing logic, cart, checkout, order creation, payment handling, login/session system, admin panel, database connection, and existing database tables.
3. Identify the existing coding conventions and Bootstrap version.
4. Determine how product prices, orders, payment methods, and admin settings currently work.
5. Identify conflicts with the requested features.
6. Check whether any currency, payment, or exchange-rate functionality already exists.
7. Identify the exact files and database tables that would need changes.
8. Present a step-by-step implementation plan, potential risks, and a list of proposed file/database changes.

**Do not edit, create, delete, rename, or overwrite any project files during Phase 1. Do not execute destructive SQL.**

Stop after presenting the plan and wait for my explicit approval.

After I approve, implement the changes incrementally, making backups before modifying existing files.

## 2. CURRENCY SWITCHER

Add a currency switcher to the existing website header.

Supported currencies:

- BDT â€” Bangladeshi Taka (à§³)
- USD â€” United States Dollar ($)

Requirements:

- BDT must be the default currency.
- Do not add country selection or IP-based country detection.
- Do not redirect users based on their location.
- The switcher must match the existing TopUpers design.
- Make it responsive for mobile and desktop.
- Preserve the selected currency using a secure PHP session.
- The selection should remain consistent when navigating between pages.
- Validate the selected currency on the server.
- Only allow supported currencies.
- If the session expires, safely default to BDT.
- Ensure currency switching does not break login, cart, checkout, or existing navigation.

Currency formatting:

- BDT: à§³190.00 or à§³190, depending on the existing price-display convention.
- USD: $1.50, with appropriate decimal precision.

Use consistent formatting throughout the product listing, product details, cart, checkout, order summary, and other relevant customer-facing pages.

Do not modify unrelated UI components.

## 3. CANONICAL PRODUCT PRICING

**BDT must remain the canonical base currency for product prices.**

Requirements:

1. Existing product prices must remain stored in BDT unless the inspection reveals a justified alternative.
2. Do not permanently convert or overwrite existing product prices when users change currency.
3. Calculate the USD display price using the active USD/BDT exchange rate.
4. Use server-side calculations for all monetary values.
5. Use a consistent rounding strategy to avoid differences between product pages, cart, and checkout.
6. Never trust a price or exchange rate submitted by the browser.
7. Recalculate the final amount on the server when an order is created.
8. Clearly show the currency and total payable amount before payment.
9. Preserve existing product IDs, supplier integrations, API logic, and order functionality.
10. Do not change the supplier's purchase cost or profit calculations unless required and explicitly documented.

If the current project uses a different pricing architecture, explain the implications before changing it.

## 4. BANGLADESH PAYMENT METHODS â€” BDT

When BDT is selected, display the payment methods configured for Bangladesh.

Initial intended methods:

- bKash
- Nagad
- Rocket
- Upay

Inspect the existing payment implementation before making changes.

Requirements:

- Display only payment methods that are enabled in the admin panel.
- Allow the admin to configure the required payment instructions and account details.
- Support manual payment verification if no official payment gateway is currently integrated.
- Show the correct order amount and order ID.
- Keep payment instructions clear and easy to follow.
- Allow the admin to enable or disable each method.
- Never claim that a payment has been automatically verified unless an actual, working verification integration exists.
- Do not invent merchant credentials or API keys.
- Do not replace an existing working gateway with a manual process without approval.

If an official gateway is later integrated, keep the architecture extensible enough to support it.

## 5. INTERNATIONAL PAYMENT â€” BINANCE PAY ID/UID OR QR

When USD is selected, prepare an international payment option using my Binance Pay ID/UID or QR code, subject to account eligibility and the available payment functionality.

Important distinction:

**A Binance Pay ID/UID or QR code is not the same thing as an official payment API. Do not assume that receiving a UID enables automatic payment verification.**

Requirements:

1. Add an admin-configurable Binance payment method.
2. Allow the admin to enter the supported payment identifier and/or upload a payment QR image.
3. Allow the admin to configure payment instructions.
4. Allow the admin to enable or disable Binance payments.
5. Keep the Binance method disabled until valid payment details have been configured and the admin has confirmed that the account can be used for the intended transactions.
6. Show the order ID, payable amount, selected currency, and payment instructions at checkout.
7. Provide a way for the customer to submit a transaction reference or payment details if required by the chosen manual workflow.
8. Store submitted payment information against the relevant order.
9. Keep the order in Pending Payment or Awaiting Verification status until an authorized administrator verifies it.
10. After verification, update the order status using the existing order-status architecture.
11. Record the verifying admin, verification timestamp, and relevant audit information.
12. Prevent customers from marking their own payments as verified.
13. Do not trust screenshots or customer-submitted transaction references as proof of payment by themselves.
14. Do not display fake success messages.
15. Do not claim that Binance Pay API, webhooks, or automatic payment verification are active unless they have actually been implemented and tested with an eligible account.

If automatic Binance payment verification requires merchant approval or API credentials, document the requirements and keep manual verification as the initial fallback.

Do not hardcode sensitive credentials into PHP files or frontend JavaScript.

## 6. EXCHANGE RATE SYSTEM â€” AUTOMATIC + MANUAL MODES

Build an exchange-rate system that supports two modes:

- Automatic Mode
- Manual Mode

The admin must be able to switch between them.

### 6.1 Automatic Mode

In Automatic Mode:

1. Retrieve the USD/BDT exchange rate from a reputable, configurable exchange-rate API.
2. Keep the API URL/provider configurable where practical.
3. Use a scheduled PHP job or cron task to refresh the exchange rate automatically.
4. Store the latest valid rate in the database.
5. Record the provider, retrieval timestamp, and fetch status.
6. Cache the rate in the database rather than calling the external API for every customer page request.
7. Do not make checkout depend on a live API request completing successfully at that exact moment.
8. Validate that the API response is successful, contains the expected currency pair, and includes a plausible positive rate.
9. Handle network failures, timeouts, malformed responses, and rate limits safely.
10. Never replace a valid rate with an invalid, empty, zero, or malformed value.
11. Log failed fetch attempts without exposing secrets.
12. Provide a configurable refresh interval if practical.
13. Show the last successful update time in the admin panel.
14. Show the active rate source and whether the cached rate is considered fresh.
15. Never invent a current exchange rate or seed a fake rate as if it were verified.

If automatic fetching fails, preserve the last valid rate for display and diagnosis. However, follow the configured stale-rate safety policy before allowing new USD orders.

### 6.2 Manual Mode

In Manual Mode:

1. Provide an admin input for the USD/BDT rate.
2. Allow the authorized admin to save and update the rate.
3. Validate the rate server-side.
4. Reject empty, zero, negative, or invalid values.
5. Display the saved manual rate and last update timestamp.
6. Keep the manual rate active until the admin changes it or switches to Automatic Mode.
7. Do not let automatic scheduled jobs overwrite the active manual rate.
8. Record which administrator updated the rate and when, where the existing authentication architecture supports it.

The admin must not need to update the rate daily while using Automatic Mode.

### 6.3 Switching Between Modes

When the admin switches from Manual Mode to Automatic Mode:

- Use the latest valid automatic rate if one is available.
- If no valid automatic rate exists, do not fabricate a rate.
- Disable new USD checkout until a valid automatic rate is available and passes the freshness policy.
- Display a clear admin warning explaining the problem.

When the admin switches from Automatic Mode to Manual Mode:

- Activate the last saved valid manual rate.
- If no valid manual rate exists, disable new USD checkout until the admin saves one.
- Do not silently fall back to an unrelated or invented rate.

The selected mode must persist in the database and survive server restarts.

### 6.4 Admin Exchange Rate Settings

Add an exchange-rate settings section to the existing admin panel.

Include:

- Active exchange-rate mode: Automatic or Manual
- Manual USD/BDT rate input
- Latest successful automatic rate
- Active rate used for new USD orders
- Current rate source/provider
- Last successful automatic update time
- Last attempted fetch time, if available
- Fetch status and error summary
- Automatic refresh interval, if supported
- Maximum allowed rate age for USD checkout
- Optional conversion buffer or markup
- Save Settings button
- Refresh Rate Now button, if safe and practical
- Clear status messages for success and failure

Restrict these controls to authorized administrators.

Do not expose API secrets or internal error traces in the public website.

### 6.5 Exchange-Rate Buffer / Markup

Provide an optional admin-configurable conversion buffer or markup.

Requirements:

- Default buffer should be 0% unless a different existing business rule is found and approved.
- The admin must be able to configure the buffer independently of the underlying exchange rate.
- Clearly distinguish the base exchange rate from the rate or conversion factor used for customer pricing.
- Validate all values server-side.
- Explain how the buffer affects the final USD price.
- Avoid applying the same markup twice.
- Do not change the underlying saved BDT product price.

### 6.6 Rate Freshness and Checkout Safety

Allow the admin to configure the maximum acceptable age of the active automatic rate.

If the last successful automatic rate exceeds that age:

- Show an appropriate warning in the admin panel.
- Do not silently represent the rate as current.
- Disable new USD checkout until a valid rate is refreshed or the admin explicitly switches to Manual Mode and saves a valid manual rate.
- Existing orders must remain accessible.
- BDT checkout must continue working if its payment method is available and there is no separate blocking issue.

Do not disable all website functionality because an exchange-rate API is unavailable.

### 6.7 Scheduled Rate Refresh

Implement a secure scheduled PHP task suitable for production hosting.

Requirements:

- Use the existing project architecture and database connection.
- Avoid running duplicate fetch jobs unnecessarily.
- Add timeouts and error handling.
- Log successful and failed attempts.
- Never overwrite the active manual rate while Manual Mode is enabled.
- Make the job safe to execute more than once.
- Protect the job against unauthorized public triggering.
- Do not expose secrets in URLs, logs, or error messages.

Document how to configure it:

- On local Windows development using Laragon/XAMPP and Windows Task Scheduler, or another appropriate local scheduling mechanism.
- On live hosting using the hosting provider's cron-job facility.

If scheduling is unavailable in the local environment, provide the exact manual command and explain that automatic refresh requires the scheduled task to be configured.

## 7. IMMUTABLE ORDER PRICE SNAPSHOTS

Exchange rates may change, but existing orders must not change.

When creating an order, store an immutable snapshot of the pricing information used for that order, adapting to the existing schema where possible.

The snapshot should include:

- Order ID
- Product ID and product name or relevant product snapshot
- Product base price in BDT
- Selected checkout currency
- Applied USD/BDT exchange rate
- Conversion buffer or markup applied
- Final payable amount
- Payment method
- Rate mode and rate source
- Order creation timestamp

Requirements:

- Save the actual values used when the order was created.
- Do not recalculate old orders using the latest exchange rate.
- Do not change an existing order's payable amount when an admin changes the rate or currency settings.
- Ensure the amount shown in the order details matches the saved order snapshot.
- Use database transactions where appropriate to keep order creation consistent.
- Preserve existing order history and avoid destructive database changes.

If the current order schema already stores some of these values, reuse the existing fields when appropriate rather than creating duplicate columns.

## 8. DATABASE AND MIGRATIONS

Inspect the current database before proposing changes.

Requirements:

- Reuse existing settings, orders, products, and payment tables where appropriate.
- Create only necessary tables or columns.
- Provide safe, reversible or carefully documented SQL migrations.
- Do not drop existing tables or reset the database.
- Preserve all existing products, users, orders, and admin accounts.
- Use prepared statements for database queries.
- Add appropriate indexes and unique constraints where needed.
- Keep monetary values in suitable DECIMAL columns, not floating-point database columns.
- Use database transactions where appropriate.
- Document backup and rollback steps.

Do not run destructive migrations without explicit approval.

## 9. SECURITY REQUIREMENTS

Apply security checks to all new and modified functionality.

- Use the existing secure authentication system.
- Enforce server-side admin authorization.
- Use prepared SQL statements.
- Protect state-changing requests with CSRF protection.
- Validate and sanitize all inputs.
- Use secure session handling consistent with the existing project.
- Prevent customers from changing order amounts or payment status.
- Prevent currency-switching requests from accepting unsupported currency codes.
- Recalculate prices and totals server-side.
- Do not trust frontend JavaScript for pricing or payment verification.
- Store secrets outside public frontend code.
- Avoid exposing database credentials, API keys, stack traces, or private logs.
- Validate uploaded QR images for permitted file types and size.
- Store uploaded files safely and prevent executable uploads.
- Escape user-controlled output to reduce XSS risks.
- Protect scheduled tasks against unauthorized access.
- Log important admin changes and payment-verification actions.
- Do not weaken existing security controls to make implementation easier.

## 10. UI/UX REQUIREMENTS

Follow the existing TopUpers design system.

The website currently aims for a premium dark navy interface with purple/blue accents.

Requirements:

- Keep the existing layout and branding.
- Make the currency switcher compact and intuitive.
- Ensure responsive behavior on mobile and desktop.
- Keep the checkout simple and clear.
- Clearly show the selected currency and final payable amount.
- Display only enabled payment methods.
- Show understandable messages when USD checkout is unavailable because of an invalid or stale exchange rate.
- Do not display raw API errors to customers.
- Avoid unnecessary homepage text or unrelated redesigns.
- Reuse existing components and styles where possible.

## 11. TESTING REQUIREMENTS

After implementation, run the tests that are practical in the current environment and report actual results.

At minimum, test:

### Currency
- BDT is the default.
- Switching to USD changes displayed prices correctly.
- Switching back to BDT restores BDT display.
- Currency selection persists across page navigation.
- Unsupported currency values are rejected.
- Cart and checkout use consistent amounts.

### Exchange Rate
- Manual rate can be saved and used.
- Invalid manual rates are rejected.
- Automatic rate can be fetched and stored.
- Failed API requests do not overwrite the last valid cached rate.
- Automatic refresh does not overwrite the active manual rate.
- Switching modes follows the specified rules.
- A stale automatic rate disables new USD checkout.
- Missing valid rates disable new USD checkout.
- BDT checkout remains available when appropriate.
- Conversion buffer is applied correctly and only once.

### Orders
- New orders save the correct currency and price snapshot.
- Existing order amounts do not change when the rate changes.
- Customers cannot alter payable amounts through browser requests.
- Order status transitions follow the existing system.

### Payments
- Only enabled methods are displayed.
- Admins can verify manual payments.
- Customers cannot verify their own payments.
- Binance payment remains disabled until correctly configured.
- No false automatic payment success is shown.

### Security and Regression
- Admin pages reject unauthorized users.
- CSRF protection works where required.
- Database operations use safe parameterized queries.
- Existing login, product listing, cart, checkout, and order history still work.
- Existing supplier/API functionality is not broken.

Do not claim a test passed unless you actually ran it and observed the result. Clearly identify any tests that could not be run.

## 12. IMPLEMENTATION PHASES

After I approve the inspection plan, proceed in these phases:

**Phase 2 â€” Backup and database preparation**
- Back up affected files and the database using an appropriate method.
- Prepare and review database migrations.
- Preserve existing data.

**Phase 3 â€” Currency and pricing**
- Implement the BDT/USD session-based switcher.
- Implement consistent server-side price conversion.
- Preserve canonical BDT prices.

**Phase 4 â€” Exchange-rate service and admin controls**
- Implement Automatic and Manual modes.
- Implement caching, validation, freshness checks, logging, and optional buffer.
- Implement the scheduled refresh task and document setup.

**Phase 5 â€” Checkout and payment methods**
- Implement the appropriate BDT payment methods.
- Implement configurable Binance Pay ID/UID or QR manual verification.
- Preserve existing gateway functionality.
- Implement immutable order price snapshots.

**Phase 6 â€” Testing and handover**
- Run relevant tests.
- Fix any regressions introduced by the changes.
- Summarize the final implementation.
- Document configuration and deployment steps.

If a phase depends on unavailable credentials, account eligibility, hosting features, or third-party approval, complete the safe independent parts and clearly explain what remains blocked. Do not fabricate credentials or claim unverified integration success.

## 13. REQUIRED FINAL REPORT

After each approved implementation phase, report:

1. What was completed.
2. Exact files created or modified.
3. Database tables or columns changed.
4. Backup and migration details.
5. Tests actually performed and their results.
6. Remaining issues or missing credentials.
7. Any required manual setup.
8. The next recommended step.

## FINAL INSTRUCTION

**Start with Phase 1 only. Inspect the existing TopUpers project and present your findings and implementation plan. Do not modify any files until I explicitly approve the plan.**

Use the existing project wherever possible. Preserve working functionality, avoid unnecessary rewrites, prioritize security, and never claim an integration or test is complete without evidence.
</USER_REQUEST>
<ADDITIONAL_METADATA>
The current local time is: 2026-10-11T22:29:45+06:00.
</ADDITIONAL_METADATA>
<USER_SETTINGS_CHANGE>
The user changed setting `Model Selection` from None to Gemini 3.8 Flash (High). No need to comment on this change if the user doesn't ask about it. If reporting what model you are, please use a human readable name instead of the exact string.
</USER_SETTINGS_CHANGE>