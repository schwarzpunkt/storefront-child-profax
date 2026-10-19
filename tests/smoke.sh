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
STYLE="$(cat "$THEME_DIR/style.css")"
assert_not_contains "$STYLE" "base64" "style.css has no embedded base64 font"
assert_not_contains "$STYLE" "font-family: profax" "style.css no longer uses the old profax font"
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
WP_PHAR="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar"
SOCK="$(find "$HOME/Library/Application Support/Local/run" -name mysqld.sock 2>/dev/null | head -1)"
if [ -n "$PHP" ] && [ -f "$WP_PHAR" ] && [ -n "$SOCK" ]; then
	NOWC="$(WP_CLI_CACHE_DIR="${TMPDIR:-/tmp}/wp-cli-cache" "$PHP" -d mysqli.default_socket="$SOCK" "$WP_PHAR" --path="$THEME_DIR/../../.." --skip-plugins=woocommerce \
		eval 'ob_start(); profax_site_header(); profax_site_footer(); echo strpos( ob_get_clean(), "profax-site-header" ) !== false ? "HEADER-OK" : "NO-HEADER";' 2>&1)"
	assert_contains "$NOWC" "HEADER-OK" "header renders with WooCommerce inactive"
fi
assert_matches "$(cat "$THEME_DIR/assets/css/profax-site.css")" 'a\.button:focus' "button text stays white on focus/visited"

# --- Task 4: menu content (needs the backend menus, see docs/backend-menus.md) ---
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
fi

echo
if [ "$FAILS" -eq 0 ]; then echo "ALL PASS"; else echo "$FAILS FAILED"; exit 1; fi
