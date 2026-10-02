# CinePass Movie Booking System — GUI Standards & Interface Design Specification
**Document Version:** 1.0  
**Project:** Movie Booking System (Aptech Semester 2 eProject)  
**Target Platform:** Web (Desktop, Laptop, Tablet, Mobile)  
**Technology Stack:** HTML5, CSS3 (Custom + Responsive), Bootstrap 5.3, Font Awesome 6.5, Poppins Font  

---

## 1. Design Philosophy & Aesthetic Theme

The **CinePass** graphical user interface is engineered with a **Modern Dark Cinema Theme (Netflix / IMAX aesthetic)**.  
The dark interface mimics the ambience of a physical movie theater auditorium, enhancing the visual prominence of vivid movie posters, vibrant genre badges, and dynamic seat selection grids while reducing eye strain.

---

## 2. Color Palette & Design Tokens

### Primary Theme Tokens

| Token Name | Hex Code | Visual Tone | Usage Description |
|---|---|:---:|---|
| `--cine-bg` | `#0F1016` | [Dark Black] | Core viewport background; deep night cinema tone |
| `--cine-dark` | `#0A0B0E` | [Deep Black] | Darker contrast background for headers and footers |
| `--cine-card-bg` | `#181924` | [Slate Surface] | Surface background for cards, tables, and modal dialogs |
| `--cine-card-hover` | `#1F2030` | [Elevated Slate] | Card hover elevation state |
| `--cine-card-border` | `#282A3C` | [Dark Border] | Subtle dividing line and container border |
| `--cine-primary` | `#E50914` | [Crimson Red] | Cinema Crimson Red; primary CTA buttons, logo accent, active links |
| `--cine-primary-hover`| `#F40612` | [Vivid Red] | Hover state for primary action buttons |
| `--cine-accent` | `#F5C518` | [Gold Amber] | IMDb-style Gold Amber; ratings, VIP seats, admin highlights |
| `--cine-text` | `#F0F2F5` | [Pure White] | High-contrast white typography for primary headings & text |
| `--cine-text-muted` | `#9CA3AF` | [Muted Gray] | Slate Gray for descriptions, labels, and metadata |

### Semantic Status Indicators

| Status Category | Hex Code | UI Application |
|---|---|---|
| **Success** | `#10B981` / `#22C55E` | Confirmed bookings, available seats, payment success alerts |
| **Warning** | `#F59E0B` / `#F5C518` | Pending payments, expiring reserves, cautionary notifications |
| **Danger / Error** | `#EF4444` / `#E50914` | Sold out / booked seats, validation failures, booking cancellations |
| **Info** | `#3B82F6` | Informational announcements, screening schedules, notice banners |
| **Maintenance** | `#4B4B54` | Blocked auditorium seats under physical maintenance |

---

## 3. Typography & Hierarchy

### Font Family
- **Primary Typeface:** `'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`
  - Loaded via Google Fonts across weights: `300` (Light), `400` (Regular), `500` (Medium), `600` (SemiBold), `700` (Bold), and `800` (ExtraBold).
- **Monospace Typeface:** `'SFMono-Regular', Menlo, Monaco, Consolas, 'Courier New', monospace`
  - Utilized for Booking Codes (`CP-74D3A19B`), Transaction IDs, and digital barcodes.

### Type Scale

| Element | Desktop Size | Mobile Size | Weight | Line Height | Case / Style |
|---|---|---|---|---|---|
| **Display / Hero H1** | `2.5rem` (40px) | `1.85rem` (30px) | 800 | 1.2 | Title Case |
| **Page Title H2** | `2.0rem` (32px) | `1.6rem` (25.6px) | 700 | 1.25 | Title Case |
| **Section Header H3** | `1.5rem` (24px) | `1.3rem` (20.8px) | 600 | 1.3 | Title Case |
| **Card Heading H4** | `1.25rem` (20px) | `1.15rem` (18.4px) | 600 | 1.35 | Title Case |
| **Subheading H5/H6** | `1.05rem` (16.8px) | `0.95rem` (15.2px) | 500 | 1.4 | Sentence Case |
| **Body Text** | `1.0rem` (16px) | `0.95rem` (15.2px) | 400 | 1.6 | Regular Text |
| **Small / Meta** | `0.85rem` (13.6px) | `0.8rem` (12.8px) | 500 | 1.5 | Meta / Subtitle |
| **Badges & Captions** | `0.75rem` (12px) | `0.7rem` (11.2px) | 700 | 1.0 | Uppercase |

---

## 4. Component Standards

### A. Buttons (`.btn`)

1. **Primary Button (`.btn-cine-primary`):**
   - Background: `linear-gradient(135deg, #e50914 0%, #b20710 100%)`
   - Border: None
   - Text: White, Bold (600)
   - Border Radius: `8px`
   - Shadow: `0 4px 14px rgba(229, 9, 20, 0.4)`
   - Hover Effect: Translates `-2px` vertically, shadow expands to `0 6px 18px rgba(229, 9, 20, 0.5)`.

2. **Outline Button (`.btn-cine-outline` / `.btn-outline-light`):**
   - Background: `rgba(255, 255, 255, 0.05)`
   - Border: `1px solid #282a3c`
   - Text: White, Medium (500)
   - Hover Effect: Background shifts to `rgba(255, 255, 255, 0.15)`.

3. **Accent / Highlight Button (`.btn-warning`):**
   - Background: `linear-gradient(135deg, #f5c518 0%, #d49a00 100%)`
   - Text: Black `#000000`, Extra Bold (700)
   - Used for: "Print Ticket Voucher", "Admin Quick Actions".

4. **Touch Target Size Rule:**
   - Minimum tap target height: `40px` on desktop, `44px` on mobile screens.

---

### B. Form Controls & Validation

1. **Input Fields (`.form-control`, `.cine-form-control`):**
   - Background: `#12131C`
   - Border: `1.5px solid #282A3C`
   - Color: `#F0F2F5`
   - Border Radius: `8px`
   - Focus State: Border switches to `--cine-primary` (`#E50914`) or Gold with outer glow `box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.25)`.
   - Placeholder: `#6C757D`

2. **Validation Feedback:**
   - **Valid State (`.is-valid`):** Green border `#10B981` with checkmark icon.
   - **Invalid State (`.is-invalid`):** Red border `#EF4444` with exclamation icon.
   - **Invalid Message (`.invalid-feedback`):** Text size `0.82rem`, color `#F87171`, clearly stating the validation requirement (e.g., *"Please provide a valid email address"*).

---

### C. Cards & Containers

1. **Movie Poster Card (`.movie-card`):**
   - Aspect Ratio: Standard 2:3 movie poster format.
   - Border Radius: `12px`
   - Hover Animation: `transform: translateY(-8px)` with transition `0.3s cubic-bezier(0.4, 0, 0.2, 1)`.
   - Poster Glow: `box-shadow: 0 12px 28px rgba(0, 0, 0, 0.6)`.
   - Overlay Badges: Rating badge positioned top-left; format badge (IMAX/2D) positioned top-right.

2. **Surface Content Card (`.cine-card`):**
   - Background: `#181924`
   - Border: `1px solid #282A3C`
   - Border Radius: `14px`
   - Padding: `1.5rem` to `2.5rem`

3. **Auditorium Seat Component (`.seat-item`):**
   - Geometry: Curved cinema armchair icon (`width: 38px`, `height: 36px`, rounded `8px 8px 4px 4px`).
   - States:
     - **Available:** Dark slate `#171923`, border `#2D3142`, text `#E2E8F0`. Hover: Green or Red highlight.
     - **Selected:** Vivid Green `#22C55E`, border `#16A34A`, text `#052E16` with ambient glow.
     - **Booked / Sold Out:** Dark muted `#1E1215`, text `#64323B`, line-through, pointer-events disabled.
     - **Maintenance:** Dashed border `#3A3A42`, low opacity `0.45`.

4. **Admission Ticket Voucher Card (`#printableTicket`):**
   - Authentic cinema pass aesthetics featuring semi-circle notch cutouts (`.ticket-notch`).
   - Perforated cut line (`.ticket-perforation`) separating the main admission voucher from the turnstile QR scan stub.

---

### D. Navigation Standards

1. **Customer Navigation Bar:**
   - Position: `sticky-top` with backdrop blur filter (`backdrop-filter: blur(12px)`).
   - Brand Icon: Vivid red gradient square featuring film reel iconography.
   - Search Field: Integrated quick search with pill border-radius.
   - User Profile Menu: Dropdown with dark theme styling and role indicator.

2. **Administrator Console Sidebar:**
   - Desktop: Sticky vertical card featuring admin avatar, active state indicators, and 9 section links.
   - Tablet/Mobile: Responsive horizontal pill-scroller preventing vertical screen blockage.

3. **Breadcrumbs:**
   - Located below the navbar on inner pages.
   - Format: `Home > Movies > [Movie Title] > Select Seats`.

---

## 5. Page Layout Architecture

```text
┌────────────────────────────────────────────────────────┐
│  Sticky Navigation Bar (Brand, Links, Search, Auth)    │
├────────────────────────────────────────────────────────┤
│  Breadcrumb Navigation / Notice Flash Alert            │
├────────────────────────────────────────────────────────┤
│                                                        │
│  Main Content Area                                     │
│  ┌───────────────────────┬──────────────────────────┐  │
│  │ Primary Action / Grid │ Sticky Summary / Sidebar │  │
│  │ (e.g. Auditorium Map) │ (e.g. Booking Drawer)    │  │
│  └───────────────────────┴──────────────────────────┘  │
│                                                        │
├────────────────────────────────────────────────────────┤
│  Cinema Footer (Links, Social, Aptech Project Credits) │
└────────────────────────────────────────────────────────┘
```

---

## 6. Feedback & Notification Design

### Flash Alert Notifications
All system messages use standardized dismissal alerts positioned at the top of the content container:
- **Success (`.alert-success`):** Green banner with `<i class="fa-solid fa-circle-check"></i>`.
- **Error / Failure (`.alert-danger`):** Red banner with `<i class="fa-solid fa-circle-exclamation"></i>`.
- **Warning (`.alert-warning`):** Amber banner with `<i class="fa-solid fa-triangle-exclamation"></i>`.
- **Information (`.alert-info`):** Blue banner with `<i class="fa-solid fa-circle-info"></i>`.

### Empty State Design
When searches yield zero matches, or user has no bookings:
- Centered layout within `.cine-card`.
- Large muted icon (size `3rem` to `4rem`).
- Friendly explanatory text with a clear next-step button (e.g. *"Browse Now Showing Movies"*).

---

## 7. Responsive Breakpoint Specification

| Breakpoint Name | Viewport Width | Layout Adaptations |
|---|---|---|
| **Large Desktop / 4K** | $\ge 1200\text{px}$ | 4-column movie grid, expansive auditorium seat grid, sticky summary drawer. |
| **Laptop** | $992\text{px} - 1199.98\text{px}$ | 3-column movie grid, compact navigation search input, proportional hero banners. |
| **Tablet** | $768\text{px} - 991.98\text{px}$ | 2-column movie grid, horizontal admin sidebar pills, collapsed burger menu. |
| **Mobile** | $576\text{px} - 767.98\text{px}$ | 1-to-2 column responsive cards, touch-scrollable seat map (`32px` seats), full-width action buttons. |
| **Small Mobile** | $< 576\text{px}$ | Single column movie cards, compact seats (`28px`), stacked ticket voucher stubs. |

---

## 8. Accessibility (a11y) & Usability Guidelines
1. **Color Contrast:** All body text meets WCAG AA standards (minimum contrast ratio of 4.5:1 against dark backgrounds).
2. **Keyboard Navigation:** All interactive elements (`<button>`, `<a>`, `<input>`) support visible outline focus states.
3. **Form Semantics:** All inputs include associated `<label>` tags with clear required asterisks (`*`).
4. **Print Optimization:** The ticket voucher page includes dedicated `@media print` rules removing background colors, navbars, and buttons to produce ink-friendly, high-contrast black-and-white physical tickets.
