---
name: SIPERSU FT-UMB Design System
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#3f4941'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#6f7a70'
  outline-variant: '#bfc9be'
  surface-tint: '#116c3f'
  primary: '#00512c'
  on-primary: '#ffffff'
  primary-container: '#0f6b3e'
  on-primary-container: '#95e9b0'
  inverse-primary: '#85d8a1'
  secondary: '#785900'
  on-secondary: '#ffffff'
  secondary-container: '#fcc019'
  on-secondary-container: '#6c5000'
  tertiary: '#00512c'
  on-tertiary: '#ffffff'
  tertiary-container: '#006c3d'
  on-tertiary-container: '#87ecaa'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#a1f5bb'
  primary-fixed-dim: '#85d8a1'
  on-primary-fixed: '#00210f'
  on-primary-fixed-variant: '#00522d'
  secondary-fixed: '#ffdf9d'
  secondary-fixed-dim: '#f9bd14'
  on-secondary-fixed: '#251a00'
  on-secondary-fixed-variant: '#5b4300'
  tertiary-fixed: '#92f8b5'
  tertiary-fixed-dim: '#76db9b'
  on-tertiary-fixed: '#00210f'
  on-tertiary-fixed-variant: '#00522d'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.04em
  mono-data:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 0.75rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

The design system embodies modern institutional prestige, academic rigor, and administrative clarity for the Faculty of Engineering at Universitas Muhammadiyah Buton. The visual language blends the formal authority of academic governance with the high utility of a contemporary enterprise application.

The design movement is **Modern Institutional Dashboard**, characterized by:
- Pure, airy workspaces that prioritize cognitive ease during document management and audit tasks.
- Precise architectural hierarchy, utilizing subtle low-contrast borders and elevated structural surfaces rather than aggressive skeuomorphism or loud decorative styling.
- A distinctive identity anchored by the iconic Muhammadiyah green, accented with purposeful warm gold/amber signifiers representing excellence and institutional stewardship.
- Intuitive workflow clarity tailored for deans, department heads (Kaprodi), administrative staff, and students interacting with formal university correspondence (*persuratan*).

## Colors

The color palette is calibrated for high accessibility, prolonged desktop usage, and clear state communication across document workflows.

### Primary & Accent
- **Primary Brand (`#0F6B3E`)**: Deep Muhammadiyah Green. Anchors top-level navigation, primary call-to-action buttons, active navigation states, and identity headers.
- **Secondary / Accent (`#F2B705`)**: Warm Gold/Amber. Reserved for highlight markers, pending action notices, critical warnings, and institutional accents.
- **Interactive Tint (`#14854E`)**: Subtle emerald used for interactive hover, active focus states, and soft green fills.

### Surface & Neutral Architecture
- **Canvas / White (`#FFFFFF`)**: Pure white base for data cards, modal sheets, and active form panels.
- **Surface Muted (`#F8FAFC`)**: Light slate gray canvas background behind elevated cards.
- **Surface Structural (`#F1F5F9`)**: Sidebar background, table header strips, and disabled field fills.
- **Border Default (`#E2E8F0`)**: Crisp 1px perimeter border defining cards, inputs, and column dividers.
- **Border Subdued (`#F1F5F9`)**: Internal table row separators.

### Typography Hierarchy Tokens
- **Text Headings (`#0F172A`)**: Deep slate for maximum legibility and gravitas.
- **Text Body (`#334155`)**: Slate neutral for reading flow and field labels.
- **Text Muted / Meta (`#64748B`)**: Secondary information, timestamps, and placeholder text.

### Status Semantic Indicators (Status Persuratan)
- **Diajukan (Submitted)**: Slate Blue — Background `#F1F5F9`, Text `#475569`, Border `#CBD5E1`
- **Diverifikasi (Verified)**: Indigo — Background `#EEF2FF`, Text `#4338CA`, Border `#C7D2FE`
- **Disetujui (Approved)**: Amber — Background `#FEF3C7`, Text `#B45309`, Border `#FDE68A`
- **Ditandatangani (Signed)**: Purple — Background `#F3E8FF`, Text `#6B21A8`, Border `#E9D5FF`
- **Selesai (Completed)**: Emerald — Background `#ECFDF5`, Text `#065F46`, Border `#A7F3D0`
- **Ditolak (Rejected)**: Rose Red — Background `#FFE4E6`, Text `#9F1239`, Border `#FECDD3`

## Typography

The dual typographic scale utilizes **Plus Jakarta Sans** for display titles, page headers, and section groupings to provide geometric modernity and administrative authority. **Inter** powers data tables, form controls, labels, and document body text, maximizing micro-legibility for Indonesian legal and administrative correspondence.

### Key Rules
- Use `headline-xl` exclusively for primary portal views (e.g., "Ringkasan Arsip Persuratan").
- Use `mono-data` with tabular numbers (`font-variant-numeric: tabular-nums`) for official document tracking IDs (*Nomor Surat*, *NIDN*, *NIM*).
- Form inputs, helper texts, and data table rows must always default to `body-md` and `body-sm`.
- Status badges and metadata tags utilize `label-sm` with slight uppercase treatment and expanded tracking.

## Layout & Spacing

The layout is built around a structured desktop dashboard grid that smoothly degrades down to mobile screens:

### Layout Shell Architecture
- **Left Sidebar (Desktop)**: Fixed 260px width, extending full height with `#F8FAFC` background and a `#E2E8F0` right-hand border.
- **Top Header Bar**: 64px fixed height with `#FFFFFF` background, subtle elevation border, housing contextual breadcrumbs, global document search, and user profile switchers.
- **Main Canvas Area**: Fluid width content area with `#F8FAFC` canvas tone and `margin` padding (32px desktop, 16px mobile).
- **Responsive Adaptations**: At viewport widths `< 1024px`, the sidebar collapses into an off-canvas drawer accessed via a hamburger menu. The table container switches to horizontal scrolling with sticky primary columns.

### Spacing Rhythm
- Grid layouts leverage a strict 8pt rhythm. 
- Form field stacks utilize `space-md` (16px) between groups, with `space-xs` (4px) separating labels from input fields.
- Content card bodies maintain `space-lg` (24px) internal padding.

## Elevation & Depth

Visual hierarchy employs low-elevation structural planes, pairing subtle borders with tinted ambient drop shadows to maintain a disciplined administrative atmosphere.

### Surface Tiers
- **Level 0 (Canvas)**: Background surface (`#F8FAFC`) without shadow.
- **Level 1 (Card & Content Container)**: Elevated white cards (`#FFFFFF`) with border `1px solid #E2E8F0` and `box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)`.
- **Level 2 (Dropdowns, Popovers, & Tooltips)**: Elevated panels with `box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -2px rgba(15, 23, 42, 0.06)`.
- **Level 3 (Modals & Confirmation Drawers)**: Centered interactive dialogs with `box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08)` coupled with an overlay backdrop of `rgba(15, 23, 42, 0.45)`.

## Shapes

The interface adopts a disciplined roundedness geometry with a default radius of 8px (`0.5rem`).

- **Data Cards, Tables, Modals, & Panels**: 8px (`0.5rem`) for a clean, professional, and unified silhouette.
- **Form Controls & Buttons**: 8px (`0.5rem`) corner rounding.
- **Status Badges & Avatar Containers**: Pill-rounded (`9999px`) or `rounded-md` (6px) for compact indicators.
- **Dividers & Focus Rings**: 2px focus outline with 2px offset in primary `#0F6B3E` for keyboard accessibility.

## Components

### Tombol (Buttons)
- **Primary**: Background `#0F6B3E`, text `#FFFFFF`, radius 8px, padding 10px 16px. Hover state changes background to `#14854E`. Active state: `#0B4F2E`.
- **Secondary / Outline**: Border `1px solid #CBD5E1`, background `#FFFFFF`, text `#334155`. Hover state: background `#F8FAFC`, border `#94A3B8`.
- **Accent (Tindakan Khusus)**: Background `#F2B705`, text `#0F172A`, font weight 600. Hover state: background `#D9A404`.
- **Danger (Tolak / Hapus)**: Background `#FFF1F2`, text `#E11D48`, border `1px solid #FECDD3`. Hover: background `#FFE4E6`.

### Lencana Status Surat (Status Badges)
Compact pills (padding 4px 10px, radius 9999px, font size 12px, font weight 600):
- **Diajukan**: Background `#F1F5F9`, text `#475569`, border `1px solid #CBD5E1`.
- **Diverifikasi**: Background `#EEF2FF`, text `#4338CA`, border `1px solid #C7D2FE`.
- **Disetujui**: Background `#FEF3C7`, text `#B45309`, border `1px solid #FDE68A`.
- **Ditandatangani**: Background `#F3E8FF`, text `#6B21A8`, border `1px solid #E9D5FF`.
- **Selesai**: Background `#ECFDF5`, text `#065F46`, border `1px solid #A7F3D0`.
- **Ditolak**: Background `#FFE4E6`, text `#9F1239`, border `1px solid #FECDD3`.

### Formulir & Bidang Input (Form Fields)
- **Text & Select Inputs**: Background `#FFFFFF`, border `1px solid #E2E8F0`, radius 8px, height 40px, padding 8px 12px, text `#0F172A`.
- **State Interaktif**: Focus displays border `#0F6B3E` with an outer ring `0 0 0 2px rgba(15, 107, 62, 0.2)`. Error state uses border `#E11D48`.
- **Label**: Font size 13px, weight 600, color `#334155`, bottom margin 6px.

### Tabel Data Arsip (Data Tables)
- **Table Frame**: Enclosed in a 1px `#E2E8F0` border card with 8px radius.
- **Header (`<thead>`)**: Background `#F8FAFC`, text `#64748B`, font size 12px, uppercase tracking, height 44px, bottom border `1px solid #E2E8F0`.
- **Row (`<tr>`)**: Height 52px, alternating or white rows with hover background `#F8FAFC`. Bottom border `1px solid #F1F5F9`.
- **Action Column**: Pinned actions with quick buttons for "Detail", "Disposisi", and "Cetak".

### Kartu Ringkasan (Metric Cards)
- Background `#FFFFFF`, border `1px solid #E2E8F0`, padding 20px, radius 8px.
- Displays key figures (e.g., "Surat Masuk", "Menunggu Tanda Tangan") with an accent icon wrapper in 10% tint of the metric's status color.

### Log Riwayat Surat (Timeline & Audit Trail)
- Vertical step trail with 2px connecting rule in `#E2E8F0`.
- Node bullets use `#0F6B3E` for completed checkpoints and `#CBD5E1` for pending stages, accompanied by operator timestamp and role title.