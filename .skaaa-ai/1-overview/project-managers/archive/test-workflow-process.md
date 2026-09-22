# E2E Test Workflow: Skaaa Organisms Categorization & Folder Management (Phase 1.0.4 / 1.0.5)

> [!NOTE]
> This document details the E2E testing scenarios for verifying the Skaaa Organisms Categorization and Folder Management feature. It covers flat database column integration, category CRUD operations, UI interaction within the Workspace Panel, physical cache updates, and dynamic optgroup rendering inside the Gutenberg Editor.

## Objectives
1. **Flat Database Schema Verification:** Confirm the `category` column exists in the flat table `wp_skaaa_data_sys_organisms` and aligns with the database dictionary registry.
2. **Category Management & Sidebar CRUD:** Validate creating, reading, and deleting categories in the Sidebar, ensuring count badges react in real-time.
3. **Symbol Movement & Safe Cascading Deletion:** Ensure symbols can be moved between categories and verify that deleting a category safely cascades its child symbols back to the "Uncategorized" pool.
4. **Physical Cache & Global State Synchronization:** Verify updates in UI immediately sync with the physical JSON cache file and JS state `window.skaaaOrganismsCache`.
5. **Gutenberg Select Element Optgroup Rendering:** Verify that the dropdown select inside the Gutenberg Editor correctly clusters symbols into categorized optgroups.

---

## 🧪 Test Cases

### Test Case 1: Schema Integrity & Dynamic Cache Synchronization
**Status:** `[x] done`

**Steps:**
1. Connect to the site's MySQL database using a GUI tool (like phpMyAdmin) or CLI:
   ```sql
   DESCRIBE wp_skaaa_data_sys_organisms;
   ```
2. Verify the existence of the `category` column.
3. Go to **Skaaa Data Pro** -> **Site Management** -> **Data Dictionary** (or dump option `skaaa_data_dictionary`) and confirm the `category` field is registered in the organism table schema.
4. In WP Admin -> **Skaaa Builder**, open the workspace panel, create a test symbol, and assign it to a category.
5. Check the physical JSON cache file typically stored in:
   `wp-content/plugins/skaaa-no-code-design/assets/cache/organisms-cache.json` (or enqueued cache folder).
6. Open any Page Editor, check the browser console (F12) and type `window.skaaaOrganismsCache`.

**Expected Results:**
- The `category` column exists in `wp_skaaa_data_sys_organisms` as `varchar(255) DEFAULT NULL`.
- The data dictionary registers `category` under the schema of `skaaa_data_sys_organisms`.
- The physical JSON cache file contains the updated symbol object with the correct `"category": "your-category-slug"` entry.
- `window.skaaaOrganismsCache` displays the exact same structured array including the `category` field for every organism.

---

### Test Case 2: Workspace Panel Category CRUD & Interactive Sidebar
**Status:** `[x] Done`

**Steps:**
1. Navigate to **Skaaa Builder** workspace dashboard.
2. Observe the Sidebar on the left of the Organism list. There should be default options: **All** and **Uncategorized**.
3. Locate the **"Create Category"** input field (or "+" icon) in the Sidebar. Type `Header Layouts` and press Enter or click Add.
4. Click on the newly created `Header Layouts` category in the Sidebar. The right Grid should be empty, and the Badge count for `Header Layouts` in the Sidebar must be `0`.
5. Click back on the **Uncategorized** category to view existing uncategorized symbols.
6. Hover over a symbol, click its kebab/options menu, select **"Move to Category"**, and select `Header Layouts`.
7. Verify the badge counts:
   - `Header Layouts` count badge should increase to `1`.
   - `Uncategorized` count badge should decrease by `1`.
   - **All** count badge must remain unchanged.
8. Click on `Header Layouts` in the Sidebar and click the **Delete (Trash)** icon next to its name.
9. Confirm the deletion popup.

**Expected Results:**
- Creating a category adds it to the Sidebar instantly with a count of `0`.
- Moving a symbol changes its category value in the database, updates the cache, and adjusts the Sidebar counts in real-time.
- Deleting the category `Header Layouts` removes it from the Sidebar, and the symbol previously assigned to it automatically reverts back to **"Uncategorized"** (safely preserved). Badge counts recalculate correctly.

---

### Test Case 3: Gutenberg Selector Optgroup Grouping
**Status:** `[x] Done`

**Steps (Gutenberg Editor):**
1. Create a page template or edit a post in Gutenberg.
2. Add the **Skaaa Organism Reference** (or **Skaaa Symbol Ref**) block to the canvas.
3. Select the block, look at the block settings in the right Inspector panel, and locate the **"Select Organism"** dropdown list.
4. Click to open the dropdown menu.

**Expected Results:**
- The dropdown options are grouped under `<optgroup>` labels matching your categories (e.g., `<optgroup label="Header Layouts">`, `<optgroup label="Uncategorized">`).
- All symbols are displayed under their corresponding group. If they have no category, they must appear under the "Uncategorized" group.
- Selecting any symbol renders it on the editor canvas successfully without JavaScript errors in the console.

---

## 🛠 Troubleshooting Guide

| Symptom | Potential Cause | Remedial Action |
| :--- | :--- | :--- |
| **Category column missing from table** | Migration script did not trigger because the plugin version option was already set. | Delete the option `skaaa_data_pro_db_version` in `wp_options` using WP-CLI `wp option delete skaaa_data_pro_db_version` and refresh the admin page to trigger migration. |
| **New category does not appear in Sidebar** | API request failed or Local state didn't react. | Inspect the browser DevTools Network tab for failed `POST` requests to `/wp-json/skaaaaa-builder/v1/categories`. Ensure credentials and nonces are correct. |
| **Dropdown does not show optgroup groupings** | JS cache was not updated or `edit.js` wasn't built. | Clear browser cache, or re-run `npm run build` inside the `skaaa-no-code-design` plugin directory. Check if `window.skaaaOrganismsCache` has correct `category` properties. |

