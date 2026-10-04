# Skaaa No-Code Ecosystem (v2.4.7)

> **A High-Performance, Decoupled, and AI-Native Visual Application Platform for WordPress.**

[![License: GPL-3.0](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4_JIT-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-v3-8BC0D0?logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![React Flow](https://img.shields.io/badge/React_Flow-v11-FF0072)](https://reactflow.dev)

**Skaaa No-Code Ecosystem** transforms WordPress from a traditional blog CMS into a modern, enterprise-grade **Visual Application Builder**. Built from the ground up on modern architecture, Skaaa eliminates legacy technical debt (zero `wp_postmeta`, zero CSS/JS bloat, zero runtime CDN dependencies) while introducing a **Bidirectional Synchronization Bridge** and an **AI-Native Agent Harness**.

---

## 🗺️ System Architecture (Decoupled Microservices)

The ecosystem consists of **4 independent plugins** and **1 clean canvas theme**, strictly decoupled and communicating exclusively via WordPress Action/Filter hooks, secure REST APIs, and reactive Alpine.js stores:

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
      │  (Flat MySQL Tables, │ (DAG Visual Workflows, │ (15 Native Blocks,  │      │
      │   Native JSON &      │  SkaaaFX AST Engine,   │  Tailwind v4 JIT,   │      │
      │   Smart Objects)     │  Pluggable Nodes)      │  Skaaapine Engine)  │      │
      └──────────────────────┴───────────────┬────────┴─────────────────────┘      │
                                             │                                     │
      ┌──────────────────────────────────────┴─────────────────────────────────────┼────┐
      │                                                                            │    │
      │                           Skaaai (Bridge & Harness)                        │    │
      │  • Bidirectional Sync Bridge (Localhost ⟷ Production/Live)                 │    │
      │  • True Mirror Synchronization & Deep Pre-flight Diff Engine                │    │
      │  • Persistent Remote Code Deployer (wp-content/skaaa-custom-nodes/)        │    │
      │  • Local Agent Harness Initializer (CLI Tools: db-tool, block-tool, jit)   │    │
      │  • AI Copilot Automation Nodes (Gemini / OpenAI)                           │    │
      └────────────────────────────────────────────────────────────────────────────┴────┘
```

---

## 📦 Ecosystem Components

### 1. 🎨 Skaaa Canvas Theme (v1.0.1)
* **Zero Overhead**: Strips out 100% of WordPress default block library CSS (`.wp-block-library`), global inline styles, and theme bloat.
* **Blank Slate**: Provides an immaculate foundation for full-width landing pages and custom web applications.
* **Smart Admin Bar Offset**: Automatically calculates sticky/fixed navigation offsets (`top: 32px` desktop, `top: 46px` mobile) for logged-in users with zero `!important` declarations.

### 2. ⚡ Skaaa No-Code Design (v2.4.7)
* **15 Native Atomic Blocks**: Pure Flat DOM rendering with zero redundant wrappers: `Container`, `Text`, `Button`, `Image`, `Icon`, `Video`, `SVG`, `Code`, `Loop`, `List`, `List Item`, `Form Inputs`, and `Organism Ref`.
* **Zero-CDN Tailwind v4 JIT**: Dual compiler engine (PHP backend + SkaaaWind JS editor) running 100% offline with Single Source of Truth (`tailwind-rules.json`), achieving **100% Compiler Parity**.
* **Skaaapine Engine (Alpine.js v3)**: Dynamic, real-time client state management using global `Alpine.store`, complete scope isolation, and automatic hash-less `@click.prevent` event handling.
* **Prefix-Agnostic Loop**: Dynamic table prefix resolution in `Skaaa Loop` ensuring seamless execution across mismatched environments (`wp_` ⟷ `wpxi_`).
* **Design Token System**: Complete theme token engine supporting CSS variables, dark mode switching, and full-width edge-to-edge canvas layouts.

### 3. 💾 Skaaa Data Pro (v1.3.3)
* **No-Postmeta Rule**: Completely eliminates WordPress EAV anti-pattern (`wp_postmeta`). Automatically creates and manages high-performance flat MySQL tables (`skaaa_data_*`).
* **Native MySQL JSON Fields**: Stores relations, multi-select items, and metadata as native JSON columns, with automated enrichment into structured objects.
* **Smart Object Blueprint**: JSON-portable schema import/export engine with dynamic collision resolution and relation re-wiring.
* **Modular DataGrid**: Vite/ES6-powered inline editing interface with specialized cell strategies (Toggle, Media, Select, Text).

### 4. 🧠 Skaaa Logic Engine (v1.3.0)
* **DAG Visual Workflow Canvas**: Visual drag-and-drop workflow builder powered by React Flow v11, with seamless live toggling between Graph and JSON views.
* **SkaaaFX DSL & AST Evaluator**: Dedicated expression interpolation language evaluated via an Abstract Syntax Tree (AST), supporting context-aware autocomplete and safe execution.
* **Pluggable Nodes Framework**: Decentralized node registration mechanism allowing third-party plugins (like `Skaaai`) to add custom nodes without bundle modifications.
* **Asynchronous Workers**: Integrates Action Scheduler for background jobs and incoming Webhook pipelines.

### 5. 🚀 Skaaai: Bridge, Deployer & Harness (v1.5.2)
* **1-Click Bidirectional Sync Bridge (Localhost ⟷ Live Webhost)**:
  - **Push to Live & Safe Pull from Live**: Effortlessly synchronize pages, posts, media, design presets, organisms, theme templates, logic workflows, and database tables between environments.
  - **True Mirror Synchronization**: Complete row-level parity for flat tables (`skaaa_data_*`), pruning orphaned workflows/organisms, and safely trashing remote-deleted posts (`wp_trash_post`).
  - **Deep Pre-flight Diff Engine**: 4-state pre-flight review (`🟡 Modified`, `🔵 New`, `🗑️ Deleted on Live`, `⚪ Synced`) with interactive accordion preview.
  - **Reverse Transformers**: Automatic bi-directional URL domain rewriting, database table prefix switching, and base64 media sideloading.
  - **Revision-First Safe Overwrite**: Always backs up local WordPress revisions before overwriting, guaranteeing zero data loss.
* **Persistent Remote Code Deployer**:
  - Safely saves and executes custom PHP nodes in `wp-content/skaaa-custom-nodes/`, completely immune to plugin updates.
  - Syntax validator shield (Tokenizer) preventing White Screen of Death (WSoD).
* **Local Agent Harness Initializer**:
  - One-click deployment of the `.agent/` and `.skaaa-ai/` scaffolding for AI-assisted development.
  - **Developer & Designer CLI Tools**:
    - `db-tool.php`: Flat database inspector, token loader, and Theme Builder organism auto-registration.
    - `block-tool.php`: Static syntax validator for Gutenberg comments and Flat DOM rules.
    - `jit-tool.php`: Offline Tailwind v4 syntax checker and CSS compiler preview.
* **AI Automation Logic Nodes**:
  - `AIPromptNode` and `AIParserNode` connecting Google Gemini and OpenAI models into Skaaa Logic DAG graphs.

---

## 🚀 Quick Start & Installation

### Option A: Pre-built Distribution (Recommended for Users)
1. Download the latest release bundle from [GitHub Releases](https://github.com/chiconcota/skaaa-nocode-ecosystem/releases):
   - **All-in-one Bundle**: `skaaa-nocode-ecosystem-v2.4.7.zip`
   - Or individual plugin packages: `skaaai.zip`, `skaaa-no-code-design.zip`, `skaaa-data-pro.zip`, `skaaa-logic-engine.zip`, `skaaa-canvas.zip`.
2. In your WordPress Admin:
   - Upload and install plugins via **Plugins ➔ Add New ➔ Upload Plugin**.
   - Upload and install the theme via **Appearance ➔ Themes ➔ Add New ➔ Upload Theme**.
3. Activate the components in the following recommended order:
   1. `Skaaa Data Pro`
   2. `Skaaa Logic Engine`
   3. `Skaaa No-Code Design`
   4. `Skaaai`
   5. `Skaaa Canvas` (as active theme)

---

### Option B: Developer Setup (From Source)

```bash
# 1. Clone the repository into your WordPress development root
git clone git@github.com:chiconcota/skaaa-nocode-ecosystem.git
cd skaaa-nocode-ecosystem

# 2. Build frontend assets for each module
cd wp-content/plugins/skaaa-no-code-design && npm install && npm run build
cd ../skaaa-data-pro && npm install && npm run build
cd ../skaaa-logic-engine && npm install && npm run build

# 3. Create distribution archives (Optional)
node wp-content/plugins/zip-all.js
```

---

## 🤖 AI-Native Architecture & Vibecoding Protocol

The Skaaa No-Code Ecosystem is designed from the ground up for **Human-AI Pair Programming (Vibecoding)**. The repository includes a standardized two-tier governance structure:

```text
.
├── .agent/                       # Execution & Governance Cockpit
│   ├── rules/                    # Non-negotiable architectural directives (PHP 8.2, Flat Tables, Security)
│   ├── skills/                   # Antigravity CLI/IDE interactive skills (/start_session, /end_session, /release-github)
│   ├── workflows/                # Guided step-by-step procedures (client intake, assembly, deployment)
│   └── harness/                  # Dedicated CLI tools (db-tool, block-tool, jit-tool)
│
└── .skaaa-ai/                    # 4-Drawer Long-Term Memory (Zero-Trash Policy)
    ├── 1-overview/               # system_map.md, project roadmaps, brand guidelines
    ├── 2-memory/                 # decision-log.md, checkpoint.md, self-improve.md
    ├── 3-ecosystem/              # Local architecture docs for each plugin & theme
    └── 4-rules/                  # System conventions
```

### Slash Commands & Skills
- `/start_session`: Restores memory, checks Git branch, isolates active plugin context, and loads behavioral corrections.
- `/end_session`: Performs memory reconciliation, logs architectural decisions, updates checkpoints, and enforces clean commits.
- `/release-github`: Automates SemVer validation, changelog extraction, ZIP packaging, and Git release tagging.

---

## 📄 License & Standards

- **PHP Standards**: PHP 8.2+ strictly typed properties, match expressions, and PSR-4 autoloading.
- **WordPress Standards**: Nonce verification, strict output escaping (`esc_html`, `esc_attr`, `esc_url`), prepared SQL statements, and HPOS compatibility.
- **License**: Licensed under the [GNU General Public License v3.0 (GPLv3)](LICENSE).
