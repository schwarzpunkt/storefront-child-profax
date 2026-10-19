# Shop-Startseite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die Startseite (Seite 90) führt Besucher über zwei Geräte-Karten, Klassen-Pills, beliebte Lernhefte und aktuelle Beiträge zum passenden Lernsystem.

**Architecture:** Neues Template `front-page.php` im Child-Theme: gibt den Seiteninhalt von Seite 90 mit `the_content()` aus und ruft danach vier Ausgabe-Funktionen auf, die (Projektkonvention: alles in `functions.php`, keine Includes) in einem neuen Block „Front page“ am Ende von `functions.php` stehen. CSS in einem neuen Block „front page“ in `assets/css/profax-site.css`, Pills über den bestehenden Pill-Stil der Kategorie-Navigation.

**Tech Stack:** WordPress, Storefront 4.6.2 (Parent), WooCommerce, PHP, CSS; Tests: `tests/smoke.sh` (curl + grep, WP-CLI `eval`), Screenshots mit `playwright-cli`.

**Spec:** [docs/superpowers/specs/2026-10-19-shop-startseite-design.md](../specs/2026-10-19-shop-startseite-design.md)

## Global Constraints

- Daten über **Slugs** auflösen, nie über IDs: Produkte `profax-lerngeraet`, `profaxli-lerngeraet`; Kategorien `lernhefte-fuer-profax-lerngeraet`, `lernhefte-fuer-profaxli-lerngeraet`.
- Fehlt ein Produkt / eine Kategorie / sind keine Daten da: Karte, Link bzw. Abschnitt ohne Fehler und ohne PHP-Warnung weglassen.
- Stil nur aus bestehenden Tokens: zimi (inkl. 500), `#50A8FF`, `#1088FF`, `#3088E0`, Pill-Hintergrund `rgba(80,168,255,0.25)`, Inhaltsbox `#content .col-full` (max. 1020 px, 20 px Rand).
- Kein Dark Mode, keine neuen Plugins, keine Service-Zeile, keine Wiederholung von Suche/Fach-Pills, kein langer Fliesstext über den Geräte-Karten.
- Responsiv: gut bei 390 px und 1280 px, kein horizontales Scrollen.
- Backend-/DB-Änderungen nicht selbst ausführen; Anleitung in `docs/backend-menus.md`.
- Bestehende Smoke-Tests (`bash tests/smoke.sh`, `MENUS=1 bash tests/smoke.sh`) bleiben grün.

## Review Focus

1. **Live-DB mit anderen/fehlenden Slugs** → betroffene Karte bzw. Link fehlt, Rest der Seite rendert, keine Warnung. Test: Task 1, `wp eval` mit falschen Slugs.
2. **Keine empfohlenen Hefte** (Stern im Backend entfernt) → Abschnitt „Beliebte Lernhefte“ fehlt ganz (kein leerer Titel). Test: Task 3, `wp eval` mit nicht existierender Kategorie.
3. **WooCommerce deaktiviert** → Startseite darf nicht fatal enden; die Woo-abhängigen Abschnitte geben nichts aus. Test: Task 1/3, `wp eval --skip-plugins=woocommerce`.
4. **Beiträge ohne Auszug, mit langem Text oder Emoji** → Auszug aus dem Inhalt, höchstens 20 Wörter. Test: Task 4, Wortzahl jedes Auszugs ≤ 21 (inkl. „…“).
5. **Schmaler Bildschirm (390 px)** → Karten untereinander, Pills umbrechen, kein horizontales Scrollen. Test: Task 5, `document.documentElement.scrollWidth <= innerWidth`.

---

### Task 1: Template-Gerüst und Geräte-Karten

**Files:**
- Create: `front-page.php`
- Modify: `functions.php` (neuer Block „Front page“ am Dateiende)
- Modify: `assets/css/profax-site.css` (neuer Block „front page“ am Dateiende)
- Test: `tests/smoke.sh` (neuer Block „Front page“ vor Task 4/menus)

**Interfaces:**
- Produces:
  - `profax_front_devices(): array` – Konfiguration `[ ['product' => 'profax-lerngeraet', 'category' => 'lernhefte-fuer-profax-lerngeraet'], ['product' => 'profaxli-lerngeraet', 'category' => 'lernhefte-fuer-profaxli-lerngeraet'] ]`.
  - `profax_front_device_cards( array $devices ): void` – gibt `<section class="profax-front-devices">` mit je `<article class="profax-device">` aus; nichts, wenn keine Karte übrig bleibt oder WooCommerce fehlt.
  - `front-page.php` ruft in dieser Reihenfolge: Seiteninhalt, `profax_front_device_cards( profax_front_devices() )`, `profax_front_klassen()`, `profax_front_featured()`, `profax_front_news()` (die drei letzten entstehen in Task 2–4; jede Task ergänzt ihren Aufruf selbst).

- [ ] **Step 1: Failing tests in `tests/smoke.sh`**

```bash
# --- Front page ---
FRONT="$(sed -n '/<main id="main"/,/<\/main>/p' <<<"$HOME_HTML")"
assert_contains "$FRONT" '<h2>profax Lerngerät</h2>' "front page shows page content"
assert_contains "$FRONT" 'class="profax-front-devices"' "front page device cards"
assert_equals "$(grep -o 'class="profax-device"' <<<"$FRONT" | wc -l | tr -d ' ')" "2" "two device cards"
for url in /produkt/profax-lerngeraet/ /produkt/profaxli-lerngeraet/ /produktkategorie/lernhefte-fuer-profax-lerngeraet/ /produktkategorie/lernhefte-fuer-profaxli-lerngeraet/; do
	assert_contains "$FRONT" "href=\"$SHOP$url\"" "front page links to $url"
done
# robustness (needs WP-CLI, inside the existing WP-CLI if-block or an equivalent one)
MISSING="$(wp_eval 'profax_front_device_cards( array( array( "product" => "gibt-es-nicht", "category" => "lernhefte-fuer-profax-lerngeraet" ), array( "product" => "profaxli-lerngeraet", "category" => "gibt-es-nicht" ) ) );')"
assert_equals "$(grep -o 'class="profax-device"' <<<"$MISSING" | wc -l | tr -d ' ')" "1" "missing product drops its card"
assert_not_contains "$MISSING" 'Lernhefte ansehen' "missing category drops its link"
assert_not_contains "$MISSING" 'Warning' "no PHP warning for missing slugs"
```

`wp_eval` is a small helper extracted from the existing WP-CLI call (same `$PHP`, `$SOCK`, `$WP_PHAR`, `--path`); the existing “header renders with WooCommerce inactive” check is rewritten to use it, with extra args passed through (`wp_eval --skip-plugins=woocommerce '…'`). Extend that WooCommerce-inactive check to also call `profax_front_device_cards( profax_front_devices() );` and assert `HEADER-OK` still appears.

- [ ] **Step 2: Run `bash tests/smoke.sh`** – Expected: the new front page checks FAIL, everything else passes.

- [ ] **Step 3: Implement**
  - `front-page.php`: like Storefront `page.php` (`#primary.content-area > main#main.site-main`, loop), but per post: `<article id="post-ID" <?php post_class(); ?>>` with `<h1 class="screen-reader-text">` (page title; the visible Storefront header with the 1024 px logo is dropped), `<div class="entry-content"><?php the_content(); ?></div>`. No comments, no `storefront_sidebar` (page is full width). Then the section calls.
  - `profax_front_device_cards()`: product via `get_page_by_path( $slug, OBJECT, 'product' )` + `wc_get_product()`, only `publish`; category via `get_term_by( 'slug', …, 'product_cat' )`. Card: link-wrapped image `$product->get_image( 'shop_catalog' )` (same size as the catalog), `<h2 class="profax-device__title">` name, `<p>` with `wp_strip_all_tags( $product->get_short_description() )`, then `<p class="profax-device__links">` with `a.button` „Gerät ansehen“ (`get_permalink`) and `a.profax-device__more` „Lernhefte ansehen“ (`get_term_link`, only if term found and not `WP_Error`). Return early if `! function_exists( 'wc_get_product' )`.
  - CSS: section margin `2em 0`; grid `1fr` below 42rem, `1fr 1fr` gap 20px from 42rem; card white background, border `1px solid rgba(80,168,255,0.35)` (like the search field), radius 8px, padding 1.25em; image centered, max-height 220px, no shadow; title `font-weight: 500`, margin-top 0.5em.

- [ ] **Step 4: Run `bash tests/smoke.sh`** – Expected: ALL PASS.

- [ ] **Step 5: Commit** `git add front-page.php functions.php assets/css/profax-site.css tests/smoke.sh && git commit -m "Add front page template with device cards"`

### Task 2: Lernhefte nach Klasse

**Files:** Modify `functions.php`, `front-page.php`, `assets/css/profax-site.css`, `tests/smoke.sh`

**Interfaces:**
- Produces:
  - `profax_klasse_terms(): WP_Term[]` – `pa_klasse` terms with `count > 0`, sorted: `vorschule` first, numeric slugs ascending, other names alphabetically, `if` last.
  - `profax_klasse_label( WP_Term $term ): string` – „Vorschule“ → `Vorschule`, numeric `n` → `n. Klasse`, `if` → `IF`, sonst `$term->name`.
  - `profax_front_klassen(): void` – `<section class="profax-front-klassen">` with `<h2>Lernhefte nach Klasse</h2>` and `<ul class="profax-pills">`; link `add_query_arg( 'filter_klasse', $term->slug, get_permalink( wc_get_page_id( 'shop' ) ) )`. Nothing if no terms or taxonomy missing.

- [ ] **Step 1: Failing tests**

```bash
KLASSEN="$(sed -n '/class="profax-front-klassen"/,/<\/section>/p' <<<"$FRONT")"
assert_contains "$KLASSEN" '<h2>Lernhefte nach Klasse</h2>' "class row title"
assert_equals "$(grep -oE '<a [^>]*>[^<]+</a>' <<<"$KLASSEN" | sed -E 's/<[^>]+>//g' | paste -sd'|' -)" "Vorschule|1. Klasse|2. Klasse|3. Klasse|4. Klasse|5. Klasse|6. Klasse|7. Klasse|8. Klasse|9. Klasse|IF" "class pills order and labels (empty values hidden)"
assert_contains "$KLASSEN" "href=\"$SHOP/shop/?filter_klasse=vorschule\"" "class pill links to filtered shop"
assert_equals "$(curl -s "$SHOP/shop/?filter_klasse=if" | grep -c 'class="product type-product')" "12" "IF filter returns products"
```

- [ ] **Step 2: Run** – Expected: new checks FAIL.
- [ ] **Step 3: Implement** functions above; add call to `front-page.php`. CSS: extend the existing pill selectors in the shop-nav block with `.profax-pills a` (and hover/focus) instead of copying the rules; `.profax-pills` is `display:flex; flex-wrap:wrap; gap:0.5em; list-style:none; margin:0` (wraps on mobile, no scroll row). Section title like the other front page h2.
- [ ] **Step 4: Run** – Expected: ALL PASS.
- [ ] **Step 5: Commit** `"Add class pills to front page"`

### Task 3: Beliebte Lernhefte

**Files:** Modify `functions.php`, `front-page.php`, `tests/smoke.sh` (CSS only if needed)

**Interfaces:**
- Produces:
  - `profax_front_featured_ids( array $categories, int $limit = 4 ): int[]` – `wc_get_products( [ 'featured' => true, 'category' => $categories, 'status' => 'publish', 'visibility' => 'catalog', 'limit' => $limit, 'orderby' => 'rand', 'return' => 'ids' ] )`; `[]` without WooCommerce.
  - `profax_front_featured(): void` – with the two Lernhefte slugs; if ids empty, output nothing; else `<section class="profax-front-featured"><h2>Beliebte Lernhefte</h2>` + `do_shortcode( '[products ids="…" orderby="post__in" columns="4"]' )` (normal catalog grid `ul.products`).

- [ ] **Step 1: Failing tests**

```bash
FEATURED="$(sed -n '/class="profax-front-featured"/,/<\/section>/p' <<<"$FRONT")"
assert_contains "$FEATURED" '<h2>Beliebte Lernhefte</h2>' "featured section title"
assert_equals "$(grep -o '<li class="product type-product' <<<"$FEATURED" | wc -l | tr -d ' ')" "4" "four featured products"
assert_equals "$(grep -o '<li class="product type-product[^"]*' <<<"$FEATURED" | grep -c ' featured ')" "4" "all shown products are featured"
assert_equals "$(grep -o '<li class="product type-product[^"]*' <<<"$FEATURED" | grep -cE 'product_cat-lernhefte-fuer-profax(li)?-lerngeraet')" "4" "featured products are Lernhefte"
NONE="$(wp_eval 'var_dump( profax_front_featured_ids( array( "gibt-es-nicht" ) ) );')"
assert_contains "$NONE" 'array(0)' "no featured products -> empty list"
```

and in the WooCommerce-inactive check also call `profax_front_featured(); profax_front_klassen();`.

- [ ] **Step 2: Run** – Expected: new checks FAIL.
- [ ] **Step 3: Implement**; add call to `front-page.php`.
- [ ] **Step 4: Run** – Expected: ALL PASS.
- [ ] **Step 5: Commit** `"Add featured Lernhefte to front page"`

### Task 4: Aktuelles

**Files:** Modify `functions.php`, `front-page.php`, `assets/css/profax-site.css`, `tests/smoke.sh`

**Interfaces:**
- Produces: `profax_front_news(): void` – `WP_Query( [ 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ] )`; `<section class="profax-front-news"><h2>Aktuelles</h2><div class="profax-news">` with per post `<article class="profax-news__item">`: `<time datetime="c">` `get_the_date()`, `<h3><a href=permalink>` title, `<p>` `wp_trim_words( get_the_excerpt(), 20 )`; then `<p><a class="profax-news__all" href=…>Alle Beiträge</a></p>` only if `page_for_posts` is set. Nothing if no posts. `wp_reset_postdata()`.

- [ ] **Step 1: Failing tests**

```bash
NEWS="$(sed -n '/class="profax-front-news"/,/<\/section>/p' <<<"$FRONT")"
assert_contains "$NEWS" '<h2>Aktuelles</h2>' "news section title"
assert_equals "$(grep -o 'class="profax-news__item"' <<<"$NEWS" | wc -l | tr -d ' ')" "3" "three news cards"
assert_contains "$NEWS" "href=\"$SHOP/blog/\"" "link to all posts"
assert_not_contains "$FRONT" 'display-posts-listing' "old post list gone"   # only after Jérôme edits page 90, so: MENUS=1 block
LONGEST="$(sed -n '/class="profax-news__item"/,/<\/article>/p' <<<"$NEWS" | grep -A1 '<p' | sed -E 's/<[^>]+>//g' | awk '{print NF}' | sort -n | tail -1)"
[ "${LONGEST:-99}" -le 21 ] && pass "news excerpts ≤ 20 words" || fail "news excerpts ≤ 20 words (got $LONGEST)"
```

The `display-posts-listing` check goes into the `MENUS=1` block (it depends on a backend change, like the menu checks); rename that block's comment to “backend changes (docs/backend-menus.md)”.

- [ ] **Step 2: Run** – Expected: new checks FAIL.
- [ ] **Step 3: Implement**; CSS: `.profax-news` grid `1fr` below 42rem, `repeat(3, 1fr)` from 42rem, gap 20px; item: white with the light blue border from Task 1 (same card look as the devices; spec allows this variant), radius 8px, padding 1em; `time` small, `#3088E0`; h3 `font-weight: 500`, margin 0.25em 0; title link color `#000`, hover `#1088FF`.
- [ ] **Step 4: Run** – Expected: ALL PASS (MENUS=1 run: `display-posts-listing` check fails until the backend change; that is expected and reported).
- [ ] **Step 5: Commit** `"Add news cards to front page"`

### Task 5: Screenshots, Backend-Anleitung, Doku

**Files:** Modify `docs/backend-menus.md`, `CLAUDE.md`, `style.css` (Version 1.3 → 1.4, cache busting of the CSS), `tests/smoke.sh` only if a check needs adjusting.

- [ ] **Step 1: Screenshots** with `playwright-cli` at 1280×900 and 390×844 (full page) of `http://shop.profax.local/`; check visually against the spec; evaluate `document.documentElement.scrollWidth <= window.innerWidth` at both widths → `true`. Fix CSS if not.
- [ ] **Step 2: Backend-Anleitung** as new section in `docs/backend-menus.md` (before „Wenn die Hauptseite … umzieht“, renumber): Seite 90 bearbeiten → „Lernen aktuell“-Überschrift und den `[display-posts …]`-Shortcode samt auskommentiertem SWISSDIDAC-Block entfernen; im Textblock `<div style="padding: 1em 2.5em 0">` das Padding auf `1em 0 0` setzen (oder das `div` durch einen normalen Absatz ersetzen); Kurzbeschreibungen der beiden Geräte pflegen (erscheinen auf den Karten); „empfohlen“-Stern bei Lernheften pflegen; Hinweis: Startseite kommt aus `front-page.php`, die Seitenvorlage im Editor ist wirkungslos.
- [ ] **Step 3: CLAUDE.md** Architecture: `front-page.php` + Front-page-Funktionen in `functions.php` ergänzen.
- [ ] **Step 4: Run `bash tests/smoke.sh` and `MENUS=1 bash tests/smoke.sh`** – Expected: ALL PASS; MENUS=1 only the backend-dependent `display-posts-listing` check may fail.
- [ ] **Step 5: Commit** `"Document front page backend steps"`
