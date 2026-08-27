# Clinic Queue — Style Guideline

The single source of truth for the visual design of the Clinic Queue Management System. It defines the brand, color, typography, spacing, layout, and component specifications every screen must follow.

> **Governing rule.** Every UI component (Blade or Livewire) MUST conform to the tokens and specs in this document, and MUST be registered in the [Component Catalog](#11-component-catalog) in the same change that introduces or modifies it. Do not introduce ad-hoc colors, fonts, radii, or shadows outside the documented tokens. A component is not "done" until it is documented here.

---

## Table of contents

1. [How to use this guide](#1-how-to-use-this-guide)
2. [Brand](#2-brand)
3. [Color palette](#3-color-palette)
4. [Typography](#4-typography)
5. [Spacing, radius & elevation](#5-spacing-radius--elevation)
6. [Layout](#6-layout)
7. [Component specs](#7-component-specs)
8. [Accessibility](#8-accessibility)
9. [Implementation status](#9-implementation-status)
10. [Contribution checklist](#10-contribution-checklist)
11. [Component Catalog](#11-component-catalog)

---

## 1. How to use this guide

- **Design with tokens, not raw values.** Reference the semantic token names below (e.g. `primary`, `ink`, `surface`) rather than hard-coding hex values or one-off arbitrary Tailwind classes like `text-[#1b1b18]`.
- **Tokens map to Tailwind v4 `@theme` variables.** This project uses Tailwind v4, which is configured in `resources/css/app.css` via an `@theme { … }` block (there is no `tailwind.config.js`). Each token lists the `@theme` variable name it should be implemented as, so a Tailwind class like `bg-primary` or `text-ink` resolves correctly once the tokens are wired up.
- **Status of implementation.** See [§9](#9-implementation-status). The tokens and font setup below are the **target spec**; code snippets marked _"target — not yet applied"_ describe what to add to the codebase when styling work begins. They are intentionally not applied yet.
- **Adding a component?** Follow the [Contribution checklist](#10-contribution-checklist) and add a row to the [Component Catalog](#11-component-catalog).

---

## 2. Brand

The product is a calm, clinical, trustworthy healthcare tool. The interface should feel **light, spacious, and legible** — receptionists and providers scan it under time pressure.

Tone principles:

- **Calm over loud.** Neutral light-gray canvas, generous whitespace, a single green accent. Color is used sparingly and always meaningfully (primary action, positive status, the one "hero" metric).
- **Legible over dense.** Clear type hierarchy; numbers (queue counts, wait times) are the visual focus of the dashboard.
- **Consistent over clever.** Reuse the documented components; do not invent new visual patterns for the same job.

Logo: wordmark set in the brand font, weight 600, with the "mark" glyph in `primary` green. Keep clear space around it equal to the cap-height. Do not recolor the wordmark outside `ink` or `surface` (on dark backgrounds).

---

## 3. Color palette

Derived from the reference designs. Core brand colors are `#34AA86` (green), `#FFFFFF` (white), `#303030` (near-black).

### Core

| Token | Hex | Tailwind `@theme` var | Usage |
|-------|-----|-----------------------|-------|
| `primary` | `#34AA86` | `--color-primary` | Primary actions, active nav item, hero metric card, positive emphasis, logo mark |
| `primary-hover` | `#2E9576` | `--color-primary-hover` | Hover/active state of primary buttons |
| `primary-tint` | `#EAF6F1` | `--color-primary-tint` | Subtle green backgrounds (success badge fill, icon chips) |
| `surface` | `#FFFFFF` | `--color-surface` | Card / panel background |
| `background` | `#F4F5F5` | `--color-background` | App page canvas behind cards |
| `ink` | `#303030` | `--color-ink` | Primary text, headings, near-black UI |

### Neutral / supporting

| Token | Hex | Tailwind `@theme` var | Usage |
|-------|-----|-----------------------|-------|
| `muted` | `#6B7280` | `--color-muted` | Secondary text, captions, table labels |
| `muted-soft` | `#9CA3AF` | `--color-muted-soft` | Placeholder text, disabled labels |
| `border` | `#E5E7EB` | `--color-border` | Card borders, dividers, table row separators |
| `hover-surface` | `#F9FAFB` | `--color-hover-surface` | Row/nav hover background |

### Semantic (status)

Each status uses a **text/icon color** on a matching **tint fill** for pill badges.

| Token | Text hex | Tint hex | Tailwind `@theme` vars | Usage |
|-------|----------|----------|------------------------|-------|
| `success` | `#1E9E6A` | `#E7F6EF` | `--color-success` / `--color-success-tint` | Confirmed, Available, positive delta (`+12 from yesterday`) |
| `warning` | `#B78103` | `#FDF4E3` | `--color-warning` / `--color-warning-tint` | Pending, In Session |
| `danger` | `#DC2626` | `#FDECEC` | `--color-danger` / `--color-danger-tint` | Cancelled, Off, negative delta |
| `info` | `#2563EB` | `#EAF1FE` | `--color-info` / `--color-info-tint` | Neutral informational badges |

> **Never rely on color alone** to communicate status — always pair the tint with a text label and, where used, an icon (see [§8](#8-accessibility)).

### Target implementation — _not yet applied_

When styling work begins, add to `resources/css/app.css` inside the `@theme` block:

```css
/* target — not yet applied */
@theme {
    --color-primary: #34AA86;
    --color-primary-hover: #2E9576;
    --color-primary-tint: #EAF6F1;
    --color-surface: #FFFFFF;
    --color-background: #F4F5F5;
    --color-ink: #303030;

    --color-muted: #6B7280;
    --color-muted-soft: #9CA3AF;
    --color-border: #E5E7EB;
    --color-hover-surface: #F9FAFB;

    --color-success: #1E9E6A;
    --color-success-tint: #E7F6EF;
    --color-warning: #B78103;
    --color-warning-tint: #FDF4E3;
    --color-danger: #DC2626;
    --color-danger-tint: #FDECEC;
    --color-info: #2563EB;
    --color-info-tint: #EAF1FE;
}
```

---

## 4. Typography

Primary typeface: **Switzer** — a clean geometric sans that matches the reference brand. Self-hosted (see spec below).

Fallback stack: `Switzer, ui-sans-serif, system-ui, sans-serif`.

### Weights

| Weight | Value | Usage |
|--------|-------|-------|
| Regular | 400 | Body copy, table cells |
| Medium | 500 | Labels, nav items, table headers |
| Semibold | 600 | Card titles, buttons, section headings |
| Bold | 700 | Page title ("Hello, Sarah!"), large metric numbers |

### Type scale

| Role | Size / line-height | Weight | Color | Example |
|------|--------------------|--------|-------|---------|
| Page title | `text-2xl` / `leading-tight` (24px) | 700 | `ink` | "Hello, Sarah!" |
| Section / card title | `text-base` (16px) | 600 | `ink` | "Patient Flow Trends" |
| Metric number | `text-3xl`–`text-4xl` (30–36px) | 700 | `ink` (or `surface` on hero card) | "54" |
| Body | `text-sm` (14px) | 400 | `ink` | Table cells, descriptions |
| Label / caption | `text-xs` (12px) | 500 | `muted` | "Checked-in Patients", column headers |
| Delta caption | `text-xs` (12px) | 500 | `success` / `danger` | "+12 from yesterday" |

### Self-hosting spec — _not yet applied_

1. Place the Switzer web fonts under `resources/fonts/switzer/` (e.g. `Switzer-Regular.woff2`, `-Medium`, `-Semibold`, `-Bold`). Obtain them from the licensed Switzer distribution (Fontshare).
2. Add `@font-face` declarations and set the sans token in `resources/css/app.css`:

```css
/* target — not yet applied */
@font-face {
    font-family: 'Switzer';
    src: url('/fonts/switzer/Switzer-Regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Switzer';
    src: url('/fonts/switzer/Switzer-Medium.woff2') format('woff2');
    font-weight: 500;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Switzer';
    src: url('/fonts/switzer/Switzer-Semibold.woff2') format('woff2');
    font-weight: 600;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Switzer';
    src: url('/fonts/switzer/Switzer-Bold.woff2') format('woff2');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}

@theme {
    --font-sans: 'Switzer', ui-sans-serif, system-ui, sans-serif;
}
```

3. Ensure the font files are served from `public/` (e.g. copy/symlink to `public/fonts/switzer/` or reference via Vite `@source`/asset pipeline) so the `/fonts/...` URLs resolve. Remove the current Bunny `Instrument Sans` loader in `vite.config.js` once Switzer is in place.

---

## 5. Spacing, radius & elevation

### Spacing rhythm

Use Tailwind's 4px scale. Standard rhythm:

- **Card inner padding:** `p-5` (20px), `p-6` (24px) on large panels.
- **Gap between cards / grid gutter:** `gap-4` (16px) to `gap-6` (24px).
- **Section vertical spacing:** `space-y-6`.
- **Inline element gaps** (icon + label, avatar + name): `gap-2` / `gap-3`.

### Radius

| Element | Radius |
|---------|--------|
| Cards / panels | `rounded-2xl` (16px) |
| Buttons, inputs, filter pills | `rounded-lg` (8px) |
| Status badges | `rounded-full` |
| Avatars | `rounded-full` |

### Elevation (shadows)

Cards use one soft, low shadow — never harsh. Standard card:

```
shadow-[0_1px_2px_0_rgba(16,24,40,0.04),0_1px_3px_0_rgba(16,24,40,0.06)]
```

Combined with a `1px` `border` (`--color-border`). The green hero card omits the border and may use `shadow-[0_8px_24px_-8px_rgba(52,170,134,0.45)]` for a subtle colored lift. Avoid more than one elevation level per surface.

---

## 6. Layout

The app uses a **fixed-sidebar dashboard shell**:

```
┌────────────┬──────────────────────────────────────────────┐
│            │  Top bar: search · notifications · user       │
│  Sidebar   ├──────────────────────────────────────────────┤
│            │  Page title + subtitle          [date][actions]│
│  Basics    │                                                │
│   · Nav    │  ┌ hero ─┐ ┌ metric ┐ ┌ metric ┐   metric row │
│   · Nav    │  └───────┘ └────────┘ └────────┘               │
│            │  ┌ chart panel ─────────┐ ┌ list panel ─┐      │
│  Others    │  └──────────────────────┘ └─────────────┘      │
│   · Nav    │  ┌ data table panel ───────────────────────┐   │
│            │  └──────────────────────────────────────────┘  │
└────────────┴──────────────────────────────────────────────┘
```

- **Sidebar:** fixed width (`w-60`), `surface` background, grouped nav sections with a `muted` uppercase group label ("Basics", "Others"). Active item uses `primary-tint` background + `primary` text/icon. Logo pinned top.
- **Top bar:** full-width search input (left), notification/mail icons and user avatar+name (right).
- **Page header:** bold page title + `muted` subtitle on the left; date-range / period / quick-action pills on the right.
- **Metric row:** three equal cards; the **first is the green hero** (`primary` fill, white text), the rest are default `surface` cards.
- **Content grid:** two columns — a wider left panel (chart) and a narrower right panel (list). Collapses to one column below `lg`.
- **Data table panel:** full-width card containing the table.
- Content max width is constrained; grid is responsive (single column on small screens).

---

## 7. Component specs

Each spec lists purpose, anatomy, the Tailwind classes to use (via tokens), and states. These are the reference implementations; actual Blade/Livewire components are tracked in the [Component Catalog](#11-component-catalog).

### 7.1 Button

- **Purpose:** Trigger an action ("See Details", "Quick Actions", "Call Next Patient").
- **Variants:**
  - **Primary** — `bg-primary text-surface font-semibold rounded-lg px-4 py-2 hover:bg-primary-hover`
  - **Secondary** — `bg-surface text-ink border border-border rounded-lg px-4 py-2 hover:bg-hover-surface`
  - **Ghost** — `text-muted hover:text-ink hover:bg-hover-surface rounded-lg px-3 py-2`
- **States:** default, hover, focus (`focus-visible:ring-2 focus-visible:ring-primary/40`), disabled (`opacity-50 cursor-not-allowed`).
- **Sizes:** `sm` (`text-xs px-3 py-1.5`), `md` (default). Icon + label use `inline-flex items-center gap-2`.

### 7.2 Card / Panel

- **Purpose:** Group related content on a surface.
- **Anatomy:** container → optional header (icon chip + title + optional action) → body.
- **Classes:** `bg-surface border border-border rounded-2xl p-6` + standard card shadow (see [§5](#5-spacing-radius--elevation)).
- **Header:** title `text-base font-semibold text-ink`; leading icon in a `primary-tint` rounded chip.

### 7.3 Stat / Metric card

- **Purpose:** Headline a single KPI (Checked-in Patients, Upcoming Appointments, On-site Now; and app metrics: waiting, in-service, completed, avg wait).
- **Anatomy:** label + icon (top), big number (middle), delta caption (bottom).
- **Default variant:** Card base + number `text-3xl font-bold text-ink`, label `text-xs font-medium text-muted`, delta in `success`/`danger`.
- **Hero variant:** `bg-primary text-surface` (no border); label and delta use `text-surface/80`; optional faint decorative line/graphic. Use for the **first / most important** metric only — never more than one hero per row.

### 7.4 Status badge

- **Purpose:** Show record state (Confirmed / Pending / Cancelled; Available / In Session / Off).
- **Anatomy:** pill with optional leading dot/icon + label.
- **Classes:** `inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium`, plus a semantic pair:
  - Success → `bg-success-tint text-success` (Confirmed, Available)
  - Warning → `bg-warning-tint text-warning` (Pending, In Session)
  - Danger → `bg-danger-tint text-danger` (Cancelled, Off)
- **Rule:** label text is mandatory; color is reinforcement, not the sole signal.

### 7.5 Nav item

- **Purpose:** Sidebar navigation entry.
- **Anatomy:** icon + label, full-width clickable row.
- **States:**
  - Idle — `text-muted hover:text-ink hover:bg-hover-surface rounded-lg px-3 py-2`
  - Active — `bg-primary-tint text-primary font-medium rounded-lg px-3 py-2` (icon inherits `primary`).
- Group labels: `text-xs font-medium uppercase tracking-wide text-muted-soft`.

### 7.6 Data table

- **Purpose:** List records (Patient Bookings, queue entries).
- **Anatomy:** header row + body rows; columns e.g. Patient Name · Number · Date · Status · Doctor · Action.
- **Header:** `text-xs font-medium text-muted`, bottom `border-border`.
- **Row:** `text-sm text-ink`, `border-b border-border`, `hover:bg-hover-surface`; comfortable height (`py-3`).
- **Cells:** avatar cell = `Avatar` + name; status cell = `Status badge`; action cell = `Button` (primary `sm` or ghost).
- Optional leading checkbox column and `Filter` / `Sort by` pills in the panel header.

### 7.7 Avatar

- **Purpose:** Represent a person (patient, doctor, current user).
- **Classes:** `rounded-full object-cover` at `w-8 h-8` (table/list) or `w-9 h-9` (top bar). Fallback: initials on `primary-tint` with `primary` text. Optional status ring using a semantic color.

### 7.8 Filter / Sort pill

- **Purpose:** Lightweight control in panel headers ("Filter", "Sort by", date range, "Monthly").
- **Classes:** `inline-flex items-center gap-2 text-xs font-medium text-ink bg-surface border border-border rounded-lg px-3 py-1.5 hover:bg-hover-surface`, with a leading icon and (for dropdowns) a trailing chevron.

### 7.9 Icon chip

- **Purpose:** Small rounded container holding a card's leading icon.
- **Classes:** `inline-flex items-center justify-center rounded-lg bg-primary-tint text-primary w-8 h-8`.

---

## 8. Accessibility

- **Contrast.** `ink` (`#303030`) on `surface`/`background` passes AA. `primary` green `#34AA86` on white does **not** reliably pass AA for small text — use `success` (`#1E9E6A`) for small green text, and reserve `primary` for large text, icons, and fills. White text on the `primary` hero card is acceptable at `text-3xl`+ sizes.
- **Status is never color-only.** Every badge carries a text label; add an icon where it aids scanning. This supports color-blind users.
- **Focus.** All interactive elements get a visible focus ring: `focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none`.
- **Hit targets.** Minimum `~40px` height for buttons, nav items, and table actions.
- **Semantics.** Use real `<button>`, `<a>`, `<table>` elements; label inputs and icon-only buttons with `aria-label`.

---

## 9. Implementation status

| Item | Status |
|------|--------|
| Design tokens in `app.css @theme` | **Not applied** — spec in [§3](#3-color-palette) |
| Switzer font self-hosted | **Not applied** — spec in [§4](#4-typography); app currently loads Instrument Sans via Bunny |
| Blade/Livewire components | **None yet** — see [Component Catalog](#11-component-catalog) |

This guide is documentation-first: it defines the target so that all future UI work is consistent from day one. Wire up the tokens and font before (or alongside) building the first real component.

---

## 10. Contribution checklist

Before a UI component (Blade or Livewire) is considered complete:

- [ ] Uses only documented **color tokens** — no ad-hoc hex or arbitrary `[#…]` classes.
- [ ] Uses the **Switzer** font stack (once wired) — no per-component font overrides.
- [ ] Uses documented **radius, spacing, and shadow** values.
- [ ] Matches the relevant **component spec** in [§7](#7-component-specs), or adds a new spec there if it is a genuinely new pattern.
- [ ] Meets the **accessibility** rules in [§8](#8-accessibility) (contrast, focus, status-not-color-only, semantics).
- [ ] Is **registered in the [Component Catalog](#11-component-catalog)** with its path and status, in the same change.

If a design need is not covered by an existing token or spec, extend this guide first, then build — do not diverge silently.

---

## 11. Component Catalog

Every reusable Blade/Livewire component in the app is registered here. **Add a row whenever you create or significantly change a component.**

| Component | Type | Path | Spec | Status | Notes |
|-----------|------|------|------|--------|-------|
| _Template — copy this row_ | Blade / Livewire | `resources/views/components/…` or `app/Livewire/…` | [§7.x](#7-component-specs) | Planned / In progress / Done | — |

_No components have been built yet. This table will grow as the UI is implemented; the [governing rule](#clinic-queue--style-guideline) makes registration mandatory._
