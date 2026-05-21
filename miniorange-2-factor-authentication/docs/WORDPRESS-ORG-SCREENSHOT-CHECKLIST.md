# WordPress.org listing: screenshot checklist (Day 5)

Use this when updating assets in the plugin SVN `assets/` folder or readme **Screenshots** section.

## Recommended captures (order = story)

1. **Plugin dashboard / header** — Show the **free-plan seat meter** (“X / 5 used”) and primary navigation so users see transparency immediately.
2. **Method selection** — “Setup 2FA for you” grid with authenticator / email / SMS thumbnails (method selection step).
3. **Success / configured state** — User has a method selected; optional “Test” or backup codes CTA visible.
4. **Limit reached context** — Either the **warning** on setup screen when seats are full, or the **Users’ 2FA Status** report with the premium note (shows honesty + upgrade path).
5. **Users’ 2FA Status** — Registered vs unregistered users table (admins understand who counts toward seats).

## Technical notes

- **Size:** Follow [WordPress.org plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/) (e.g. 772×250 banner if used; screenshots typically 1200px wide or as per current directory practice).
- **No misleading UI:** Screenshots should match the **free** build (seat limit, labels) shipped in this repo.
- **Alt text:** In `readme.txt`, screenshot captions should describe what the image proves (e.g. “Seat meter and upgrade link in admin header”).

## Optional: deactivation insights (local)

The plugin stores **aggregated counts** in the site option `mo2f_deactivate_feedback_counts` when an admin deactivates via the feedback modal (submit or skip). Keys are prefixed with `r_` for submitted reasons (slug of the label) or `skipped_no_feedback` for skip. This is **not** sent to WordPress.org; use internally or via WP-CLI / support tooling.

---

## Sprint retrospective (Day 5) — 30 minutes

**What shipped (Days 1–5)**  
- Honest readme/FAQ + **5-user** free cap (`MO2F_FREE_PLAN_USER_LIMIT`).  
- Seat meter in header; limit UX + links; activation nudge; deactivation reason polish + local stats.

**What slipped / follow-ups**  
- Passkeys / WebAuthn teaser; unlimited TOTP on free (strategy decision); multisite; SMS gateway work.

**Next bet (pick one for next sprint)**  
- Passkey teaser for admins **or** raise seat cap again **or** unlimited TOTP with premium on policy/enforcement — measure support tickets and `mo2f_deactivate_feedback_counts` before deciding.
