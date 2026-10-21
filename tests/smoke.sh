#!/usr/bin/env bash
# Smoke tests against the local shop. Usage: bash tests/smoke.sh  (MENUS=1 for menu content checks)
set -u

SHOP="${SHOP_URL:-http://shop.profax.local}"
THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
THEME_URL="$SHOP/wp-content/themes/$(basename "$THEME_DIR")"
PHP="${PHP:-$(ls "$HOME/Library/Application Support/Local/lightning-services"/php-*/bin/darwin-*/bin/php 2>/dev/null | tail -1)}"
FAILS=0

fail() { echo "FAIL: $1"; FAILS=$((FAILS + 1)); }
pass() { echo "ok:   $1"; }

get() {
	local out code
	out="$(curl -s -w '\n%{http_code}' "$SHOP$1")"
	code="${out##*$'\n'}"
	[ "$code" = "200" ] || { echo "FAIL: GET $1 returned $code" >&2; FAILS=$((FAILS + 1)); }
	printf '%s' "${out%$'\n'*}"
}

# WP-CLI eval against the local site; PHP notices/warnings are printed as "PHPWARN: ..." (deprecations ignored)
WP_PHAR="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar"
SOCK="$(find "$HOME/Library/Application Support/Local/run" -name mysqld.sock 2>/dev/null | head -1)"
HAS_WP=0; [ -n "$PHP" ] && [ -f "$WP_PHAR" ] && [ -n "$SOCK" ] && HAS_WP=1
wp_eval() {
	local code="${*: -1}" args=("${@:1:$#-1}")
	WP_CLI_CACHE_DIR="${TMPDIR:-/tmp}/wp-cli-cache" "$PHP" -d mysqli.default_socket="$SOCK" "$WP_PHAR" --path="$THEME_DIR/../../.." ${args[@]+"${args[@]}"} \
		eval 'set_error_handler( function( $no, $msg, $file, $line ) { echo "PHPWARN: $msg ($file:$line)\n"; return true; }, E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED );'"$code" 2>&1
}

status() { curl -s -o /dev/null -w '%{http_code}' "$1"; }

assert_contains()     { if grep -qF -- "$2" <<<"$1"; then pass "$3"; else fail "$3"; fi; }
assert_not_contains() { if grep -qF -- "$2" <<<"$1"; then fail "$3"; else pass "$3"; fi; }
assert_matches()      { if grep -qiE -- "$2" <<<"$1"; then pass "$3"; else fail "$3"; fi; }
assert_equals()       { if [ "$1" = "$2" ]; then pass "$3"; else fail "$3 (got '$1', want '$2')"; fi; }

# --- PHP lint ---
if [ -n "$PHP" ]; then
	while IFS= read -r f; do
		[ -f "$f" ] || continue
		"$PHP" -l "$f" >/dev/null 2>&1 && pass "php -l $(basename "$f")" || fail "php -l $f"
	done < <(find "$THEME_DIR" -name "*.php" -not -path "*/.*")
fi

HOME_HTML="$(get /)"

# --- Task 1: fonts, base typography, Storefront colors ---
assert_matches "$HOME_HTML" "id=['\"]profax-site-css" "profax-site.css enqueued"
assert_not_contains "$HOME_HTML" "storefront-fonts-css" "Storefront Google fonts not loaded"
assert_equals "$(status "$THEME_URL/assets/fonts/zimilight.woff2")" "200" "zimilight.woff2 served"
assert_equals "$(status "$THEME_URL/assets/fonts/zimiicons.woff2")" "200" "zimiicons.woff2 served"
assert_equals "$(status "$THEME_URL/assets/fonts/zimiregular.woff2")" "200" "zimiregular.woff2 served"
assert_not_contains "$(cat "$THEME_DIR/style.css")" "{" "style.css holds only the theme header"
assert_not_contains "$HOME_HTML" "storefront-child-style-css" "empty style.css not loaded"
STYLE="$(cat "$THEME_DIR/assets/css/profax-site.css")"
assert_not_contains "$STYLE" "base64" "profax-site.css has no embedded base64 font"
assert_not_contains "$STYLE" "font-family: profax" "profax-site.css no longer uses the old profax font"
assert_matches "$HOME_HTML" "#1088ff" "Storefront button color #1088FF"
assert_matches "$HOME_HTML" "#50a8ff" "Storefront header color #50A8FF"

# --- Task 2: header (must also pass with no menus assigned) ---
assert_contains "$HOME_HTML" '<header id="header" class="profax-site-header">' "profax header rendered"
assert_contains "$HOME_HTML" 'class="topnav"' "header has .topnav"
assert_contains "$HOME_HTML" 'class="subLog"' "header has .subLog"
assert_contains "$HOME_HTML" 'class="subNav"' "header has .subNav"
assert_contains "$HOME_HTML" 'class="home" href="https://www.profaxonline.com/dech/"' "logo links to main site"
assert_contains "$HOME_HTML" 'class="profax-cart"' "cart link in header"
assert_matches "$HOME_HTML" "id=['\"]profax-nav-js" "nav script enqueued"
assert_not_contains "$HOME_HTML" 'id="masthead"' "Storefront masthead gone"
assert_not_contains "$HOME_HTML" 'id="site-header-cart"' "Storefront header cart gone"
assert_not_contains "$HOME_HTML" 'storefront-primary-navigation' "Storefront primary navigation gone from header"
assert_contains "$HOME_HTML" 'class="profax-shop-nav"' "shop category navigation below header"
SEARCH_POS="$(grep -bo 'class="header-widget-region"' <<<"$HOME_HTML" | head -1 | cut -d: -f1)"
NAV_POS="$(grep -bo 'class="profax-shop-nav"' <<<"$HOME_HTML" | head -1 | cut -d: -f1)"
if [ -n "$SEARCH_POS" ] && [ -n "$NAV_POS" ] && [ "$SEARCH_POS" -lt "$NAV_POS" ]; then pass "search above category navigation"; else fail "search above category navigation"; fi
FIRST_CAT="$(get /produktkategorie/mathematik/ | sed -n '/<nav id="site-navigation" class="profax-shop-nav"/,/<\/nav>/p' | grep -o '<li [^>]*>' | head -1)"
assert_not_contains "$FIRST_CAT" 'current-menu-item' "menu order kept in markup (desktop order)"
assert_matches "$(tr -d '\n\t' < "$THEME_DIR/assets/css/profax-site.css")" 'max-width: 48\.999rem\) \{[^@]*\.current-menu-item \{order: -1;' "active category first in mobile scroll row"
assert_contains "$(get /produkt/ersatzschieber-profax/)" 'profax-site-header' "header on product page"
assert_contains "$(get /warenkorb/)" 'profax-site-header' "header on cart page"
FRAG="$(curl -s -X POST "$SHOP/?wc-ajax=get_refreshed_fragments")"
assert_contains "$FRAG" 'a.profax-cart' "cart fragment registered"
assert_not_contains "$(sed -n '/<header id="header"/,/<\/header>/p' <<<"$HOME_HTML")" 'profax-cart__count' "empty cart shows no count"

# --- Task 3: footer ---
FOOTER_HTML="$(sed -n '/<footer class="profax-site-footer">/,/<\/footer>/p' <<<"$HOME_HTML")"
assert_contains "$HOME_HTML" '<footer class="profax-site-footer">' "profax footer rendered"
assert_contains "$FOOTER_HTML" '<h3><a class="home" href="https://www.profaxonline.com/dech/">profaxonline</a></h3>' "footer wordmark links to main site"
assert_contains "$FOOTER_HTML" 'class="fboxS"' "footer has .fboxS"
assert_equals "$(grep -o 'class="f2"' <<<"$FOOTER_HTML" | wc -l | tr -d ' ')" "2" "footer has two columns"
assert_not_contains "$HOME_HTML" 'id="colophon"' "Storefront colophon gone"
assert_not_contains "$HOME_HTML" 'class="footer-widgets' "Storefront footer widgets gone"
assert_not_contains "$HOME_HTML" '<div class="storefront-handheld-footer-bar"' "handheld footer bar gone"
assert_contains "$(cat "$THEME_DIR/footer.php")" "do_action( 'storefront_after_footer' )" "footer.php keeps storefront_after_footer"
assert_contains "$(get /produkt/ersatzschieber-profax/)" 'storefront-sticky-add-to-cart' "sticky add-to-cart still rendered"

# --- Review fixes ---
for target in $(grep -oE 'class="skip-link[^"]*" href="#[^"]+"' <<<"$HOME_HTML" | grep -oE '#[^"]+' | tr -d '#'); do
	assert_contains "$HOME_HTML" "id=\"$target\"" "skip link target #$target exists"
done
if [ "$HAS_WP" = 1 ]; then
	NOWC="$(wp_eval --skip-plugins=woocommerce 'ob_start(); profax_site_header(); profax_site_footer(); profax_front_device_cards( profax_front_devices() ); profax_front_klassen(); profax_front_featured(); echo strpos( ob_get_clean(), "profax-site-header" ) !== false ? "HEADER-OK" : "NO-HEADER";')"
	assert_contains "$NOWC" "HEADER-OK" "header and front page sections render with WooCommerce inactive"
	assert_not_contains "$NOWC" "PHPWARN" "no PHP warning with WooCommerce inactive"
fi
assert_matches "$(cat "$THEME_DIR/assets/css/profax-site.css")" 'a\.button:focus' "button text stays white on focus/visited"

# --- Front page (docs/superpowers/specs/2026-10-19-shop-startseite-design.md) ---
FRONT="$(sed -n '/<main id="main"/,/<\/main>/p' <<<"$HOME_HTML")"
# one front page section, from its class to the next </section> (sections may share a line)
front_section() { tr -d "\n" <<<"$FRONT" | grep -o "<section class=\"$1\".*" | sed "s|</section>.*||"; }
# pill texts without the product counts, joined with |
pill_labels() { sed -E 's| <span class="profax-count">\([0-9]+\)</span>||g' <<<"$1" | grep -oE '<a [^>]*>[^<]+</a>' | sed -E 's/<[^>]+>//g' | paste -sd'|' -; }
# each pill count "(n)" equals the number of products on the page the pill links to (optional $3: cookie)
check_counts() {
	local ok=1 n=0 href count got
	while IFS='|' read -r href count; do
		n=$((n + 1))
		got="$(curl -s -b "${3:-}" "$href" | grep -c 'class="product type-product')"
		[ "$got" = "$count" ] || { ok=0; echo "  $href: pill says $count, page has $got"; }
	done < <(grep -oE '<a href="[^"]*"[^>]*>[^<]*<span class="profax-count">\([0-9]+\)</span>' <<<"$1" | sed -E 's#<a href="([^"]*)".*\(([0-9]+)\).*#\1|\2#')
	if [ "$ok" = 1 ] && [ "$n" -gt 0 ]; then pass "$2 ($n pills)"; else fail "$2 ($n pills)"; fi
}
assert_contains "$FRONT" '<div class="entry-content">' "front page shows page content"
assert_contains "$FRONT" 'class="profax-front-devices"' "front page device cards"
assert_matches "$(tr -d '\n\t' < "$THEME_DIR/assets/css/profax-site.css")" '@media \(min-width: 42rem\) \{\.profax-front-intro \{grid-template-columns: 1fr 1fr;' "intro block in the page content: image and text 50/50 from 42rem"
assert_matches "$(tr -d '\n\t' < "$THEME_DIR/assets/css/profax-site.css")" '\.profax-front-intro > p:empty \{display: none;' "intro block ignores the empty paragraph wpautop inserts"
assert_matches "$(tr -d '\n\t' < "$THEME_DIR/assets/css/profax-site.css")" '\.profax-front-intro \{[^}]*align-items: start;' "intro text aligned to the top of the image"
assert_equals "$(grep -o 'class="profax-device"' <<<"$FRONT" | wc -l | tr -d ' ')" "2" "two device cards"
for url in /produkt/profax-lerngeraet/ /produkt/profaxli-lerngeraet/ /produktkategorie/lernhefte-fuer-profax-lerngeraet/ /produktkategorie/lernhefte-fuer-profaxli-lerngeraet/; do
	assert_contains "$FRONT" "href=\"$SHOP$url\"" "front page links to $url"
done
KLASSEN="$(front_section profax-front-klassen)"
assert_contains "$KLASSEN" '<h2>Lernhefte nach Klasse</h2>' "class row title"
assert_equals "$(pill_labels "$KLASSEN")" "Vorschule|1. Klasse|2. Klasse|3. Klasse|4. Klasse|5. Klasse|6. Klasse|7. Klasse|8. Klasse|9. Klasse|IF" "class pills order and labels (empty values hidden)"
assert_contains "$KLASSEN" "href=\"$SHOP/shop/?filter_klasse=vorschule\"" "class pill links to filtered shop"
assert_equals "$(curl -s "$SHOP/shop/?filter_klasse=if" | grep -c 'class="product type-product')" "12" "IF filter returns products"
FEATURED="$(front_section profax-front-featured)"
assert_contains "$FEATURED" '<h2>Beliebte Lernhefte</h2>' "featured section title"
assert_equals "$(grep -o '<li class="product type-product' <<<"$FEATURED" | wc -l | tr -d ' ')" "4" "four featured products"
assert_equals "$(grep -o '<li class="product type-product[^"]*' <<<"$FEATURED" | grep -c ' featured ')" "4" "all shown products are featured"
assert_equals "$(grep -o '<li class="product type-product[^"]*' <<<"$FEATURED" | grep -cE 'product_cat-lernhefte-fuer-profax(li)?-lerngeraet')" "4" "featured products are Lernhefte"
NEWS="$(front_section profax-front-news)"
assert_contains "$NEWS" '<h2>Aktuelles</h2>' "news section title"
assert_equals "$(grep -o 'class="profax-news__item"' <<<"$NEWS" | wc -l | tr -d ' ')" "3" "three news cards"
assert_contains "$NEWS" "href=\"$SHOP/blog/\"" "link to all posts"
EXCERPTS="$(grep -oE '<p class="profax-news__excerpt">[^<]*' <<<"$NEWS" | sed 's/<[^>]*>//')"
assert_equals "$(grep -c . <<<"$EXCERPTS")" "3" "three news excerpts"
LONGEST="$(awk '{print NF}' <<<"$EXCERPTS" | sort -n | tail -1)"
if [ "${LONGEST:-99}" -le 21 ]; then pass "news excerpts at most 20 words"; else fail "news excerpts at most 20 words (got $LONGEST)"; fi
if [ "$HAS_WP" = 1 ]; then
	NONE="$(wp_eval 'var_dump( profax_front_featured_ids( array( "gibt-es-nicht" ) ) );')"
	assert_contains "$NONE" 'array(0)' "no featured products -> empty list"
	# products the shop hides (e.g. out of stock with "hide out of stock items" on) are skipped, the rest fills up
	HIDDEN="$(wp_eval 'add_filter( "woocommerce_product_is_visible", function( $visible, $id ) { return $visible && 0 !== $id % 2; }, 10, 2 ); $ids = profax_front_featured_ids( array( "lernhefte-fuer-profax-lerngeraet", "lernhefte-fuer-profaxli-lerngeraet" ) ); echo "COUNT=" . count( $ids ) . " EVEN=" . count( array_filter( $ids, function( $id ) { return 0 === $id % 2; } ) );')"
	assert_contains "$HIDDEN" 'COUNT=4 EVEN=0' "hidden featured products skipped, still four shown"
	ALLHIDDEN="$(wp_eval 'add_filter( "woocommerce_product_is_visible", "__return_false" ); profax_front_featured(); echo "END";')"
	assert_not_contains "$ALLHIDDEN" 'Beliebte Lernhefte' "no visible featured products -> section omitted"
	MISSING="$(wp_eval 'profax_front_device_cards( array( array( "product" => "gibt-es-nicht", "category" => "lernhefte-fuer-profax-lerngeraet" ), array( "product" => "profaxli-lerngeraet", "category" => "gibt-es-nicht" ) ) );')"
	assert_equals "$(grep -o 'class="profax-device"' <<<"$MISSING" | wc -l | tr -d ' ')" "1" "missing product drops its card"
	assert_not_contains "$MISSING" 'Lernhefte ansehen' "missing category drops its link"
	assert_not_contains "$MISSING" 'PHPWARN' "no PHP warning for missing slugs"
fi

# --- Class filter on shop and category pages, combined with the category navigation ---
klassen_row() { tr -d '\n' <<<"$1" | grep -o '<nav class="profax-klassen-filter".*' | sed 's|</nav>.*||'; }
shop_nav() { tr -d '\n' <<<"$1" | grep -o '<nav id="site-navigation" class="profax-shop-nav".*' | sed 's|</nav>.*|</nav>|'; }
MATHE_HTML="$(get /produktkategorie/mathematik/)"
MATHE="$(klassen_row "$MATHE_HTML")"
assert_equals "$(pill_labels "$MATHE")" "Alle|1. Klasse|2. Klasse|3. Klasse|4. Klasse|5. Klasse|6. Klasse|7. Klasse" "category class pills: only classes of the category"
assert_contains "$MATHE" "<li class=\"is-active\"><a href=\"$SHOP/produktkategorie/mathematik/\" aria-current=\"true\">Alle <span class=\"profax-count\">" "Alle active without class filter"
assert_not_contains "$(shop_nav "$MATHE_HTML")" 'profax-no-match' "no dimmed categories without class filter"
assert_contains "$(shop_nav "$HOME_HTML")" '<span class="profax-filter-label">Produktkategorien:</span>' "category navigation labelled"
assert_contains "$MATHE" '<span class="profax-filter-label">Klassen:</span>' "class row labelled"
assert_matches "$(tr -d '\n\t' < "$THEME_DIR/assets/css/profax-site.css")" '\.profax-shop-nav,\.profax-klassen-filter \{display: flex;flex-direction: column;' "filter titles above the pills at every width"
POS_NAV="$(grep -bo '<nav id="site-navigation" class="profax-shop-nav"' <<<"$MATHE_HTML" | head -1 | cut -d: -f1)"
POS_KLASSEN="$(grep -bo '<nav class="profax-klassen-filter"' <<<"$MATHE_HTML" | head -1 | cut -d: -f1)"
POS_TITLE="$(grep -bo 'class="woocommerce-products-header__title' <<<"$MATHE_HTML" | head -1 | cut -d: -f1)"
if [ -n "$POS_NAV" ] && [ -n "$POS_KLASSEN" ] && [ -n "$POS_TITLE" ] && [ "$POS_NAV" -lt "$POS_KLASSEN" ] && [ "$POS_KLASSEN" -lt "$POS_TITLE" ]; then pass "class row between category navigation and page title"; else fail "class row between category navigation and page title"; fi
assert_matches "$(tr -d '\n\t' < "$THEME_DIR/assets/css/profax-site.css")" '\.profax-pills a,\.profax-pills a:visited \{[^}]*background-color: rgba\(80, 168, 255, 0\.25\);color: #3088E0;' "class pills blue like the categories, also when visited (style.css: BODY a:visited)"
MATHE3_HTML="$(get '/produktkategorie/mathematik/?filter_klasse=3')"
MATHE3="$(klassen_row "$MATHE3_HTML")"
assert_contains "$MATHE3" "<li class=\"is-active\"><a href=\"$SHOP/produktkategorie/mathematik/?filter_klasse=3\" aria-current=\"true\">3. Klasse <span class=\"profax-count\">" "active class pill"
assert_contains "$MATHE3" "<a href=\"$SHOP/produktkategorie/mathematik/\">Alle <span class=\"profax-count\">" "Alle removes the class filter, keeps the category"
assert_contains "$MATHE3" "<a href=\"$SHOP/produktkategorie/mathematik/?filter_klasse=4\">4. Klasse <span class=\"profax-count\">" "class pill keeps the category"
assert_equals "$(grep -c 'class="product type-product' <<<"$MATHE3_HTML")" "5" "category and class filter combined"
NAV9="$(shop_nav "$(get '/shop/?filter_klasse=9')")"
assert_contains "$NAV9" "href=\"$SHOP/produktkategorie/geografie/?filter_klasse=9\"" "category link keeps the class filter"
assert_contains "$NAV9" "href=\"$SHOP/shop/?filter_klasse=9\"" "Alle category link keeps the class filter"
RS9="$(grep -oE '<li [^>]*><a href="[^"]*/rechtschreibung/[^"]*"' <<<"$NAV9")"
assert_contains "$RS9" 'profax-no-match' "category without products for the class is dimmed"
assert_not_contains "$RS9" 'filter_klasse' "dimmed category links without class filter"
# product counts in the pills
check_counts "$KLASSEN" "front page class pill counts match the linked pages"
check_counts "$MATHE3" "category class pill counts match the linked pages"
check_counts "$(shop_nav "$MATHE_HTML")" "category pill counts match the linked pages"
check_counts "$NAV9" "category pill counts with active class match the linked pages"
assert_equals "$(grep -cE 'profax-no-match[^>]*><a [^>]*>[^<]*<span class="profax-count"' <<<"$NAV9")" "0" "dimmed categories show no count"
assert_contains "$(klassen_row "$(get '/shop/?filter_klasse=vorschule')")" 'aria-current="true">Vorschule <span class="profax-count">' "class row on the shop page shows the active filter"
assert_not_contains "$(get '/produktkategorie/mathematik/?filter_klasse=%22%3E%3Cb%3Exss')" '"><b>xss' "class filter value is escaped"
SEARCH_HTML="$(get '/?s=mathe&post_type=product')"
assert_contains "$SEARCH_HTML" 'class="product type-product' "product search returns products"
assert_not_contains "$SEARCH_HTML" 'profax-klassen-filter' "no class row on search results (links would drop the search)"
if [ "$HAS_WP" = 1 ]; then
	ONE="$(wp_eval 'echo profax_klassen_filter_html( home_url( "/shop/" ), array( get_term_by( "slug", "3", "pa_klasse" ) ), "" ); echo "END";')"
	assert_not_contains "$ONE" 'profax-klassen-filter' "single class -> no class row"
	assert_not_contains "$ONE" 'PHPWARN' "no PHP warning for single class row"
fi

# --- Category navigation only on the front page and WooCommerce pages outside the order process; search everywhere ---
skip_targets_ok() {  # every skip link points to an element on the page
	local html="$1" target
	for target in $(grep -oE 'class="skip-link[^"]*" href="#[^"]+"' <<<"$html" | grep -oE '#[^"]+' | tr -d '#'); do
		grep -qF "id=\"$target\"" <<<"$html" || return 1
	done
}
for path in / /shop/ /produktkategorie/mathematik/ /produkt/ersatzschieber-profax/ /mein-konto/ '/?s=mathe&post_type=product'; do
	PAGE="$(get "$path")"
	assert_contains "$PAGE" 'class="profax-shop-nav"' "category navigation on $path"
	assert_contains "$PAGE" 'class="header-widget-region"' "search on $path"
done
for path in /warenkorb/ /blog/ /brueche-verstehen-mit-bruechen-rechnen/ /datenzugriffsanfrage/ '/?s=mathe'; do
	PAGE="$(get "$path")"
	assert_not_contains "$PAGE" 'class="profax-shop-nav"' "no category navigation on $path"
	assert_contains "$PAGE" 'class="header-widget-region"' "search on $path"
	if skip_targets_ok "$PAGE"; then pass "skip links have targets on $path"; else fail "skip links have targets on $path"; fi
done

# --- Private products stay out of the listings, also for logged-in admins ---
if [ "$HAS_WP" = 1 ]; then
	ADMIN_COOKIE="$(wp_eval '$u = get_users( array( "role" => "administrator", "number" => 1 ) ); echo "COOKIE:" . LOGGED_IN_COOKIE . "=" . rawurlencode( wp_generate_auth_cookie( $u[0]->ID, time() + 600, "logged_in" ) ) . "\n";' | grep -o 'COOKIE:.*' | cut -d: -f2-)"
	for path in /shop/ /produktkategorie/mathematik/ '/produktkategorie/rechtschreibung/?filter_klasse=3' '/?s=logo&post_type=product'; do
		AS_ADMIN="$(curl -s -b "$ADMIN_COOKIE" "$SHOP$path")"
		assert_matches "$AS_ADMIN" '<body class="[^"]*logged-in' "logged in as admin on $path"
		assert_not_contains "$(grep -o '<li class="product type-product[^"]*' <<<"$AS_ADMIN")" 'status-private' "no private products for admins on $path"
	done
	check_counts "$(shop_nav "$(curl -s -b "$ADMIN_COOKIE" "$SHOP/produktkategorie/mathematik/")")" "category pill counts match the linked pages for admins" "$ADMIN_COOKIE"
fi

# --- Backend changes: menus and front page content (see docs/backend-menus.md) ---
if [ "${MENUS:-0}" = "1" ]; then
	for path in / /shop/ /produkt/ersatzschieber-profax/ /warenkorb/ /mein-konto/ /blog/; do  # /kasse/ redirects to the cart while it is empty
		HEADER="$(get "$path" | sed -n '/<header id="header"/,/<\/header>/p')"
		CURRENT="$(grep -oE '<a href="[^"]*" class="[^"]*profax-current[^"]*"' <<<"$HEADER")"
		assert_equals "$(grep -c . <<<"$CURRENT")" "1" "one active header link on $path"
		assert_contains "$CURRENT" "href=\"$SHOP" "active header link points to the shop on $path"
	done
	HEADER="$(sed -n '/<header id="header"/,/<\/header>/p' <<<"$HOME_HTML")"
	assert_matches "$HEADER" 'class="hlRed">Registrieren</a>' "Registrieren is red"
	assert_not_contains "$(grep -oE '<a href="https://www.profaxonline.com[^>]*>' <<<"$HEADER")" 'profax-current' "main site links are not active"
	assert_contains "$FOOTER_HTML" '<h4>Hilfe</h4>' "footer heading Hilfe"
	assert_contains "$FOOTER_HTML" '<h4>Über profax</h4>' "footer heading Über profax"
	assert_not_contains "$(sed -n '/<nav id="site-navigation" class="profax-shop-nav"/,/<\/nav>/p' <<<"$HOME_HTML")" '>p</a>' "old 'p' logo item removed from category menu"
	assert_not_contains "$FRONT" 'display-posts-listing' "old post list removed from front page content"
	assert_not_contains "$FRONT" 'id="stellen"' "off-screen job teaser removed from front page content (causes horizontal scrolling)"
	assert_contains "$FRONT" '<div class="profax-front-jobs">' "job notice box in front page content"
	assert_contains "$FRONT" '<div class="profax-front-intro">' "intro block (image and text) in front page content"
fi

echo
if [ "$FAILS" -eq 0 ]; then echo "ALL PASS"; else echo "$FAILS FAILED"; exit 1; fi
