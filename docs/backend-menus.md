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

## 7. Wenn die Hauptseite von profaxonline.com nach profax.com umzieht

Das Shop-WordPress zieht nicht um. Nur die Links auf die Hauptseite müssen nachgeführt werden (sofern der Kunde keine Weiterleitungen einrichtet):

1. Im Theme die Konstante `PROFAX_MAIN_URL` in `functions.php` anpassen (Logo-Links in Header und Footer).
2. Unter **Design → Menüs** die individuellen Links in „Hauptnavigation“, „Login“, „Hilfe“ und „Über profax“ auf die neue Domain ändern.
