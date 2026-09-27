## 2.4.93

### Das bisher größte Betrio-Update

Mit dieser Version habe ich einen großen Teil der Funktionen, die ich selbst bereits seit längerer Zeit intern mit Betrio nutze und weiterentwickle, für die Community freigegeben.

Im Mittelpunkt stand dabei nicht, möglichst viele einzelne Funktionen hinzuzufügen, sondern die vorhandenen Bereiche zu einem durchgängigen Arbeitsablauf zu verbinden. Vom Angebot über Auftrag und Projekt bis zu Material, Arbeitszeiten, Rapporten, Lieferanten, Rechnungen, Zahlungen, Nachkalkulation und DATEV greifen die Bereiche jetzt deutlich stärker ineinander.

### Neu bzw. umfassend erweitert

- Projektcontrolling und Nachkalkulation mit Soll/Ist-Vergleich, internen Personalkosten, Material- und Fremdkosten
- Einkauf, Lieferanten-Cockpit und projektbezogene Eingangsbelege
- Lagerentnahmen als tatsächliche Projektkosten und Übergabe an die Abrechnung
- Abrechnungsassistent für noch nicht berechnete Zeiten, Materialien und Leistungen
- Durchgängige Belegkette von Angebot und Auftrag bis Lieferschein und Rechnung
- Rechnungsprotokoll, Festschreibung, Storno und Gutschriften
- DATEV-EXTF-Export und erweiterte Finanzübersichten
- Arbeitskostenanteil nach § 35a EStG
- Smart Document Inbox mit Projekt-, Kunden- und Lieferantenzuordnung
- Überarbeitete Projektnotizen, Hilfen und Einstellungen
- Zahlreiche Verbesserungen an Bedienung, Darstellung und bestehenden Arbeitsabläufen

Viele dieser Funktionen sind aus dem täglichen Einsatz von Betrio entstanden. Mit diesem Release stehen sie nun auch allen anderen Betrio-Nutzern zur Verfügung.

# 2.4.91

- Lieferantenbelege, Projektkosten und Projektabschluss enger verbunden.
- Wird ein Lieferschein aus dem Dokumenteneingang einem Lieferantenvorgang zugeordnet, wechselt ein bislang offener Wareneingang automatisch auf „Teilweise eingelagert“; „Vollständig“ bleibt eine bewusste manuelle Entscheidung.
- Zugeordnete Eingangsrechnungen werden im Lieferanten-Cockpit mit Anzahl und Netto-Kosten sichtbar und bleiben dieselben Belege, die bereits in die Projekt-Nachkalkulation einfließen.
- Offene Forderungen werden im Projektabschluss als offener Punkt berücksichtigt.
- Vorhandene Rapport-, Zeit-, Lager-, Rechnungs- und Zahlungsdaten werden weiterverwendet; keine parallelen Datenstrukturen.
- Keine Datenbankmigration.

# 2.4.90

- Projektbezogene Lagerentnahmen fließen mit ihrem Einstandswert in die Ist-Materialkosten der Nachkalkulation ein.
- Künftige Lagerentnahmen speichern den Einstandswert zum Buchungszeitpunkt; bestehende Entnahmen verwenden als Fallback den aktuellen EK des Materials.
- Ist-Kosten und Projektergebnis berücksichtigen Lagerverbrauch zusätzlich zu zugeordneten Eingangsbelegen.

# 2.4.85

- Assistent-Navigation und PayPal-Unterstützung UX korrigiert.

## 2.4.83 – 2026-09-27

### Einstellungen Navigation & Schnellzugriff
- Neue Schnellzugriffe oben auf der Einstellungsseite für Firmendaten, Nummernkreise, Stundensätze, DATEV, Kalender sowie Rapporte & PDF.
- Direkte Funktionsbuttons zu Benutzer & Rechte, Integrationen, Mobile und Dokumentation.
- Sprungziele landen direkt am passenden Einstellungsbereich und werden beim Anspringen hervorgehoben.
- Responsive Darstellung für kleinere Browserbreiten.
- Keine Datenbankmigration.

## 2.4.82 – 2026-09-27

### UX & Projektcockpit
- Nachkalkulation erkennt Arbeitszeiten ohne internen Kostensatz und kennzeichnet das Ergebnis als vorläufig.
- Direkter Hinweis mit Sprung zu Mitarbeiter & Rechte, wenn Kostensätze für die Nachkalkulation fehlen.
- Plausibilitätswarnung, wenn der Verkaufswert der erfassten Arbeitszeit den Auftragswert übersteigt.
- Marge in Prozent wird nur angezeigt, wenn die Personalkosten vollständig bewertet werden können.
- Wichtige Projekt-KPIs und interne Personalkosten führen direkt zu den zugehörigen Bereichen.
- Warnungen sind handlungsorientiert und verlinken auf die Stelle, an der sie behoben werden können.
- Keine Datenbankmigration.

## 2.4.81 – 2026-09-27

### Behoben
- Kontext-Hilfe wird als echtes Overlay außerhalb der Projektkarten dargestellt und nicht mehr durch Container abgeschnitten.
- Tooltip positioniert sich automatisch ober- oder unterhalb des Hilfe-Symbols, abhängig vom verfügbaren Platz.
- Maus kann vom Hilfe-Symbol in die Hilfebox bewegt werden, ohne dass diese sofort schließt.
- „Mehr erfahren“ bleibt dadurch zuverlässig anklickbar.
- Klick fixiert eine Hilfe; Klick außerhalb oder Escape schließt sie.

## 2.4.80
- Kontext-Hilfe: Tooltip-Boxen sind technisch per hidden-Status geschlossen und werden nur beim aktiven Tooltip eingeblendet.

## 2.4.79
- Kontext-Hilfe/Tooltips: geschlossen im Normalzustand, sauberes Hover-, Klick- und Touch-Verhalten.

## 2.4.78

### Behoben
- Projekt-Entity für die Kalkulationsfelder aus 2.4.76 vervollständigt.
- Fehler `calcValueNet is not a valid attribute` beim Laden von Projekten behoben.

## 2.4.77

- Kontextbezogene Tooltips mit direkten Dokumentationslinks.
- Dokumentation auf aktuellen Stand für Kalkulation, Projektcontrolling, Smart Inbox, Lieferanten, §35a, DATEV und Projektabschluss gebracht.

## 2.4.76 – Projektsteuerung & Automatik

- Projektabschluss-Assistent mit konkreten offenen Prüfpunkten.
- Klickbare Nachkalkulation und direkte Abrechnung aus dem Projekt.
- Fixierter Soll-Kalkulationsstand ab Auftragsphase.
- Netto-Auftragswert mit Angebots-Fallback.
- Smart-Inbox-Kostenartvorschlag für Eingangsrechnungen.
- §35a-Vorschlag aus offenen Arbeitsleistungen.

## 2.4.75
- Projekt-Nachkalkulation nur noch auf der Projektübersicht.
- Kompakteres Projektcontrolling mit klarer Trennung von Kalkulation und Abrechnung.
- Verkaufswert Arbeitszeit und interne Personalkosten eindeutig getrennt.

## 2.4.74

- Kalkulation & Projektcontrolling: interne Angebots-Sollwerte, Mitarbeiter-Kostensätze, Soll/Ist-Nachkalkulation und Abschlussprüfung.
- Einkauf und Eingangsbelege fließen nach Kostenarten in die Projektkosten ein.
- Keine Banking-Anbindung in diesem Release.

## 2.4.73

- Eingangsrechnung → Lieferantenvorgang → Projektkosten → Nachkalkulation enger verbunden.
- Kostenarten und Fremdkosten-Aufteilung für Projekte ergänzt.
- Eingangsbelege speichern ihren Lieferanten-/Bestellbezug als Grundlage für die weitere Buchhaltungsintegration.

# Betrio 2.4.72

## Geändert
- Projekt-Nachkalkulation direkt auf der Projektübersicht unmittelbar unter den Kennzahlen platziert.
- Bereich „Was noch offen ist“ ist ohne Scrollen durch die Fachbereiche erreichbar.
- Bestehende Nachkalkulationslogik aus 2.4.71 unverändert beibehalten.

# Betrio 2.4.71

## Hinzugefügt
- Projekt-Nachkalkulation direkt in der Projektakte mit Auftragswert, Abrechnungsstand, Zahlungseingängen, Arbeitsleistung, Rapportmaterial und zugeordneten Eingangsrechnungen.
- Automatische Übersicht „Was noch offen ist“ für fehlende Auftragsbestätigungen, offene Lieferungen, Rapporte, Abrechnungsentscheidungen und Restabrechnung.
- Abrechnungsfortschritt je Projekt und direkte Sprünge zu Abrechnung, Lieferanten und Rapporten.
- §35a-Arbeitskosten werden bei projektbezogenen Rechnungen aus noch offenen Arbeitszeiten automatisch als Netto-Vorschlag vorbereitet.

## Verbessert
- Projektakte verbindet Verkauf, Leistung, Einkauf, Rechnungen und Zahlungen enger miteinander.
- Bereits vorhandene Daten werden für die Nachkalkulation genutzt; es entsteht keine parallele Projektpflege.
- Der automatisch vorgeschlagene §35a-Betrag bleibt vor dem Speichern vollständig manuell änderbar.

# Betrio 2.4.70

## Geändert
- Arbeitskosten nach § 35a EStG werden vom Handwerker netto eingegeben.
- Betrio berechnet den zugehörigen MwSt.-Anteil automatisch mit dem Steuersatz der Rechnung.
- Im Rechnungs-PDF wird der Arbeitskostenanteil brutto inklusive MwSt. ausgewiesen.
- Netto-Arbeitskosten und enthaltener MwSt.-Betrag werden zusätzlich transparent dargestellt.
- Der Arbeitskostenanteil verändert den Rechnungsbetrag nicht.

## 2.4.69

### Hinzugefügt
- Optionaler Arbeitskostenanteil für Privatkunden in Rechnungen.
- Gesonderter Ausweis des Arbeitskostenanteils nach § 35a EStG im Rechnungs-PDF.
- Arbeitskostenanteil kann im Rechnungsentwurf erfasst und geändert werden.
- Der ausgewiesene Betrag ist nur ein Nachweis und verändert den Rechnungsbetrag nicht.

## 2.4.67 – 2026-09-26

### Neu
- Oberfläche der zentralen Betrio-Bereiche weiter vereinheitlicht und verfeinert.
- Smart Document Inbox mit verbesserter Erkennung von Dokumenttyp, Projekt, Kunde, Lieferant und Referenznummern.
- Zugeordnete Dokumente verschwinden aus dem aktiven Inbox-Arbeitsvorrat und bleiben weiterhin nachvollziehbar.
- Dokumente können direkt mit Lieferanten- und Bestellvorgängen eines Projekts verknüpft werden.
- Auftragsbestätigungen und Lieferscheine können direkt dem Lieferanten-Cockpit zugeordnet werden.
- Passende Lieferanten-/Bestellvorgänge werden bei eindeutiger Erkennung automatisch vorgeschlagen.
- Neuer Abrechnungsassistent „Nichts vergessen“ für Arbeitszeiten, Material, Rapporte und Lieferantenvorgänge.
- Abrechnungsrelevante Vorgänge können als übernommen, bereits enthalten oder nicht zu berechnen markiert werden.
- Neue Belegkette: Angebot → Auftrag → Lieferschein → Abschlagsrechnung/Rechnung/Schlussrechnung.
- Lieferscheine als eigene Betrio-Belege mit LS-Nummernkreis, Positionen, Detailansicht und PDF ohne Verkaufspreise.

### Verbessert
- Bestellnummern und AB-Nummern werden bei Dokumentvorschlägen stärker berücksichtigt.
- Projekt- und Lieferantenvorschläge arbeiten enger mit dem Lieferanten-Cockpit zusammen.
- Der Abrechnungsassistent aktualisiert sich direkt beim Wechsel des ausgewählten Projekts.
- Folgebelege übernehmen vorhandene Kunden-, Projekt- und Positionsdaten.
- Verknüpfte Ursprungs- und Folgebelege bleiben direkt nachvollziehbar und anklickbar.
- Kurze betriebliche Meldungen statt erklärender Systemtexte.
- Sicherheitsabfrage vor dem Erzeugen bzw. Vorbereiten von Folgebelegen.

### Weiterhin enthalten
- Rechnungsfestschreibung und Rechnungsprotokoll.
- Abschlags- und Schlussrechnungslogik.
- Storno-/Gutschriften-Workflow.
- DATEV-EXTF-Export.
- Mobile API und Offline-Synchronisation.
