# Skaaa No-Code Ecosystem
> A professional, high-performance, and AI-native web application builder built on WordPress.

Skaaa No-Code Ecosystem is a decoupled micro-architecture designed to transform WordPress from a traditional CMS into a production-ready, visual application builder. By utilizing flat database tables, modern frontend state management, dynamic server-side expressions, and a zero-legacy-bloat canvas, Skaaa provides a modern software development environment for both developers and AI assistants.

---

## 🗺️ Architectural Ecosystem

The ecosystem is split into **3 core plugins**, **1 AI addon**, and **1 clean canvas theme**, communicating asynchronously through WordPress hooks (`do_action` / `apply_filters`) to ensure strict isolation:

```
                  ┌─────────────────────────────────┐
                  │       Skaaa Canvas Theme        │
                  │     (Zero Legacy CSS Canvas)    │
                  └────────────────┬────────────────┘
                                   │
 ┌─────────────────────────────────┼────────────────────────────────────────┐
 │                      THE CORE TRINITY PLUGINS                            │
 ├───────────────────┬─────────────┴──────┬─────────────────────────────────┤
 │  Skaaa Data Pro   │ Skaaa Logic Engine │     Skaaa No-Code Design        │
 │  (Flat Database   │ (Event & Expression│  (Tailwind v4, Alpine v3, JIT,  │
 │  & REST APIs)     │   & Webhooks)      │      and html2tailwind)         │
 └───────────────────┘└──────────┬────────┘└────────────────────────────────┘
                                 │
                   ┌─────────────┴─────────────┐
                   │          Skaaai           │
                   │        (AI Addon)         │
                   └───────────────────────────┘
```

1. **Skaaa Canvas (Theme)**
   * Acts as a pure blank canvas by stripping out all default WordPress block library styles and global inline CSS styles.
   * Offers a clean slate for custom frontend application renders.

2. **Skaaa No-Code Design**
   * Handles visual block rendering (atomic container, lists, icons, forms, images, buttons).
   * Provides compilation bridges including translation maps (`html2tailwind` parser).
   * Bundles the **Skaaapine Engine** (using Alpine.js store and directives) for instant interactive state management.
   * Integrates an editor workspace and a **Tailwind v4 JIT** compiler engine to prevent inline style bloat.

3. **Skaaa Data Pro**
   * Abandons the slow and legacy `wp_postmeta` model.
   * Creates and queries high-performance custom flat tables (`skaaa_data_*`) using a built-in Schema Manager.
   * Exposes raw high-performance REST APIs (`Integration APIs`) to distribute layouts and data directly to external services.

4. **Skaaa Logic Engine**
   * Implements "The Trinity" event-driven flow: View (Events) ➔ Logic (Event pipelines) ➔ Data (Reads/Writes).
   * Evaluates dynamic code and data binding safely using the **SkaaaFX DSL** (Abstract Syntax Tree Parser).
   * Supports incoming event triggers via external **Webhooks**.

5. **Skaaai (AI Addon)**
   * Integrates Google Gemini and OpenAI APIs to bring AI Automation into logic flows.
   * Provides AI Prompt Node (`AIPromptNode`), Structured Data Parser Node (`AIParserNode`), and supports Agentic workflows.

---

## 🛠️ Getting Started

### Prerequisites
* **Local Development Environment**: LocalWP, Docker (ddev/lando), or a standard LAMP/LEMP stack with PHP 8.2+ and MySQL/MariaDB.
* **Node.js**: v18+ and npm v10+.
* **Git**: To track and manage changes.

### Installation Steps
1. Clone the repository into your WordPress development root:
   ```bash
   git clone git@github.com:chiconcota/skaaa-nocode-ecosystem.git
   ```
2. Symlink or copy the plugins and theme folders to their respective directories:
   * Plugins ➔ `wp-content/plugins/`
   * Theme ➔ `wp-content/themes/`

3. Navigate to each package directory to install build dependencies and compile frontend assets:

   ```bash
   # Skaaa No-Code Design
   cd wp-content/plugins/skaaa-no-code-design
   npm install
   npm run build

   # Skaaa Data Pro
   cd ../skaaa-data-pro
   npm install
   npm run build

   # Skaaa Logic Engine
   cd ../skaaa-logic-engine
   npm install
   npm run build
   ```

4. Log in to the WordPress Admin dashboard and activate the plugins and theme in the following order:
   1. **Skaaa Data Pro**
   2. **Skaaa Logic Engine**
   3. **Skaaa No-Code Design**
   4. **Skaaai** (Optional AI Addon)
   5. **Skaaa Canvas** (as the active theme)

---

## 🤖 AI-Native Collaboration (Developer Note)

This project is built to be **AI-Native**, allowing AI agents (like Cursor, Copilot, Gemini) to work on the codebase side-by-side with human developers without losing architectural context.

We maintain two directories at the workspace root to guide AI models:
* [`.skaaa-ai/`](file://./.skaaa-ai): Contains the System Map, Architectural Decision Log, and Phase Manager roadmaps.
* [`.agent/`](file://./.agent): Contains context files and formatting rules (`wp-architect.md`, `skaaa-nocode-system.md`, `skaaa-docs-management.md`) which prompt the LLM to follow strict clean-code boundaries, PHP 8.2 typing, secure WP standards, and i18n specifications.

> [!NOTE]
> **Always notify your AI assistant** to read the rules in `.agent/rules/` and update `system_map.md` & `decision-log.md` whenever committing code changes.

---

## 🤝 Contributing
We welcome contributions from the community! Whether you are fixing a bug, adding new features, or refining documentation, please check out our [Contributing Guidelines](file://./CONTRIBUTING.md) to get started.

---

## 📄 License
This project is open-source and licensed under the **GNU GPLv3 (General Public License version 3)**. See the [LICENSE](file://./LICENSE) file for the full legal text.
