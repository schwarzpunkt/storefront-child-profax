# Shop-Integration in profax.com – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Der Storefront-Child-Shop übernimmt Header, Footer und Grundtypografie von www.profaxonline.com, sodass der Wechsel Hauptseite ↔ Shop nicht sichtbar ist.

**Architecture:** Eigene `header.php`/`footer.php` im Child-Theme mit dem Markup der Hauptseite, gespeist aus vier WordPress-Menü-Positionen. Die relevanten CSS-Regeln der Hauptseite werden in `assets/css/profax-site.css` übernommen und auf `.profax-site-header` / `.profax-site-footer` eingegrenzt. Storefront-Farben werden per `theme_mod_*`-Filter gesetzt, Schriften (zimi, zimiicons) liegen im Theme.

**Tech Stack:** WordPress, Storefront 4.6.2, WooCommerce, PHP 8.5 (Local), Vanilla JS, Bash/curl-Smoke-Tests, playwright-cli für Screenshots.

**Spec:** `docs/superpowers/specs/2026-10-19-profax-site-integration-design.md`

## Global Constraints

- Lokale Site: `http://shop.profax.local`; PHP: `~/Library/Application Support/Local/lightning-services/php-*/bin/darwin-*/bin/php`; WP-CLI siehe Skill `wp-cli-local` (Run-ID `R755D7dIU`).
- **Keine Datenbank-Änderungen durch den Agenten.** Backend-Schritte werden Jérôme als Anleitung übergeben (Task 4).
- Keine CSS/JS-Dateien von profaxonline.com zur Laufzeit laden. Kein jQuery/t5 im eigenen JS.
- Vorlage-CSS (zum Abschreiben, nicht einbinden): `https://www.profaxonline.com/main/common.css`, `/backend/php/template.css`, `/backend/php/zeitgeist.css`. Aktive Regeln sind die `.zg …`-Regeln in `zeitgeist.css` (Header/Footer ab Z. ~1365, Breakpoints `max-width: 49rem`, `min-width: 35rem / 42rem / 49rem / 57rem / 65rem`). Dark-Mode-Regeln (`prefers-color-scheme: dark`) und `nav#lang` **nicht** übernehmen.
- Farben: Header/Footer `#50A8FF`; Header-Links `#FFF`, Hover `#000`; `.subLog`-Links `#404040`, Hover `#FFF`; `hlRed` `#E00000`, Hover `#000`; aktiv `font-weight: bold; color: #002080`, Hover `#000`; Body `#F8F8F8`/`#000`; Links `#3088E0`, Hover `#40D0FF`; Buttons `#1088FF`/`#FFF`, Radius 4px, Schatten `rgba(0,0,0,.75) 0 4px 4px -4px`.
- Schrift: `font-family: zimi, zimiicons, sans-serif; font-size: 14px; line-height: 1.4`. Glyphen: Warenkorb `\f07a`, Hamburger `\f0c9`.
- `PROFAX_MAIN_URL` = `https://www.profaxonline.com/dech/`.
- Menü-Positionen: `profax_header_main` („Header: Hauptnavigation“), `profax_header_login` („Header: Login“), `profax_footer_help` („Footer: Hilfe“), `profax_footer_about` („Footer: Über profax“).
- Code-Stil wie `functions.php`: Tabs, Präfix `profax_`, Kommentare sparsam, Escaping mit `esc_url`/`esc_html`/`esc_attr`.

## Review Focus

1. **Menü-Position ohne zugewiesenes Menü** (frische Installation, vor den Backend-Schritten): Header/Footer rendern ohne PHP-Warnung, leere Bereiche statt Fatal Error. → Test in Task 2 und 3 (Smoke-Test läuft ohne zugewiesene Menüs).
2. **Leerer Warenkorb vs. gefüllter Warenkorb:** Zähler bei 0 ausgeblendet, nach AJAX-Add-to-Cart aktualisiert ohne Neuladen. → Fragment-Test in Task 2, Browser-Test in Task 5.
3. **„Shop“-Aktivmarkierung auf Unterseiten** (Produkt, Warenkorb, Kasse, Mein Konto, Blog) und **nicht** auf Links zur Hauptseite. → Test in Task 4.
4. **Schmale Viewports (390 px)**: Hamburger öffnet/schliesst, Warenkorb bleibt sichtbar, kein horizontales Scrollen. → Task 5.
5. **Storefront-Sticky-Add-to-Cart und Plugin-Ausgaben** hängen an `storefront_after_footer` / `wp_footer` und müssen weiter erscheinen. → Test in Task 3.

---

### Task 1: Schriften, Grundtypografie, Storefront-Farben, Smoke-Test-Gerüst

**Files:**
- Create: `tests/smoke.sh`
- Create: `assets/fonts/{zimilight,zimidemibold,zimiicons}.{woff2,woff}`
- Create: `assets/css/profax-site.css`
- Modify: `functions.php` (Enqueue + `theme_mod`-Filter, neuer Abschnitt am Dateiende)
- Modify: `style.css:15-37` (Base64-`@font-face` und `* { font-family: profax }` entfernen), `style.css:393-399` und alle weiteren `font-family: profax`

**Interfaces:**
- Produces: `tests/smoke.sh` mit Helfern `get <path>` (curl, gibt HTML aus, Abbruch bei ≠200), `assert_contains <haystack> <needle> <msg>`, `assert_not_contains …`, `assert_matches <haystack> <regex> <msg>` (grep -E); am Ende `echo "ALL PASS"`, Exit-Code ≠0 bei Fehler. Basis-URL `SHOP=${SHOP_URL:-http://shop.profax.local}`. Prüft zusätzlich `php -l` auf alle `*.php` des Themes (Local-PHP-Pfad wie in Global Constraints).
- Produces: Stylesheet-Handle `profax-site` (abhängig von `storefront-child-style`), Konstante `PROFAX_THEME_VERSION` (= `wp_get_theme()->get('Version')`) für Cache-Busting.

- [ ] **Step 1: Smoke-Test schreiben** – `tests/smoke.sh` mit Abschnitt „Task 1“:
  - `get /` enthält `id=['"]profax-site-css` (Regex, quote-unabhängig)
  - `get /assets…`: `curl -o /dev/null -w %{http_code}` auf `$SHOP/wp-content/themes/storefront-child-profax/assets/fonts/zimilight.woff2` und `zimiicons.woff2` → `200`
  - `style.css` (lokal gelesen) enthält nicht `base64` und nicht `font-family: profax`
  - Inline-Storefront-CSS in `get /` enthält `#1088ff` (Button) und `#50a8ff` (Header-Hintergrund) (Storefront gibt Kleinbuchstaben aus; case-insensitive prüfen)
- [ ] **Step 2: Test laufen lassen** – `bash tests/smoke.sh` → FAIL bei `profax-site-css`.
- [ ] **Step 3: Schriften ablegen** – die 6 Dateien von `https://www.profaxonline.com/main/plc/fonts/` nach `assets/fonts/` laden (`curl -sf`), mit `file` prüfen (Web Open Font Format).
- [ ] **Step 4: `assets/css/profax-site.css` anlegen** – `@font-face` für `zimi` (normal → zimilight, bold → zimidemibold) und `zimiicons`, je woff2+woff, `font-display: block` (wie Hauptseite). Grundregeln: `body, button, input, select, textarea, label` → Schrift aus Global Constraints; `body` Hintergrund/Text; `a`/`a:hover` Farben. Abschnitts-Kommentare `/* fonts */`, `/* base */`, `/* header */`, `/* footer */` als Ankerpunkte für Task 2/3.
- [ ] **Step 5: Enqueue in `functions.php`** – `profax_enqueue_assets()` an `wp_enqueue_scripts`, Priorität 20 (nach Storefront), registriert `profax-site` mit `get_stylesheet_directory_uri() . '/assets/css/profax-site.css'`.
- [ ] **Step 6: Storefront-Farben** – `profax_theme_mod_colors()`: Map `theme_mod => Wert` und je `add_filter( 'theme_mod_' . $key, fn() => $value )`. Werte: `storefront_header_background_color` `#50A8FF`, `storefront_header_text_color` `#FFFFFF`, `storefront_header_link_color` `#FFFFFF`, `storefront_footer_background_color` `#50A8FF`, `storefront_footer_heading_color` `#FFFFFF`, `storefront_footer_text_color` `#FFFFFF`, `storefront_footer_link_color` `#FFFFFF`, `storefront_text_color` `#000000`, `storefront_heading_color` `#000000`, `storefront_accent_color` `#3088E0`, `storefront_button_background_color` `#1088FF`, `storefront_button_text_color` `#FFFFFF`, `storefront_button_alt_background_color` `#1088FF`, `storefront_button_alt_text_color` `#FFFFFF`, `background_color` `F8F8F8` (ohne `#`, WordPress-Core-Format).
- [ ] **Step 7: `style.css` bereinigen** – beide Base64-`@font-face` und die `font-family`-Zeile im `*`-Block entfernen (Hyphens/Smoothing bleiben); übrige `font-family: profax ! important` (Placeholder-Regeln Z. ~393–399) auf `zimi, zimiicons, sans-serif` umstellen. `BODY { font-size: 1.25em; line-height: 1.25 }` (Z. ~39) entfernen – Grundgrösse kommt aus `profax-site.css`. Theme-Version im Header auf `1.3` erhöhen.
- [ ] **Step 8: Test laufen lassen** – `bash tests/smoke.sh` → `ALL PASS`.
- [ ] **Step 9: Sichtprüfung** – `playwright-cli` Screenshot von `/shop/` bei 1280 px; Text in zimi (Schriftzug sichtbar anders als vorher), keine Fallback-Serifenschrift.
- [ ] **Step 10: Commit** – `git add tests/smoke.sh assets functions.php style.css && git commit -m "Switch shop to zimi font and profax.com base colors"`

---

### Task 2: Header

**Files:**
- Create: `header.php` (Kopie von `../storefront/header.php`, Storefront 4.6.2)
- Create: `assets/js/profax-nav.js`
- Modify: `functions.php` (Konstante, Menü-Positionen, Header-Funktionen, Hooks in `storefront_custom_logo()` aufräumen)
- Modify: `assets/css/profax-site.css` (Abschnitt `/* header */`)
- Modify: `style.css` (obsolete Regeln: `.site-header`, `HEADER.site-header`, `.profax_blue HEADER.site-header` (2×), `.main-navigation …`, `.site-header-cart …` (alle), `.storefront-primary-navigation`, `#masthead`, `#menu-productcategories …` (alle), `.header-widget-region`, `button.menu-toggle …` (alle), `.handheld-navigation …` (alle), `.wpdreams_asl_container …` nur falls die Suche nirgends sonst eingebunden ist – per `grep -r ajaxsearchlite` in `get /` prüfen)
- Test: `tests/smoke.sh`

**Interfaces:**
- Consumes: Handle `profax-site`, `PROFAX_THEME_VERSION` (Task 1)
- Produces:
  - `define( 'PROFAX_MAIN_URL', 'https://www.profaxonline.com/dech/' )`
  - `profax_menu_items( string $location ): array` – `WP_Post[]` der Menüeinträge der Position, `[]` wenn keine Zuweisung (nur Top-Level, `menu_item_parent == 0`)
  - `profax_is_shop_link( string $url ): bool` – true, wenn Host von `$url` == Host von `home_url()`
  - `profax_menu_link( WP_Post $item ): string` – `<a href class>` mit `$item->classes` (leere entfernt) + `profax-current`, wenn `profax_is_shop_link( $item->url )`; Titel escaped; `target`/`rel` aus Item übernehmen
  - `profax_cart_link(): string` – `<a class="profax-cart" href="wc_get_cart_url()" aria-label="Warenkorb">` + Glyph `&#xf07a;` + `<span class="profax-cart__count">n</span>` nur wenn `n > 0` (`WC()->cart->get_cart_contents_count()`)
  - `profax_site_header(): void` – gibt das Header-Markup aus (Spec „Header“)
  - Script-Handle `profax-nav` (Footer, `defer`)

- [ ] **Step 1: Smoke-Test erweitern** (Abschnitt „Task 2“, läuft ohne zugewiesene Menüs):
  - `get /` enthält `<header id="header" class="profax-site-header">`, `class="topnav"`, `class="subLog"`, `class="subNav"`, `class="home" href="https://www.profaxonline.com/dech/"`, `class="profax-cart"`, `id=['"]profax-nav-js` (Regex)
  - `get /` enthält nicht `id="masthead"`, nicht `site-header-cart`, nicht `storefront-primary-navigation`
  - `get /produkt/ersatzschieber-profax/` und `get /warenkorb/` enthalten `profax-site-header`
  - Fragment: `curl -s -X POST "$SHOP/?wc-ajax=get_refreshed_fragments"` enthält `a.profax-cart`
- [ ] **Step 2: Test laufen lassen** → FAIL bei `profax-site-header`.
- [ ] **Step 3: Menü-Positionen + Konstante** – `register_nav_menus()` an `after_setup_theme` mit den vier Positionen aus Global Constraints (Labels übersetzbar, Text-Domain `storefront-child-profax`).
- [ ] **Step 4: Helfer** – `profax_menu_items`, `profax_is_shop_link`, `profax_menu_link`, `profax_cart_link` wie in Interfaces. `profax_menu_items` via `get_nav_menu_locations()` + `wp_get_nav_menu_items()`; `false` → `[]`.
- [ ] **Step 5: `header.php`** – Storefront-Datei kopieren, Kopfkommentar „Override of Storefront 4.6.2 header.php“; den Block `<header id="masthead" …> … do_action( 'storefront_header' ) … </header>` ersetzen durch `<?php profax_site_header(); ?>`. Rest (inkl. `storefront_before_header`, `storefront_before_content`, `#content`-Wrapper) unverändert.
- [ ] **Step 6: `profax_site_header()`** – Markup gemäss Spec: `.subLog` = Links aus `profax_header_login` + `profax_cart_link()`; `.subNav` = Links aus `profax_header_main`; `a.home` mit Text `profax`; `a.icon` (`href="#"`, `aria-label="Menü"`, `aria-expanded="false"`, `aria-controls="menu1"`, Inhalt `&#xf0c9;`).
- [ ] **Step 7: Warenkorb-Fragment** – `add_filter( 'woocommerce_add_to_cart_fragments', … )`: `$fragments['a.profax-cart'] = profax_cart_link();`.
- [ ] **Step 8: Storefront-Header-Ausgaben entfernen** – in `storefront_custom_logo()`: die drei bestehenden `remove_action('storefront_header', …)` entfernen (Hook wird nicht mehr aufgerufen) und ergänzen: `remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 )`. Breadcrumb-Entfernung bleibt.
- [ ] **Step 9: `assets/js/profax-nav.js`** – Klick auf `.profax-site-header a.icon`: `preventDefault`, Klasse `responsive` auf `#menu1` togglen, `aria-expanded` setzen. IIFE, kein Global. Enqueue in `profax_enqueue_assets()` mit `[ 'in_footer' => true, 'strategy' => 'defer' ]`.
- [ ] **Step 10: Header-CSS** – im Abschnitt `/* header */` die `.zg #header`, `.zg #menu1 …`, `.zg .topnav …`, `.zg a.home`, `.zg .subLog/.subNav`, `.zg header li/ul`-Regeln aus `zeitgeist.css` inkl. der Breakpoint-Varianten übernehmen; Präfix `.zg` → `.profax-site-header`; Farben gemäss Global Constraints; zusätzlich `.profax-current` (aktiv-Stil) und `.profax-cart` / `.profax-cart__count` (Zähler: kleine Zahl hinter dem Icon, gleiche Farbe wie `.subLog`). Woher die Hamburger-Glyphe auf der Hauptseite kommt (JS oder CSS), vorher mit `playwright-cli` auf `https://www.profaxonline.com/dech/` bei 390 px prüfen (`getComputedStyle` / `textContent` von `#menu1 a.icon`) und gleich darstellen.
- [ ] **Step 11: `style.css` aufräumen** – die unter „Files“ genannten obsoleten Regeln entfernen.
- [ ] **Step 12: Test laufen lassen** → `ALL PASS`; `php -l` sauber; `wp-content/debug.log` ohne neue Einträge aus dem Theme.
- [ ] **Step 13: Commit** – `git commit -m "Replace Storefront header with profax.com header markup"`

---

### Task 3: Footer

**Files:**
- Create: `footer.php` (Kopie von `../storefront/footer.php`, Storefront 4.6.2)
- Modify: `functions.php` (Footer-Funktion; `remove_action('storefront_footer', 'storefront_credit', 20)` entfällt)
- Modify: `assets/css/profax-site.css` (Abschnitt `/* footer */`)
- Modify: `style.css` (obsolet: `FOOTER H3`, `footer`, `footer .col-full`, `.footer-widgets…` (alle), `footer .block`, `footer .widget-title`, `FOOTER .widget_nav_menu …` (alle), `footer li.menu-item a`)
- Test: `tests/smoke.sh`

**Interfaces:**
- Consumes: `profax_menu_items()`, `profax_menu_link()`, `PROFAX_MAIN_URL` (Task 2)
- Produces: `profax_menu_title( string $location ): string` (Name des zugewiesenen Menüs, `''` ohne Zuweisung), `profax_site_footer(): void`

- [ ] **Step 1: Smoke-Test erweitern** (Abschnitt „Task 3“):
  - `get /` enthält `<footer class="profax-site-footer">`, `class="fboxS"`, zweimal `class="f2"`
  - `get /` enthält nicht `id="colophon"`, nicht `footer-widgets`, nicht `storefront-handheld-footer-bar`
  - `get /produkt/ersatzschieber-profax/` enthält `storefront-sticky-add-to-cart` (Sticky-Bar via `storefront_after_footer` bleibt; falls im Customizer deaktiviert, stattdessen prüfen, dass `do_action( 'storefront_after_footer' )` in `footer.php` steht)
- [ ] **Step 2: Test laufen lassen** → FAIL bei `profax-site-footer`.
- [ ] **Step 3: `footer.php`** – Storefront-Datei kopieren, Kopfkommentar wie Task 2; `<footer id="colophon" …> … </footer>` ersetzen durch `<?php profax_site_footer(); ?>`. `storefront_before_footer`, `storefront_after_footer`, `wp_footer()` bleiben.
- [ ] **Step 4: `profax_menu_title()` + `profax_site_footer()`** – Markup gemäss Spec „Footer“: `<h3><a class="home" href="PROFAX_MAIN_URL">profaxonline</a></h3>`, pro Position `profax_footer_help` / `profax_footer_about` ein `.f2` mit `<h4>` = Menütitel (nur wenn nicht leer) und `<ul>` mit `<li>` + `profax_menu_link()`. Spalte ohne Menü: `.f2` leer ausgeben (Layout bleibt stabil).
- [ ] **Step 5: Footer-CSS** – `.zg footer …`, `.zg .fboxS`, `.zg .f2`, `.zg footer h3/h4`, `footer a`-Regeln (inkl. Breakpoints, `.zg footer a.home` Z. ~1574 und Responsive-Varianten) aus `zeitgeist.css`/`template.css`; Präfix → `.profax-site-footer`. `.profax-current` im Footer ohne Aktiv-Stil (nur Header).
- [ ] **Step 6: `style.css` aufräumen** – obsolete Footer-Regeln entfernen; in `storefront_custom_logo()` die `storefront_credit`-Zeile entfernen.
- [ ] **Step 7: Test laufen lassen** → `ALL PASS`.
- [ ] **Step 8: Commit** – `git commit -m "Replace Storefront footer with profax.com footer markup"`

---

### Task 4: Menüs im Backend (Jérôme) und Inhaltstests

**Files:**
- Create: `docs/backend-menus.md` (Anleitung für lokal und Produktion)
- Modify: `tests/smoke.sh`

**Interfaces:**
- Consumes: Menü-Positionen (Task 2), `profax-current`, `hlRed`

- [ ] **Step 1: Anleitung schreiben** – `docs/backend-menus.md` mit den Backend-Schritten 1–6 aus der Spec, je Menü die exakten Einträge (Titel, URL, CSS-Klasse), Hinweis „Ansicht anpassen → CSS-Klassen“, Zuordnung zu den vier Positionen, Migrationsbefehl `wp search-replace 'www.profaxonline.com' 'www.profax.com' --dry-run` zuerst.
- [ ] **Step 2: Übergabe an Jérôme** – Anleitung im Chat verlinken und **warten**, bis er die Menüs lokal angelegt hat. Optional anbieten: ein WP-CLI-Skript, das er selbst ausführt.
- [ ] **Step 3: Smoke-Test erweitern** (Abschnitt „Task 4“, aktivierbar mit `MENUS=1`):
  - Für `/`, `/shop/`, `/produkt/ersatzschieber-profax/`, `/warenkorb/`, `/kasse/`, `/mein-konto/`: Header enthält genau einen Link mit `profax-current`, und dessen `href` beginnt mit `$SHOP`
  - `get /` enthält `hlRed` und den Text `Registrieren`
  - Footer enthält `<h4>Hilfe</h4>` und `<h4>Über profax</h4>`
  - Kein Header-Link auf `www.profaxonline.com` trägt `profax-current`
- [ ] **Step 4: Test laufen lassen** – `MENUS=1 bash tests/smoke.sh` → `ALL PASS`.
- [ ] **Step 5: Commit** – `git commit -m "Add backend menu guide and menu content checks"`

---

### Task 5: Visueller Abgleich, Doku

**Files:**
- Modify: `assets/css/profax-site.css` (Feinkorrekturen)
- Modify: `CLAUDE.md` (Architektur: `header.php`, `footer.php`, `assets/`, Menü-Positionen, `PROFAX_MAIN_URL`, `tests/smoke.sh`; veralteten Hinweis auf Cookie-Banner in `footer.php` entfernen)

- [ ] **Step 1: Screenshots** – mit `playwright-cli` (Ausgabe in `.playwright-cli/`) bei 1280×800 und 390×844: `https://www.profaxonline.com/dech/prices/` und Shop `/`, `/shop/`, `/produkt/ersatzschieber-profax/`, `/warenkorb/`, `/kasse/`, `/mein-konto/`. Header- und Footer-Ausschnitte nebeneinander vergleichen (Höhe, Abstände, Schriftgrösse, Farben, Logo).
- [ ] **Step 2: Interaktion prüfen** – 390 px: Hamburger öffnet/schliesst, `aria-expanded` wechselt, kein horizontales Scrollen (`document.documentElement.scrollWidth <= innerWidth`). Produktseite: „In den Warenkorb“ → Zähler erscheint ohne Reload.
- [ ] **Step 3: Abweichungen korrigieren** in `profax-site.css`, bis Header/Footer übereinstimmen; danach `bash tests/smoke.sh` und `MENUS=1 bash tests/smoke.sh` → `ALL PASS`.
- [ ] **Step 4: `CLAUDE.md` nachführen** (Datei ist in `.gitignore`, wird nicht committet).
- [ ] **Step 5: Commit** – `git commit -m "Fine-tune header/footer against profax.com and update docs"`
