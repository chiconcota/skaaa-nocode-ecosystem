# Skaaa No-Code Ecosystem

> **A High-Performance, Decoupled, and AI-Native Visual Application Platform for WordPress.**

[![License: GPL-3.0](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4_JIT_Offline-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-v3_Reactive_Store-8BC0D0?logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![React Flow](https://img.shields.io/badge/React_Flow-v11_DAG-FF0072)](https://reactflow.dev)
[![Architecture](https://img.shields.io/badge/Architecture-Decoupled_Microservices-orange.svg)](#-system-architecture)

**Skaaa No-Code Ecosystem** is an enterprise-grade visual application platform that transforms WordPress from a traditional blogging CMS into a lightning-fast, modern web application engine. 

Engineered from the ground up to eradicate legacy technical debt, Skaaa eliminates the notorious `wp_postmeta` bottleneck, strips away bulky page builder bloat, and operates 100% offline without runtime CDN dependencies. It equips creators, developers, and agency teams with a complete suite for data modeling, visual design, event-driven DAG workflows, and 1-click cloud synchronization.

---

## 🗺️ System Architecture

The Skaaa Ecosystem is built upon a **Decoupled Microservices Architecture** consisting of **1 Blank Canvas Theme** and **4 Independent Core Plugins**. Each module possesses strict boundary isolation and communicates exclusively via WordPress Action/Filter hooks, secure REST APIs, and reactive Alpine.js global stores:

```
                          ┌─────────────────────────────────────┐
                          │         Skaaa Canvas Theme          │
                          │   (Zero-Legacy Pure Blank Canvas)   │
                          └──────────────────┬──────────────────┘
                                             │
      ┌──────────────────────────────────────┼──────────────────────────────────────┐
      │                   THE CORE APPLICATION TRINITY                       │      │
      ├──────────────────────┬───────────────┴────────┬─────────────────────┤      │
      │   Skaaa Data Pro     │   Skaaa Logic Engine   │ Skaaa No-Code Design│      │
      │  • Flat MySQL Tables │  • Visual DAG Graphs   │  • 15 Atomic Blocks │      │
      │  • Native JSON Types │  • SkaaaFX AST Engine  │  • Offline Tailwind │      │
      │  • Smart Objects     │  • Pluggable Nodes     │  • Skaaapine State  │      │
      │  • Dynamic DataGrid  │  • Background Workers  │  • Token Dark Mode  │      │
      └──────────────────────┴───────────────┬────────┴─────────────────────┘      │
                                             │                                     │
      ┌──────────────────────────────────────┴─────────────────────────────────────┼────┐
      │                                                                            │    │
      │                           Skaaai (Bridge & Harness)                        │    │
      │  • 1-Click Bidirectional Sync Bridge (Localhost ⟷ Production/Live)         │    │
      │  • True Mirror Synchronization & Deep Pre-flight Diff Engine                │    │
      │  • Persistent Remote Code Deployer (wp-content/skaaa-custom-nodes/)        │    │
      │  • Local Agent Harness Initializer (db-tool, block-tool, jit-tool)         │    │
      │  • AI Copilot Automation Nodes (Gemini / OpenAI Integrations)              │    │
      └────────────────────────────────────────────────────────────────────────────┴────┘
```

---

## 🌟 The 5 Pillars of the Ecosystem

### 1. 🎨 Skaaa Canvas (Theme) — *The Zero-Legacy Foundation*
* **100% Clean Slate**: Completely strips out default WordPress block library CSS (`.wp-block-library`), global inline styles, and theme bloat, giving you an immaculate 0KB baseline.
* **Smart Admin Bar Offset**: Automatically compensates fixed and sticky navigation for logged-in administrators (`top: 32px` desktop, `top: 46px` mobile) using natural CSS specificity with zero `!important` declarations.
* **Full-Width Canvas**: Delivers edge-to-edge layouts designed specifically for modern web applications and responsive landing pages.

### 2. ⚡ Skaaa No-Code Design (Plugin) — *Visual UI & Offline Tailwind JIT*
* **15 Native Atomic Blocks**: Render clean, semantic Flat DOM without redundant `<div>` wrapper soup: `Container`, `Text`, `Button`, `Image`, `Icon`, `Video`, `SVG`, `Code`, `Loop`, `List`, `List Item`, `Form Inputs`, and `Organism Ref`.
* **Zero-CDN Tailwind v4 JIT Engine**: Dual-compiler architecture (PHP backend compiler for frontend SSR + SkaaaWind JavaScript compiler for live Gutenberg canvas) operating 100% offline with Single Source of Truth (`tailwind-rules.json`).
* **Instant Editor Dark/Light Mode**: Live canvas Dark Mode toggle directly inside the Gutenberg top toolbar with persistent state, synchronizing CSS tokens and custom color opacities in real time.
* **Skaaapine Reactive Engine**: Dynamic frontend interactivity powered by Alpine.js v3, utilizing isolated global stores (`Alpine.store`) and automatic `@click.prevent` hash-less navigation.
* **Design Token System**: Centralized design system managing typography, color palettes, spacing, and container widths across all blocks.

### 3. 💾 Skaaa Data Pro (Plugin) — *High-Performance Flat Database Engine*
* **No-Postmeta Rule**: Breaks free from WordPress's slow EAV model. Automatically provisions and manages dedicated flat MySQL tables (`skaaa_data_*`) for blazing-fast indexing and query execution.
* **Native MySQL JSON Storage**: Stores relational mappings, multi-select values, and complex payloads directly into native MySQL JSON columns with automated object enrichment.
* **Smart Object Blueprint**: JSON-portable schema import and export system with dynamic slug collision handling and automated relationship re-wiring.
* **Interactive DataGrid**: Modern ES6/Vite-powered inline editing interface featuring specialized cell strategies for rapid CRUD operations.
* **Rollup Virtualization**: Real-time virtual aggregations computed in PHP memory with zero unnecessary database writes.

### 4. 🧠 Skaaa Logic Engine (Plugin) — *Event-Driven Workflow Brain*
* **DAG Visual Workflow Canvas**: Visual node-based graph editor powered by React Flow v11, supporting real-time switching between visual graph and JSON schema representations.
* **SkaaaFX Expression Language (AST Evaluator)**: Dedicated expression interpolation engine evaluated via Abstract Syntax Tree (AST), complete with context-aware floating autocomplete for variables and functions.
* **Pluggable Nodes Framework**: Decentralized architecture allowing addon plugins (such as Skaaai) to register custom execution nodes with dynamic settings schemas.
* **Two-Pass Template Rendering**: Pure template interpolation resolving dynamic block structures first, followed by personalized runtime data binding.
* **Asynchronous Task Workers**: Background job execution powered by Action Scheduler with incoming Webhook triggers.

### 5. 🚀 Skaaai (Plugin) — *Bidirectional Sync Bridge & AI Automation*
* **1-Click Cloud Sync Bridge**: Effortlessly synchronize pages, posts, media assets, design presets, organisms, theme templates, logic workflows, and database tables between Localhost and Live Webhosts.
* **True Mirror Synchronization**: Complete row-level parity for flat tables (`skaaa_data_*`), orphan cleanup for deleted workflows/organisms, and safe trashing (`wp_trash_post`) for removed remote content.
* **Deep Pre-flight Diff Engine**: 4-state visual inspection (`Modified`, `New`, `Deleted on Live`, `Synced`) with interactive accordion comparisons before committing any changes.
* **Automated Data Transformers**: Bi-directional domain translation, database table prefix switching (`wp_` ⟷ `wpxi_`), and standalone media library synchronization with MD5 hash deduplication.
* **Persistent Remote Code Deployer**: Safely author and deploy custom PHP nodes into `wp-content/skaaa-custom-nodes/`, immune to plugin updates and protected by syntax tokenizers.
* **AI Copilot & Logic Nodes**: Native nodes connecting Google Gemini and OpenAI models directly into Skaaa Logic DAG workflows.

---

## ⚡ Why Skaaa? (The Paradigm Shift)

| Feature / Metric | Traditional WordPress (Elementor / Divi) | Skaaa No-Code Ecosystem |
| :--- | :--- | :--- |
| **Database Architecture** | Cluttered `wp_postmeta` (EAV anti-pattern) | **Flat MySQL Tables (`skaaa_data_*`)** with Native JSON |
| **CSS Overhead** | 200KB - 800KB bulky CSS bundles & inline styles | **Zero CSS Bloat**; Offline Tailwind v4 JIT compiles only used classes |
| **DOM Structure** | Deep nested wrappers (10+ levels of `div` soup) | **Flat DOM**; Clean semantic HTML with zero wrapper bloat |
| **External Dependencies**| Third-party CDNs, external Google Fonts scripts | **100% Offline-First**; Zero runtime CDN reliance |
| **Client Interactivity** | Heavy jQuery plugins or massive React runtimes | **Skaaapine (Alpine.js)**; Lightweight, reactive micro-states |
| **Deployment / Sync**   | Complex SQL search-replace, manual migrations | **1-Click Skaaai Sync Bridge** with True Mirror Parity |
| **AI Integration**      | External third-party wrappers | **AI-Native Pair Programming** & Built-in DAG AI Nodes |

---

## 🛠️ How It Works (4-Step Builder Flow)

```
  [ 1. Model Data ]         [ 2. Design UI ]          [ 3. Wire Logic ]         [ 4. Deploy & Sync ]
   Skaaa Data Pro       ➔  Skaaa No-Code Design  ➔    Skaaa Logic Engine   ➔          Skaaai
  Create flat tables       Assemble atomic blocks     Connect DAG workflows      1-Click Mirror Sync
  & JSON relationships     with offline Tailwind      with SkaaaFX DSL           Localhost ⟷ Production
```

1. **Model Data (Skaaa Data Pro)**: Create custom database models with flat MySQL tables, define fields (text, number, relation, media, JSON), and input data via the interactive DataGrid.
2. **Design UI (Skaaa No-Code Design + Canvas)**: Use Gutenberg to drag-and-drop atomic blocks on a blank canvas. Style components using offline Tailwind v4 classes with instant Dark/Light mode preview.
3. **Wire Logic (Skaaa Logic Engine)**: Create visual DAG workflows triggered by form submissions, webhooks, or user events. Process data using SkaaaFX expressions and automated nodes.
4. **Deploy & Sync (Skaaai)**: Inspect pre-flight diffs and sync pages, database rows, organisms, and media directly from your local development environment to your live production server in one click.

---

## 📦 Installation & Getting Started

### Method 1: All-in-One Distribution (Recommended for Builders)

1. Download the latest release package (`skaaa-nocode-ecosystem.zip` or individual module zips) from [GitHub Releases](https://github.com/chiconcota/skaaa-nocode-ecosystem/releases).
2. In your WordPress Admin Dashboard:
   - Navigate to **Plugins ➔ Add New ➔ Upload Plugin** and install:
     1. `skaaa-data-pro.zip`
     2. `skaaa-logic-engine.zip`
     3. `skaaa-no-code-design.zip`
     4. `skaaai.zip`
   - Navigate to **Appearance ➔ Themes ➔ Add New ➔ Upload Theme** and install:
     - `skaaa-canvas.zip`
3. Activate the components in the recommended sequence:
   - **Step 1:** Activate `Skaaa Data Pro`
   - **Step 2:** Activate `Skaaa Logic Engine`
   - **Step 3:** Activate `Skaaa No-Code Design`
   - **Step 4:** Activate `Skaaai`
   - **Step 5:** Activate `Skaaa Canvas` as your active site theme.

---

### Method 2: Developer Setup (From Source)

```bash
# 1. Clone the repository into your WordPress development root
git clone git@github.com:chiconcota/skaaa-nocode-ecosystem.git
cd skaaa-nocode-ecosystem

# 2. Build assets for active frontend modules
cd wp-content/plugins/skaaa-no-code-design && npm install && npm run build
cd ../skaaa-data-pro && npm install && npm run build
cd ../skaaa-logic-engine && npm install && npm run build

# 3. Create production distribution archives
node wp-content/plugins/zip-all.js
```

---

## 🤖 AI-Native Pair Programming (Vibecoding Ready)

The Skaaa No-Code Ecosystem is architected specifically for **Human-AI Pair Programming (Vibecoding)**. The repository includes an integrated governance and long-term memory system:

```text
.
├── .agent/                       # AI Execution Cockpit
│   ├── rules/                    # Architectural constraints (PHP 8.2, WPCS, Flat Tables)
│   ├── skills/                   # Antigravity CLI skills (/start_session, /end_session, /release-github)
│   ├── workflows/                # Guided step-by-step procedures
│   └── harness/                  # Dedicated developer CLI inspection tools
│
└── .skaaa-ai/                    # 4-Drawer Long-Term Memory (Zero-Trash Policy)
    ├── 1-overview/               # System map, project roadmaps, architecture blueprints
    ├── 2-memory/                 # Decision logs, checkpoints, self-improvement notes
    ├── 3-ecosystem/              # Local architecture documents for each plugin & theme
    └── 4-rules/                  # System conventions and coding guidelines
```

### Slash Commands & Skills
* `/start_session`: Restores session context, validates Git branches, and isolates target plugin scope.
* `/end_session`: Reconciles architectural memory, logs major decisions, and verifies clean commit states.
* `/release-github`: Automates SemVer validation, changelog extraction, and ZIP package distribution.
* `/debug-workflow`: Executes surgical 5-step root cause analysis (RCA) and non-invasive debugging.

---

## 📄 Technical Standards & License

* **Language & Runtime**: PHP 8.2+ strictly typed properties, match expressions, and PSR-4 autoloading.
* **Database**: MySQL 8.0+ with native JSON storage engines and prepared statements (`$wpdb->prepare`).
* **Security**: Nonce verification (`wp_verify_nonce`, `check_ajax_referer`), capability checks (`current_user_can`), and strict output sanitization/escaping (`esc_html`, `esc_attr`, `esc_url`).
* **License**: Open-source software licensed under the [GNU General Public License v3.0 (GPLv3)](LICENSE).

---

<p align="center">
  <b>Built with ❤️ by the Skaaa Team for the Next Generation of Visual Creators.</b>
</p>
