# EMBRYOCASE
### Embryo Data & Research Management System

**EmbryoCase** is a research-grade, centralized embryo data management and analysis platform engineered for reproductive biology laboratories and clinical embryologists. It provides centralized administrative ownership, multi-factor credentialed access, private non-destructive image repositories from Day 1 to Day 6+, Fabric.js vector annotations, side-by-side comparative analysis, clinical observation records, audit trails, and backup synchronization.

---

## 1. System Architecture & Technology Stack

- **Backend:** PHP 8.4+ (Portable PHP 8.4.27 included in `tools/php`), Laravel 13, Composer 2.11
- **Database:** MySQL 8 (production & XAMPP ready) / SQLite (zero-config local runtime)
- **Frontend:** Monochromatic Scientific Design System, Bootstrap 5.3.3 (local), AJAX, Vanilla JS
- **Vector Image Annotation:** Fabric.js 5.3.1 (integrated offline in `public/vendor/fabric/`)
- **Image Architecture:** Non-destructive SHA-256 storage (`storage/app/private/embryos/`)
- **Authentication & Security:** 
  - Admin: Email + Password + 6-digit OTP
  - Embryologist: **4 Mandatory Credentials** (Full Name + Registered Email + Password + Unique Special Key) + 6-digit OTP

---

## 2. Default Seeded Credentials

### A. Central System Administrator
- **Portal URL:** `http://localhost:8000/login/admin`
- **Email:** `admin@embryocase.lab`
- **Password:** `AdminPassword123!`
- **Role:** Administrator (Central database ownership, staff management, case oversight, backups)
- **OTP Verification:** Dispatched to email (in local debug mode, displayed directly on screen for developer testing)

### B. Clinical Embryologists
- **Portal URL:** `http://localhost:8000/login`

**Account 1:**
- **1. Full Name:** `Dr. Sarah Chen`
- **2. Email:** `sarah.chen@embryocase.lab`
- **3. Password:** `EmbryoPassword123!`
- **4. Special Key:** `EMB-KEY-7892-XYZ`

**Account 2:**
- **1. Full Name:** `Dr. Arthur Miller`
- **2. Email:** `arthur.miller@embryocase.lab`
- **3. Password:** `EmbryoPassword123!`
- **4. Special Key:** `EMB-KEY-4519-ABC`

---

## 3. How to Launch the Application

Double-click `serve.cmd` in the project root, or execute:

```powershell
.\artisan.cmd serve --port=8000
```

Open your browser at:
```
http://localhost:8000
```

---

## 4. Key Functional Modules

### 1. Monochromatic Scientific UI
Strict scientific palette conforming to medical imaging systems:
- Pure White (`#FFFFFF`) & Off-White (`#FAFAFA`)
- Pure Black (`#000000`) for primary typography and buttons
- Technical Grays (`#E5E5E5`, `#A0A0A0`, `#404040`) for borders and metadata
- No generic SaaS gradients, illustrations, or neon styling.

### 2. Multi-Layer Authentication & Special Key Management
- Embryologists must supply all four credentials on every single session initiation.
- Special Keys are cryptographically hashed in MySQL (`special_key_hash`) and never stored in plaintext.
- Administrators can activate, deactivate, suspend accounts, regenerate keys, or revoke keys at any moment.

### 3. Embryo Case Lifecycle & Day-Wise Repository
- Sequential internal Case IDs: `EMB-YYYY-XXXX` (e.g. `EMB-2026-0001`).
- Day 1 to Day 6 dynamic folder structures automatically created in private storage.
- Dual-view interface:
  - **Folder View:** Structured directory tree with image galleries and observation records.
  - **Timeline View:** Chronological developmental timeline ordered by 24h acquisition timestamps.

### 4. Non-Destructive Scientific Annotation Editor (Fabric.js)
- **Left Toolbar (9 Tools):** Selection, Freehand Pen, Highlighter Brush, Straight Line, Arrow, Circle, Rectangle, Text Box (re-editable after placement), Eraser.
- **Top Toolbar:** Undo/Redo, Zoom In/Out/Reset, Clinical Marker Color Picker, Stroke Width Slider, Text Size Selector, Save, Cancel.
- **Storage Convention:**
  - Raw microscopy image untouched: `09-00-00.png`
  - Flattened preview generated separately: `09-00-00_E.png`
  - Vector coordinates & objects preserved in JSON: `09-00-00.json`
  - Reopening the image restores all vector objects for continued scientific editing.

### 5. Side-by-Side Dual-Viewport Comparison
- Compare Original vs Edited (`_E.png`).
- Compare across developmental days (e.g. Day 1 vs Day 2, Day 2 vs Day 3).
- Independent pan and zoom controls for each viewport.

### 6. Clinical Observations
- Independent clinical notes linked by author, timestamp, development day, and optional specific image reference.

### 7. Export & Backup Synchronization
- Single-case ZIP export (`EMB-2026-0001.zip`) containing `Case_Metadata.json`, Day folders, original files, edited files, and annotation JSONs.
- Administrator Backup Console: Generates full atomic archives (SQL database dump + complete media storage) downloadable directly to the Admin's PC for offline synchronization.

---

## 5. Automated Verification & Testing

To run the automated PHPUnit test suite:

```powershell
.\artisan.cmd test
```

All 8 tests (29 assertions) verify:
- Security gateway redirection
- 4-credential validation and lockout
- Administrator OTP generation and session authorization
- Sequential Case ID generation (`EMB-YYYY-XXXX`)
- Non-destructive Fabric.js vector saving and flattened preview generation
