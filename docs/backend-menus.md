# Menüs im Backend einrichten

Anleitung für die Menüs von Header und Footer im Shop (Theme „Storefront profax Child Theme“ ab Version 1.3). Gilt für die lokale Installation und für die Produktion.

Alle Schritte unter **Design → Menüs**. Damit Sie CSS-Klassen eintragen können: oben rechts **Ansicht anpassen** öffnen und **CSS-Klassen** aktivieren.

Links auf die Hauptseite legen Sie als **Individuelle Links** an. Für Shop-Seiten nehmen Sie den Eintrag aus **Seiten**, dann passt sich die Domain automatisch an.

## 1. Menü „Hauptnavigation“ (neu)

| Titel | Typ | URL / Seite | CSS-Klasse |
|---|---|---|---|
| Produkte | Individueller Link | `https://www.profaxonline.com/dech/programs/` | |
| Preise | Individueller Link | `https://www.profaxonline.com/dech/prices/` | |
| Hilfe | Individueller Link | `https://www.profaxonline.com/dech/documentation/` | |
| Shop | Individueller Link | Shop-Startseite (lokal `http://shop.profax.local/`, Produktion `https://shop.profax.com/`) | |
| Registrieren | Individueller Link | `https://www.profaxonline.com/dech/registration/` | `hlRed` |

Position: **Header: Hauptnavigation**

„Shop“ wird auf allen Shop-Seiten automatisch als aktiv markiert. „Registrieren“ wird durch `hlRed` rot.

## 2. Menü „Login“ (neu)

| Titel | Typ | URL |
|---|---|---|
| Login | Individueller Link | `https://www.profaxonline.com/dech/` |

Position: **Header: Login**

Das Login-Symbol und den Warenkorb ergänzt das Theme selbst.

## 3. Menü „Hilfe“ (neu)

Der Menü-Name erscheint als Spaltenüberschrift im Footer.

| Titel | Typ | URL |
|---|---|---|
| Videoanleitungen | Individueller Link | `https://www.profaxonline.com/dech/howto/` |
| Handbücher / Zusatzmaterial | Individueller Link | `https://www.profaxonline.com/dech/documentation/` |
| Häufig gestellte Fragen | Individueller Link | `https://www.profaxonline.com/dech/faq/` |
| Seminare | Individueller Link | `https://www.profaxonline.com/dech/workshops/seminar/` |

Position: **Footer: Hilfe**

## 4. Menü `info` → „Über profax“ (bestehend)

Den Menü-Namen von `info` auf **Über profax** ändern (er erscheint als Spaltenüberschrift). Danach die Einträge auf diese Reihenfolge bringen:

| Titel | Typ | URL / Seite | Aktion |
|---|---|---|---|
| Kontakt und Impressum | Individueller Link | `https://www.profaxonline.com/dech/contact/` | neu (ersetzt „Impressum und Kontakt“) |
| Aktuelles | Seite | Blog | neu |
| Allgemeine Geschäftsbedingungen | Individueller Link | `https://www.profaxonline.com/dech/termsofservice/` | bestehenden Eintrag umbenennen, URL anpassen |
| Datenschutz | Individueller Link | `https://www.profaxonline.com/dech/privacypolicy/` | bestehenden Eintrag umbenennen, URL anpassen |
| Verkauf und Lieferung | Seite | Verkauf und Lieferung | bleibt |
| Datenzugriffsanfrage | Seite | Datenzugriffsanfrage | bleibt |
| Shop | Individueller Link | Shop-Startseite | neu |

Entfernen: „Impressum und Kontakt“ (Shop-Seite), „Die profax Story“, „Der profaxpreis“, „Der profax Verlag sucht dich“. Die Seiten selbst bleiben bestehen.

Position: **Footer: Über profax**

## 5. Menü „productcategories“ (bestehend)

Wird weiterhin als Kategorie-Navigation unter dem Header angezeigt (Position **Primäres Menü**, bleibt so).

Den ersten Eintrag **„p“** (Link auf die Startseite, Überbleibsel des alten Logos) entfernen.

## 6. Nicht mehr benötigt

- Die Position **Handheld-Menü** wird nicht mehr ausgegeben. Die Zuweisung kann bleiben oder entfernt werden.
- Die Footer-Widgets unter **Design → Widgets** (Shop, aktuell, über profax) werden nicht mehr ausgegeben. Sie können bleiben oder gelöscht werden.

## 7. Startseite (ab Theme-Version 1.4)

Die Startseite kommt aus dem Theme-Template `front-page.php`. Oben steht weiterhin der Inhalt der Startseite (**Seiten → „profax Verlag AG“**, Seite 90), darunter folgen automatisch Geräte-Karten, „Lernhefte nach Klasse“, „Beliebte Lernhefte“ und „Aktuelles“. Seitentitel und Beitragsbild (Logo) der Seite werden nicht mehr angezeigt; die Seitenvorlage-Einstellung im Editor hat keine Wirkung.

**Seite 90 bearbeiten** (Code-/Text-Ansicht des Editors):

1. Die Überschrift `<h2>Lernen aktuell</h2>`, den auskommentierten Block darunter (`<!--div style=… SWISSDIDAC … </center-->`) und den Shortcode `[display-posts …]` löschen. Die Beiträge erscheinen neu im Abschnitt „Aktuelles“.
2. Im Textblock `<div style="padding: 1em 2.5em 0">` das Padding auf `1em 0 0` ändern, damit der Text bündig mit Bild und Karten steht. Alternativ das `div` durch einen normalen Absatz ersetzen.
3. Den Block für „Der profax Verlag sucht dich!“ am Ende löschen: `<style> #stellen … </style>`, `<script> … </script>` und den Link `<a href="…/der-profax-verlag-sucht-dich/" id="stellen">…</a>`. Der Link liegt unsichtbar rechts ausserhalb der Seite (das Skript, das ihn einblendet, ist auskommentiert) und macht die Seite breiter als den Bildschirm: Auf dem Handy lässt sie sich dadurch seitlich verschieben. Stattdessen den sichtbaren Stellen-Hinweis als Block in den Inhalt setzen (siehe Tabelle unten).
4. Aktualisieren.

**Pflege der Abschnitte:**

| Abschnitt | Wo pflegen |
|---|---|
| Geräte-Karten | Produkte „profax Lerngerät“ (`profax-lerngeraet`) und „profaxli Lerngerät“ (`profaxli-lerngeraet`): Beitragsbild = Kartenbild, **Kurzbeschreibung** = Kartentext. Link „Lernhefte ansehen“ führt auf die Kategorien `lernhefte-fuer-profax-lerngeraet` bzw. `lernhefte-fuer-profaxli-lerngeraet`. Die Slugs nicht ändern, sonst fällt die Karte bzw. der Link weg. |
| Lernhefte nach Klasse | Produkt-Attribut **Klasse** (Produkte → Eigenschaften). Werte ohne Produkte werden ausgeblendet. |
| Beliebte Lernhefte | Stern „Empfohlen“ in der Produktliste. Gezeigt werden zufällig 4 empfohlene Produkte aus den beiden Lernheft-Kategorien. Ohne empfohlene Hefte fällt der Abschnitt weg. |
| Stellen-Hinweis „Der profax Verlag sucht dich!“ | Im Inhalt von Seite 90, als eigene Zeile mit Leerzeilen davor und danach: `<div class="profax-front-jobs"><a href="/der-profax-verlag-sucht-dich/"><strong>Der profax Verlag sucht dich!</strong> <span>Zur Stellenausschreibung →</span></a></div>`. Das Theme liefert das Aussehen der Box (hellblau, ganze Box ist der Link). Ist die Stelle besetzt, den Block löschen. |
| Aktuelles | Die 3 neusten Beiträge; der Auszug stammt aus dem Feld „Textauszug“ oder, wenn leer, aus dem Beitragstext (max. 20 Wörter). |

## 8. Wenn die Hauptseite von profaxonline.com nach profax.com umzieht

Das Shop-WordPress zieht nicht um. Nur die Links auf die Hauptseite müssen nachgeführt werden (sofern der Kunde keine Weiterleitungen einrichtet):

1. Im Theme die Konstante `PROFAX_MAIN_URL` in `functions.php` anpassen (Logo-Links in Header und Footer).
2. Unter **Design → Menüs** die individuellen Links in „Hauptnavigation“, „Login“, „Hilfe“ und „Über profax“ auf die neue Domain ändern.
