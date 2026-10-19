# Shop-Integration in profax.com – Design

Datum: 2026-10-19

## Ziel

shop.profax.com wird als Subdomain neben www.profax.com betrieben (der Kunde migriert profaxonline.com → profax.com). Der Shop übernimmt Header, Footer und Grundtypografie der Hauptseite, sodass der Wechsel zwischen Hauptseite und Shop für Besucher nicht sichtbar ist.

**Verbindliche Vorlage:** www.profaxonline.com (Stand Oktober 2026), insbesondere `zeitgeist.css`, `template.css`, `common.css`.

**Erfolgskriterium:** Bei 1280 px und 390 px Breite sind Header und Footer von Hauptseite und Shop optisch gleich (Schrift, Farben, Abstände, Mobile-Menü). Einziger Unterschied im Header: aktiver Eintrag „Shop“ und Warenkorb-Symbol.

## Entscheide

| Thema | Entscheid |
|---|---|
| Ansatz | Eigenes Markup in `header.php`/`footer.php`, relevante CSS-Regeln der Hauptseite in eigene, eingegrenzte Datei übernommen. Keine CSS-Dateien der Hauptseite einbinden. |
| Header-Navigation | 1:1 wie Hauptseite: Produkte · Preise · Hilfe · Shop · Registrieren · Login, plus Warenkorb-Symbol. „Shop“ auf allen Shop-Seiten aktiv. Kein „Mein Konto“ im Header. |
| Pflege der Navigation | Header- und Footer-Navigation als WordPress-Menüs im Backend pflegbar. |
| Footer | Struktur der Hauptseite (Schriftzug + zwei Spalten „Hilfe“, „Über profax“). |
| Schrift | zimi Light/DemiBold + zimiicons, selbst gehostet im Theme. Ganzer Shop auf zimi. Alte Base64-„profax“-Schrift entfällt. |
| Dark Mode | Vorerst nicht. Shop bleibt hell. |
| Sprach-/Länderleiste (`nav#lang`) | Wird weggelassen. |
| Cookie-Banner | Nicht Teil dieses Projekts (später evtl. Plugin). |
| Layout Shop-Inhalte | Bleibt Storefront; nur Schrift und Farben werden angeglichen. |

## Schriften

Quelle: `https://www.profaxonline.com/main/plc/fonts/{zimilight,zimidemibold,zimiicons}.{woff2,woff}`.

Lizenz laut Font-Metadaten: Verwendung ausschliesslich dem profax Verlag vorbehalten. Der Shop gehört dem profax Verlag → zulässig. Dateien nur im Shop-Theme verwenden; das Repository darf nicht öffentlich sein.

zimiicons belegt FontAwesome-4-Codepoints. Verwendet: Warenkorb `U+F07A`, Hamburger `U+F0C9`. Die Schrift-Kette `zimi, zimiicons, sans-serif` macht Icons als Textzeichen nutzbar (wie auf der Hauptseite). Der „profax“-Schriftzug des Logos ist eine Glyphe in zimi; das Logo ist Text, kein Bild.

## Header

`header.php` (Override von Storefront 4.6.2 `header.php`): `<head>`, `wp_head()`, `wp_body_open()` und `#page`/`#content`-Wrapper bleiben wie bei Storefront; `<header id="masthead">` mit `do_action('storefront_header')` wird ersetzt durch:

```html
<header id="header" class="profax-site-header">
  <nav id="menu1" class="topnav">
    <div class="subLog"><!-- Menü „Header: Login“ --> <!-- Warenkorb --></div>
    <div class="subNav"><!-- Menü „Header: Hauptnavigation“ --></div>
    <a class="home" href="PROFAX_MAIN_URL">profax</a>
    <a class="icon" href="#" aria-label="Menü" aria-expanded="false" aria-controls="menu1"></a>
  </nav>
</header>
```

- Menüs mit `wp_nav_menu()` ausgegeben als flache `<a>`-Liste ohne `<ul>` (Markup wie Hauptseite), CSS-Klassen der Menüeinträge werden auf das `<a>` übertragen (für `hlRed`).
- **Aktiv-Markierung „Shop“:** Ein Menüeintrag, dessen URL auf die Shop-Domain (`home_url()`) zeigt, erhält auf jeder Shop-Seite die Aktiv-Klasse. Stil wie aktiver Punkt auf der Hauptseite (`zeitgeist.css` Z. 357 ff.): `font-weight: bold`, Farbe `#002080`, Hover `#000`.
- **Warenkorb:** Link auf `wc_get_cart_url()`, Icon `U+F07A`, Anzahl der Artikel. Aktualisierung via WooCommerce-Filter `woocommerce_add_to_cart_fragments`.
- **Mobil (< 49rem):** `.subNav` ausgeblendet, `a.icon` zeigt Hamburger; Klick schaltet Klasse `responsive` auf `.topnav` und `aria-expanded`. Eigenes Vanilla-JS (`assets/js/profax-nav.js`), kein jQuery/t5.
- `do_action('storefront_before_header')` und `storefront_before_content` bleiben erhalten (für Plugins); `storefront_header` wird nicht mehr aufgerufen.

## Footer

`footer.php` (Override von Storefront 4.6.2 `footer.php`):

```html
<footer class="profax-site-footer">
  <h3><a class="home" href="PROFAX_MAIN_URL">profaxonline</a></h3>
  <nav class="fboxS">
    <div class="f2"><h4>{Name Menü „Footer: Hilfe“}</h4><ul>…</ul></div>
    <div class="f2"><h4>{Name Menü „Footer: Über profax“}</h4><ul>…</ul></div>
  </nav>
</footer>
```

- Spaltenüberschrift = Name des zugewiesenen Menüs.
- Nicht mehr ausgegeben: Storefront-Footer-Widgets, Credit, Handheld-Footer-Bar. Widgets bleiben in der Datenbank.
- `storefront_before_footer`, `storefront_after_footer` (z. B. Sticky-Add-to-Cart) und `wp_footer()` bleiben erhalten.

### Inhalt „Über profax“ (Pflege im Backend)

| Eintrag | Ziel |
|---|---|
| Kontakt und Impressum | Hauptseite `/dech/contact/` |
| Aktuelles | Shop `/blog/` |
| Allgemeine Geschäftsbedingungen | Hauptseite `/dech/termsofservice/` |
| Datenschutz | Hauptseite `/dech/privacypolicy/` |
| Verkauf und Lieferung | Shop `/verkauf-lieferung/` |
| Datenzugriffsanfrage | Shop `/datenzugriffsanfrage/` |
| Shop | Shop-Startseite |

Hinweis an den Kunden: AGB und Datenschutzerklärung der Hauptseite erwähnen Shop/Warenverkauf nicht; rechtliche Abdeckung des Shops prüfen (besteht bereits heute so).

## Inhaltsbereich

| Element | Wert |
|---|---|
| Schrift | `zimi, zimiicons, sans-serif`, 14 px, line-height 1.4 |
| Seitenhintergrund / Text | `#F8F8F8` / `#000` |
| Links / Hover | `#3088E0` / `#40D0FF` |
| Buttons | Hintergrund `#1088FF`, Text `#FFF`, Radius 4 px, Schatten `rgba(0,0,0,.75) 0 4px 4px -4px` |
| Header/Footer | Hintergrund `#50A8FF`, Links `#FFF`, Hover `#000`, `hlRed` `#E00000` (Hover `#000`), `.subLog`-Links `#404040` (Hover `#FFF`) |

- Storefront-Customizer-Farben werden per `theme_mod_*`-Filter im Code gesetzt (keine Customizer-Pflege, gespeicherte DB-Werte werden überschrieben).
- Base64-`@font-face` „profax“ in `style.css` entfernen, alle `font-family: profax` auf zimi umstellen.

## Dateien

| Datei | Inhalt |
|---|---|
| `header.php` | neu, siehe oben |
| `footer.php` | neu, siehe oben |
| `assets/fonts/*` | 6 Schriftdateien |
| `assets/css/profax-site.css` | `@font-face`, Header, Footer, Grundtypografie; Selektoren auf `.profax-site-header`, `.profax-site-footer` bzw. `body` eingegrenzt |
| `assets/js/profax-nav.js` | Hamburger-Toggle |
| `functions.php` | Konstante `PROFAX_MAIN_URL` (`https://www.profaxonline.com/dech/`), `register_nav_menus` (4 Positionen), Enqueue CSS/JS, Entfernen der Storefront-Header-/Footer-Hooks, Aktiv-Klasse „Shop“, Warenkorb-Fragment, `theme_mod`-Filter |
| `style.css` | Base64-Schrift entfernen, Schrift umstellen |
| `CLAUDE.md` | Architektur-Abschnitt nachführen |

Menü-Positionen: `profax_header_main` („Header: Hauptnavigation“), `profax_header_login` („Header: Login“), `profax_footer_help` („Footer: Hilfe“), `profax_footer_about` („Footer: Über profax“).

## Backend-Schritte (durch Jérôme)

1. Menü „Hauptnavigation“: Produkte, Preise, Hilfe (individuelle Links auf Hauptseite), Shop (Shop-Startseite), Registrieren (Hauptseite, CSS-Klasse `hlRed`). CSS-Klassen-Feld über „Ansicht anpassen“ einblenden.
2. Menü „Login“: Login (Hauptseite).
3. Menü „Hilfe“: Videoanleitungen, Handbücher / Zusatzmaterial, Häufig gestellte Fragen, Seminare (Hauptseite).
4. Menü `info` umbenennen in „Über profax“, Einträge gemäss Tabelle oben.
5. Menüs den vier Positionen zuweisen; Positionen `primary`/`handheld` können leer bleiben.
6. Bei der Migration: `wp search-replace 'www.profaxonline.com' 'www.profax.com'` (Menü-Links) und `PROFAX_MAIN_URL` im Theme anpassen.

## Test

Playwright-Screenshots Hauptseite vs. Shop bei 1280 px und 390 px. Seiten: Startseite, Kategorie, Produkt, Warenkorb, Checkout, Mein Konto. Prüfen:
- Header/Footer optisch gleich wie Hauptseite
- Hamburger öffnet/schliesst Navigation mobil
- Warenkorb-Zähler aktualisiert sich nach „In den Warenkorb“ ohne Neuladen
- „Shop“ auf allen Seiten aktiv, „Registrieren“ rot
- Keine PHP-Notices; Checkout funktioniert
