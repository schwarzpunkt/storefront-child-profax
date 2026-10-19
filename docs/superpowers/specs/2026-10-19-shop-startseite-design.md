# Shop-Startseite – Design

Datum: 2026-10-19
Status: von Jérôme freigegeben; Umsetzung in einem neuen Claude-Kontext.

## Ausgangslage

Die Startseite von shop.profax.com ist die statische Seite **ID 90** (`show_on_front = page`, Blog-Seite ID 1060). Ihr Inhalt besteht heute aus dem Seiteninhalt im Editor: Überschrift „Lernen aktuell“ mit einer Liste der 10 neusten Beiträge (Shortcode „Display Posts“, `ul.display-posts-listing`), darunter „profax Lerngerät“ mit Bild, einem langen Werbetext in einem `<div style="padding: 1em 2.5em 0">` und eingebettetem `<style>`.

Header, Suche, Kategorie-Pills und Footer kommen aus dem Theme und erscheinen auf jeder Seite (siehe `docs/superpowers/specs/2026-10-19-profax-site-integration-design.md` und `CLAUDE.md`).

Problem: Ein Besucher sieht auf der Startseite weder, was der Shop verkauft, noch wo er anfangen soll.

## Ziel

Die Startseite führt Besucher schnell zum passenden Lernsystem. **Bestätigte Annahme (Jérôme):** Die beiden Geräte – profax Lerngerät und profaxli Lerngerät – sind der wichtigste Einstieg, weil fast das ganze Sortiment an einem der beiden hängt.

## Nicht-Ziele

- Keine Wiederholung von Suche und Fach-Pills (stehen schon oben auf jeder Seite).
- Kein Umbau von Header, Footer, Produkt- oder Kategorieseiten.
- Kein Dark Mode (Projektentscheid, siehe Integrations-Spec).
- Keine neuen Plugins, wenn es ohne geht.
- Keine Vertrauens-/Service-Zeile (Jérôme: weglassen).

## Anti-Ziele

- Die Startseite darf nicht wie eine andere Website wirken als der Rest des Shops und die Hauptseite (Schrift zimi, Farben und Pill-Stil aus `assets/css/profax-site.css`).
- Kein langer Fliesstext oberhalb der Geräte-Karten.

## Aufbau (von oben nach unten)

1. **Seiteninhalt von Seite 90 (Jérôme):** Der heutige Block „profax Lerngerät“ (Überschrift, Bild, Text) bleibt oben stehen und wird weiter im Backend als Inhalt von Seite 90 gepflegt; das Template gibt ihn mit `the_content()` aus. Den oberen Teil „Lernen aktuell“ mit der 10er-Beitragsliste entfernt Jérôme aus dem Seiteninhalt (Backend-Anleitung liefern); die Beiträge kommen neu aus Abschnitt 5. Das Template soll die Einrückung des Textes (`<div style="padding: 1em 2.5em 0">` im Inhalt) nicht per CSS-Hack ausgleichen – Jérôme passt das Padding im Inhalt an (Anleitung).
2. **Zwei Geräte-Karten nebeneinander** (auf dem Handy untereinander):
   - **profax Lerngerät** – Produkt-Slug `profax-lerngeraet` (lokal ID 89), Lernhefte: Kategorie-Slug `lernhefte-fuer-profax-lerngeraet` (lokal 49 Produkte).
   - **profaxli Lerngerät** – Produkt-Slug `profaxli-lerngeraet` (lokal ID 33), Lernhefte: Kategorie-Slug `lernhefte-fuer-profaxli-lerngeraet` (lokal 8 Produkte).
   - Je Karte: Produktbild (Beitragsbild des Produkts), Produktname, die **Kurzbeschreibung des Produkts** als Satz (damit im Backend pflegbar), zwei Links: „Gerät ansehen“ (Produkt) und „Lernhefte ansehen“ (Kategorie).
   - Template über **Slugs** auflösen, nicht über IDs (IDs der Live-Datenbank können abweichen). Fehlt ein Produkt oder eine Kategorie, die Karte bzw. den Link ohne Fehler weglassen.
3. **Lernhefte nach Klasse** (Jérôme: behalten; direkt unter den Geräte-Karten, als schlichte Zeile mit Titel „Lernhefte nach Klasse“): Pills im bestehenden Pill-Stil für die Werte des Produkt-Attributs **`pa_klasse`** (nicht die Schlagwörter – das Attribut ist vollständiger gepflegt und enthält „Vorschule“). Reihenfolge: Vorschule, 1 … 9; Anzeige „Vorschule“, „1. Klasse“ … „9. Klasse“. Werte mit 0 Produkten ausblenden (lokal: 10, Erwachsenenbildung, Frühförderung, Gymnasium); „IF“ (Integrative Förderung, 12 Produkte) als letzte Pill „IF“. Link: Shop-Seite mit WooCommerce-Filter `?filter_klasse=<slug>` (funktioniert ohne Attribut-Archive).
4. **Beliebte Lernhefte:** 4 Produkte im normalen Katalog-Raster (`ul.products`, damit Bildbox, Titel in Zimi Regular etc. automatisch passen). **Quelle (Jérôme): Zufallsauswahl aus den „empfohlenen“ Produkten (Stern im Backend), nur aus den Kategorien `lernhefte-fuer-profax-lerngeraet` und `lernhefte-fuer-profaxli-lerngeraet`.** Lokal sind 9 solche Hefte markiert. Weniger als 4 vorhanden → die vorhandenen zeigen; keine → Abschnitt weglassen. Hinweis: Mit einem Seiten-Cache wechselt die Auswahl nur, wenn der Cache erneuert wird – akzeptiert.
5. **Aktuelles:** die 3 neusten Beiträge als Text-Karten nebeneinander (auf dem Handy untereinander): Datum, Titel, Auszug von höchstens ~20 Wörtern, Link auf den Beitrag. **Die Beiträge haben keine Beitragsbilder (Jérôme)** – die Karten sind deshalb rein typografisch, z. B. im Pill-Hintergrund `rgba(80,168,255,0.25)` oder mit hellblauem Rand wie das Suchfeld. Link „Alle Beiträge“ auf die Blog-Seite (`get_option( "page_for_posts" )`, lokal ID 1060). Ersetzt die heutige 10er-Liste.

## Entscheide

- **Umsetzung als Theme-Template** `front-page.php` im Child-Theme (Claudes Entscheid, von Jérôme nicht widersprochen). Begründung: zuverlässig gleiches Aussehen wie der Rest des Shops, versioniert.
- Stil: bestehende Tokens und Komponenten aus `assets/css/profax-site.css` wiederverwenden (Pills, Inhaltsbox `#content .col-full` max. 1020 px / 20 px Rand, Schrift zimi inkl. Gewicht 500, Farben `#50A8FF`, `#1088FF`, `#3088E0`, Pill-Hintergrund `rgba(80,168,255,0.25)`).
- Backend-/Datenbank-Änderungen macht Jérôme selbst; Claude liefert dazu eine Anleitung (Projektregel, siehe `CLAUDE.md`).

## Geklärt (Jérôme)

- **Doppelung profax Lerngerät:** Inhaltsblock oben und beide Geräte-Karten zeigen; Jérôme entfernt bei Bedarf später etwas.
- **Beliebte Lernhefte:** Zufall aus empfohlenen Heften (siehe Abschnitt 4).
- **Klassen-Zeile:** behalten (Abschnitt 3).
- **News ohne Beitragsbild**, **keine Service-Zeile**.

## Dem Builder überlassen

- Konkretes Markup und CSS der Abschnitte, Responsiveness (muss bei 390 px und 1280 px gut aussehen, kein horizontales Scrollen).
- Ob Beiträge per `WP_Query` oder bestehendem Shortcode kommen.
- Bildgrössen der Karten.

## Prüfung

- `bash tests/smoke.sh` und `MENUS=1 bash tests/smoke.sh` bleiben grün; neue Checks für die Startseite ergänzen (Abschnitte vorhanden, Links auf die vier Ziel-URLs).
- Screenshots mit `playwright-cli` bei 1280 px und 390 px.
