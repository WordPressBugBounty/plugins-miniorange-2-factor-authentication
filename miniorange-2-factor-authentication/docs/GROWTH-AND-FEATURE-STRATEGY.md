# miniOrange 2FA (Free): Growth, Features, and Profit Strategy

This document outlines how to grow WordPress.org installations, improve user acquisition and retention, and convert free users to paid plans—without turning the free product into a hollow trial.

---

## 1. Goals (what “success” means)

| Goal | Typical signal |
|------|----------------|
| **More installs** | Active installs trend, search ranking on wordpress.org, referral traffic |
| **More active usage** | % of installs that complete 2FA setup for ≥1 user |
| **More revenue** | Premium upgrades, SMS/transaction revenue, support contracts |
| **Lower cost** | Fewer confused-support tickets, fewer abandoned setups |

Install count alone does not equal profit. The funnel that matters: **Discover → Install → Activate → Complete setup → Keep enabled → Upgrade when limits hit.**

---

## 2. Current free tier (baseline)

- **User cap:** Up to **5 users** can complete 2FA configuration (enforced via `MoWpnsConstants::MO2F_FREE_PLAN_USER_LIMIT` and described in readme).
- **Strengths:** Multiple methods (TOTP, email, SMS, Telegram, KBA, etc.), WooCommerce/custom forms narrative, wizard, reports-related story.
- **Risk:** Competitors often offer **unlimited TOTP for all users** on free; a low **user cap** can still cause uninstalls when a site outgrows it—monitor support and deactivate reasons.

Use this doc to decide **what to widen in free** (for growth) vs **what stays premium** (for margin).

---

## 3. How to increase installations (wordpress.org and beyond)

### 3.1 Plugin directory SEO and first impression

- **Short description:** Lead with outcomes (“Brute-force protection”, “WooCommerce login”) and **one honest limit** (“Free: 2FA for up to 5 users—upgrade for unlimited”) so users don’t feel tricked.
- **Tags:** Cover synonyms: `mfa`, `login security`, `woocommerce 2fa`, `totp`, `authenticator`.
- **Screenshots:** Show the **happy path** in 5–6 images: install → choose method → success → user list with **seat usage** (e.g. “3 / 5”).
- **Video:** Short demo (under 90 seconds) beats a long tutorial for scroll depth.

### 3.2 Trust and compliance

- **Privacy:** Clear data flow (what leaves the site for SMS/email cloud vs local TOTP).
- **Compatibility:** Keep “Tested up to” current; align **Requires PHP** with real minimum (very old PHP in header hurts trust and security narrative).

### 3.3 Reduce silent churn

Many plugins are installed once and never configured. **Push completion, not upsell, in the first session:**

- Admin notice: “Finish securing your site—2FA not enabled for any user yet” with one primary button.
- Optional email to site admin (once) after 48h if no user configured.

---

## 4. Product improvements that drive retention and upgrades

### 4.1 Fix contradictory messaging

If marketing or FAQ says users can “enforce 2FA for everyone” without stating the **free user cap**, **fix the copy**. Misleading text increases **anger-uninstalls** and support load; honest limits often **increase** upgrade clicks.

### 4.2 Make the limit visible and fair-feeling

- Dashboard widget: **“2FA seats used: X / 5”** (or current free limit) with link to upgrade and to **which users** count.
- When seat limit blocks a user, show **one clear screen**: why, who is using seats, and **single CTA** to premium (no dead ends).

### 4.3 Onboarding that matches real WordPress sites

- **Presets:** “Secure administrators only (recommended)” vs “Custom roles.”
- **First-run checklist:** Administrator has 2FA → backup codes saved → test login in incognito.

### 4.4 Methods: free vs paid (strategic split)

| Direction | Growth play | Profit play |
|-----------|-------------|-------------|
| **TOTP / authenticator apps** | Strong **unlimited** or high cap on free—cheap to deliver, high satisfaction | Premium: **policies**, **forced enrollment**, **grace periods**, reporting |
| **Email OTP / magic link** | Limited free (rate limits, or included in seat count) | Premium: branding, templates, advanced rules |
| **SMS / WhatsApp** | Keep **metered** or premium; state pricing upfront | Transactions and bundles |
| **Passkeys / WebAuthn** | **Teaser** on free (e.g. admins only) drives headlines and installs | Full rollout, device management, audit = premium |

### 4.5 “Sticky” free features (keep users from switching plugins)

- Export/import **2FA enrollment state** (where safe) for migrations.
- **Admin emergency access** workflow documented and obvious (reduce lockout fear).
- **Compatibility** with popular login/customizer plugins—documented and tested.

---

## 5. New features worth considering (prioritized themes)

**A. Growth (more installs & completions)**  
- Passkeys/WebAuthn (even partial).  
- Clear seat meter + better first-run wizard.  
- Optional security score / checklist (2FA + basic hardening tips).  

**B. Conversion (free → paid)**  
- Role-based **enforcement** and **grace period** as premium highlights.  
- **Unlimited users** or higher seat tiers.  
- Trusted devices, multisite, white-label, custom SMS gateway.  

**C. Profit without harming growth**  
- Usage-based SMS with **transparent balance** in dashboard.  
- “Teams” or “agency” SKUs for many sites.  
- Priority support as paid add-on.  

---

## 6. Should the free tier give “more”?

**Yes, selectively.**

- **Increasing seats** (e.g. 3 → 5 or “all Administrator-role users up to N”) can **reduce uninstalls** on small business sites—the largest WordPress segment. **(Free limit raised to 5 in v6.2.5.)**
- **Unlimited TOTP-only** for all users is a strong competitive move; offset by gating **enforcement**, **SMS**, **trusted devices**, **reporting**, and **multisite**—features enterprises pay for.

Every free expansion should answer: **Does this increase completed setups and trust, and do we still have a sharp premium story?**

---

## 7. Metrics to track internally

- Time-to-first configured user after install.  
- % installs with ≥1 user configured within 7 days.  
- Uninstall correlation: **after hitting seat limit** vs other reasons (survey on deactivate).  
- Premium conversion from: seat-limit screen, SMS exhaustion, multisite attempt.  
- Support ticket categories (lockout, WooCommerce, limit confusion).  

---

## 8. Suggested 90-day focus (example roadmap)

1. **Weeks 1–2:** Copy audit (readme, FAQ, in-plugin strings)—align all claims with the **free user limit** in code (`MO2F_FREE_PLAN_USER_LIMIT`) or change both together. **Partially done:** limit raised to 5 + readme/FAQ updated (v6.2.5).  
2. **Weeks 3–4:** Seat meter + limit-reached UX + deactivate survey.  
3. **Weeks 5–8:** Onboarding preset for “Admins” + completion nudges.  
4. **Weeks 9–12:** One **headline** feature (passkeys teaser **or** seat increase **or** unlimited TOTP policy—pick one to avoid scope creep).  

---

## 9. Five-day implementation plan (sprint toward success)

This is a **sequenced sprint**: each day has a **shippable outcome**. Adjust owners (PM, Dev, Design, Marketing) to your team size; if one person, stack Days 1–2 and 4–5 as copy-only vs code-heavy days.

### Day 1 — Trust and honest positioning (low code)

| Focus | Deliverables |
|--------|----------------|
| **Copy audit** | Inventory every place the **free user** limit appears or is implied: `readme.txt`, FAQ block, plugin settings screens, upgrade modals, support macros. |
| **Fix contradictions** | Update FAQ/marketing strings so “enforce for all users” explicitly says **free = limited configured users; premium = unlimited** (aligned with `MO2F_FREE_PLAN_USER_LIMIT`). **Done in v6.2.5 readme/FAQ.** |
| **readme.txt** | Refresh short description with outcome + honest limit; add tags (`mfa`, `login security`, `totp`) if wordpress.org allows; bump **Tested up to** if validated. |
| **Acceptance** | No remaining text that promises unlimited free 2FA for all users. |

**Success signal:** Fewer “I thought it was free for everyone” tickets within two weeks.

---

### Day 2 — Seat visibility (product + dev)

| Focus | Deliverables |
|--------|----------------|
| **Seat counter** | Reuse existing logic (e.g. count of users with 2FA user-detail meta) to compute **used / limit** on free (`MO2F_FREE_PLAN_USER_LIMIT`). **Shipped:** `Mo2fDB::mo2f_get_configured_2fa_user_count()`. |
| **UI surface** | Show **“2FA seats: X / N”** on the main plugin dashboard (or setup wizard footer) with link to **Users → 2FA status** (or your existing report screen). **Shipped:** banner in `views/navbar.php` for admins when not `mo2f_is_lv_needed`. |
| **Upgrade path** | Next to the meter, one link: **Upgrade for unlimited users** (pricing page). **Shipped:** pricing URL with `?ref=free_plan_seats`. |
| **Acceptance** | A new installer can see seat usage without opening the database or support docs. |

**Success signal:** Users discover the limit *before* they hit the wall.

---

### Day 3 — Limit-reached experience (dev + UX)

| Focus | Deliverables |
|--------|----------------|
| **Block screen** | When `check_alluser_limit_exceeded` (or equivalent) is true for a **new** user, show a single clear message: what a “seat” is, who is using seats, and **upgrade CTA**. **Shipped:** `MoWpnsMessages::mo2f_user_limit_exceeded_message_html()` + plain `mo2f_user_limit_exceeded_plain_message()` for AJAX. |
| **No dead ends** | Link to user list / 2FA status so admins can **free a seat** (reset/remove user 2FA) if that’s supported—reduces frustration and uninstalls. **Shipped:** links to `mo_2fa_reports&subpage=users2fastatus` in HTML + plain messages. |
| **Analytics (optional)** | One internal event or query param on upgrade link (`?ref=seat_limit`) for later funnel analysis. **Shipped:** `?ref=seat_limit` on limit-reached upgrade links (header meter uses `free_plan_seats`). |
| **Acceptance** | Support can answer “why can’t I add user 4?” with one help article that matches the on-screen text. |

**Success signal:** Seat-limit moment becomes a **conversion point**, not a mystery error.

---

### Day 4 — Activation and first-run nudge (dev)

| Focus | Deliverables |
|--------|----------------|
| **Admin notice** | Dismissible notice for `manage_options`: if **zero** users have completed 2FA setup, show **“Secure your login—finish 2FA setup”** + button to wizard/settings. **Shipped** in `mo2f_notices()`. |
| **Frequency** | Show again after 7 days if still zero (or only once per site option—pick one rule and document it). **Shipped:** 7-day cooldown via `mo2f_first_setup_nudge_dismissed_at`. |
| **Optional** | Track `mo2f_first_setup_prompt_dismissed` site option to avoid nagging power users. **Implemented equivalent:** `mo2f_first_setup_nudge_dismissed_at`. |
| **Acceptance** | Fresh install gets a clear next step within the first admin session. |

**Success signal:** Higher **% configured within 7 days** (measure in analytics or support volume).

---

### Day 5 — Distribution polish and learnings (marketing + light dev)

| Focus | Deliverables |
|--------|----------------|
| **wordpress.org assets** | Screenshot checklist: (1) dashboard with **seat meter**, (2) method selection, (3) success state, (4) limit-reached screen with CTA, (5) user 2FA status table. Replace or add images per directory rules. **Shipped:** `docs/WORDPRESS-ORG-SCREENSHOT-CHECKLIST.md`. |
| **Deactivate feedback (optional)** | Simple 1-question survey: reason = too complex / hit user limit / found alternative / other—store anonymized or use a form link. **Shipped:** new reasons in `views/feedback-form.php`; **User Limit** when configured users ≥ `MO2F_FREE_PLAN_USER_LIMIT`; local aggregates in `mo2f_deactivate_feedback_counts` (see `FeedbackHandler::mo2f_record_deactivate_feedback_local_stat`). |
| **Internal retro** | 30 minutes: what shipped, what slipped, next bet (passkey teaser vs seat increase vs unlimited TOTP). **Shipped:** prompts in `docs/WORDPRESS-ORG-SCREENSHOT-CHECKLIST.md`. |
| **Acceptance** | Plugin directory page reflects new screenshots or copy within submission review time. |

**Success signal:** Listing matches in-product truth; you have **baseline** deactivate reasons.

---

### Five-day sprint summary

| Day | Theme | Primary outcome |
|-----|--------|------------------|
| 1 | Honest messaging | No misleading “unlimited free” copy |
| 2 | Transparency | Seats **X / N** visible in admin |
| 3 | Conversion UX | Clear limit screen + upgrade + manage seats |
| 4 | Activation | Nudge until first user completes 2FA |
| 5 | Growth surface | Screenshots + optional deactivate insight |

**Explicitly out of scope for this 5-day sprint (schedule next):** Passkeys/WebAuthn, further free-tier seat changes, multisite work, new SMS gateways—track impact from Days 1–5 (including `mo2f_deactivate_feedback_counts`) before the next sprint.

---

## 10. Summary

- **Install growth** comes from **search visibility**, **trust**, and **fast first success**.  
- **Profit** comes from **clear limits**, **strong premium differentiation**, and **metered high-cost channels** (SMS).  
- The **free user cap** (currently **5**, constant `MO2F_FREE_PLAN_USER_LIMIT`) should always match readme and UI; **unbundling unlimited TOTP** remains a strong competitive lever if needed—provided **enforcement, scale, and enterprise features** stay compelling in premium.

---

*Internal strategy document for miniOrange 2FA (Free). Update when pricing, limits, or feature gates change.*
