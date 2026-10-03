<?php
return [
 '2.9.8'=>[
  'title'=>'Was ist neu in Betrio 2.9.8?',
  'intro'=>'Dieses Release bündelt die neuen Lager-, Inventur-, DATANORM- und PDF-Funktionen.',
  'items'=>[
   'DATANORM-Import mit Vorschau, Lieferantenzuordnung, EAN und Preisbasis',
   'Materialstamm nach Lieferant filtern und sortieren',
   'Inventur-Assistent sowie Inventurliste als PDF und Druckansicht',
   'PDF-Auswertungen für Arbeitszeit, Abrechnung, Finanzen, Steuern und Projekt-Nachkalkulation',
   'Druckzugriff für Angebote, Rechnungen, Lieferscheine und Rapporte erweitert',
  ],
 ],
 '2.9.6'=>[
  'title'=>'Was ist neu in Betrio 2.9.6?',
  'intro'=>'Dieses Release bündelt die neuen Lager-, Inventur-, DATANORM- und PDF-Funktionen für den offiziellen Store-Stand.',
  'items'=>[
   'DATANORM-Import mit Vorschau, Lieferantenzuordnung, EAN und Preisbasis',
   'Materialstamm nach Lieferant filtern und sortieren',
   'Inventur-Assistent sowie Inventurliste als PDF und Druckansicht',
   'PDF-Auswertungen für Arbeitszeit, Abrechnung, Finanzen, Steuern und Projekt-Nachkalkulation',
   'Druckzugriff für Angebote, Rechnungen, Lieferscheine und Rapporte erweitert',
   'Store-Beschreibung und Store-Screenshots auf den aktuellen Funktionsstand gebracht',
  ],
 ],
 '2.9.5'=>[
  'title'=>'Was ist neu in Betrio 2.9.5?',
  'intro'=>'Der Materialstamm lässt sich jetzt direkt nach Lieferanten filtern und sortieren.',
  'items'=>[
   'Neuer Lieferantenfilter direkt im Materialstamm',
   'Lieferantenfilter ist mit Suche, Materialgruppe und Bestandsfilter kombinierbar',
   'Spalte Lieferant kann auf- und absteigend sortiert werden',
  ],
 ],
 '2.9.4'=>[
  'title'=>'Was ist neu in Betrio 2.9.4?',
  'intro'=>'Der DATANORM-Import wurde mit echten Lieferantendaten korrigiert und erweitert.',
  'items'=>[
   'DATANORM-5-Feldzuordnung für Artikelnummer, Bezeichnung, Einheit, Preis und EAN korrigiert',
   'UTF-8- und klassische CP850-DATANORM-Dateien werden automatisch erkannt',
   'Preisbasis wird bei der Berechnung des Einheitspreises berücksichtigt',
   'EAN wird beim Import in das Barcode-Feld des Materialstamms übernommen',
   'Lieferantenname aus dem DATANORM-Vorlaufsatz wird in der Vorschau angezeigt',
  ],
 ],
 '2.9.3'=>[
  'title'=>'Was ist neu in Betrio 2.9.3?',
  'intro'=>'Lager und Inventur wurden erweitert und der DATANORM-Import sauber mit den Lieferanten verknüpft.',
  'items'=>[
   'DATANORM-Import mit verpflichtender Lieferantenzuordnung und Vorschau',
   'Importierte und aktualisierte DATANORM-Artikel werden dem gewählten Lieferanten zugeordnet',
   'Lagerübersicht nach Lieferant filtern und nach Lieferant, Artikel, Bezeichnung oder Bestand sortieren',
   'Inventur-Assistent zum Erfassen und Korrigieren gezählter Lagerbestände',
   'Inventurliste als PDF und Druckansicht',
   'PDF- und Druckfunktionen für betriebliche Auswertungen erweitert',
  ],
 ],
 '2.9.2'=>[
  'title'=>'Was ist neu in Betrio 2.9.2?',
  'intro'=>'Lager und Inventur wurden um DATANORM und einen geführten Inventurablauf erweitert.',
  'items'=>[
   'Inventur-Assistent zum Erfassen gezählter Bestände und Buchen von Abweichungen',
   'Inventurliste als PDF und Druckansicht',
   'DATANORM-Import mit Dateiprüfung und Vorschau vor dem Import',
  ],
 ],
 '2.9.1'=>[
  'title'=>'Was ist neu in Betrio 2.9.1?',
  'intro'=>'Die PDF- und Dokumentenverarbeitung wurde weiter verbessert.',
  'items'=>[
   'Verbesserte Texterkennung bei eingescannten PDF-Dokumenten',
   'Robustere Verarbeitung unterschiedlicher PDF-Dokumente',
   'Optimierte Dokumentenerkennung im Betrio Dokumenteneingang',
  ],
 ],
 '2.9.0'=>[
  'title'=>'Was ist neu in Betrio 2.9.0?',
  'intro'=>'Die digitale Projektakte wird zum echten Nextcloud-Dateimanager und protokolliert Dateiaktivitäten projektbezogen.',
  'items'=>[
   'Eigene Ordner und Unterordner direkt in der Projektakte anlegen',
   'Neue zentrale Übersicht für Dateiaktivitäten über alle Projekte',
   'Neue, geänderte, verschobene und gelöschte Dateien werden projektbezogen protokolliert',
   'Dateiänderungen aus Nextcloud Files, Desktop-Sync und WebDAV werden über Nextcloud-Dateiereignisse erfasst',
   'Dateiaktivitäten direkt in der jeweiligen Projektakte sichtbar',
   'NC32-Kompatibilität verbessert: verbliebener automatisch benannter Primärindex durch kurzen expliziten Namen ersetzt',
  ],
 ],
 '2.8.9'=>[
  'title'=>'Was ist neu in Betrio 2.8.9?',
  'intro'=>'Die mobile Arbeitszeiterfassung wurde für die aktuelle Nextcloud-Version stabilisiert und die Attendance-API robuster gemacht.',
  'items'=>[
   'Mobile Arbeitszeiterfassung mit festen API-Routen für Kommen, Pause, Fortsetzen und Gehen',
   'HTTP-400-Fehler bei den mobilen Anwesenheitsaktionen behoben',
   'Anwesenheitsaktionen robuster gegen Wiederholungen, Retries und Doppeltipps gemacht',
   'Pause, Fortsetzen und Gehen liefern auch bei bereits erreichtem Zustand zuverlässig den aktuellen Status zurück',
   'Temporäre Diagnose- und Debug-Ausgaben nach erfolgreicher Fehlerbehebung entfernt',
  ],
 ],
 '2.8.5'=>[
  'title'=>'Was ist neu in Betrio 2.8.5?',
  'intro'=>'Dieses Update korrigiert den Lieferantenbereich und stellt den Versionshinweis für die aktuelle Version wieder korrekt bereit.',
  'items'=>[
   '„+ Neuer Lieferant“ im Lieferantenbereich wieder funktionsfähig',
   'Bearbeitung von Lieferanten innerhalb eines Projekts übersichtlicher dargestellt',
   'Lieferantenformular für Desktop und mobile Ansichten verbessert',
   'Versionshistorie unter Hilfe → Was ist neu? auf den aktuellen Stand gebracht',
   'Automatischer „Was ist neu?“-Hinweis nach dem Update wieder aktiviert',
  ],
 ],
 '2.8.4'=>[
  'title'=>'Betrio 2.8.4 – Lieferanten-Fixes',
  'intro'=>'Fehlerbehebungen und Verbesserungen im Lieferantenbereich.',
  'items'=>[
   '„+ Neuer Lieferant“ korrigiert',
   'Projektbezogene Lieferantenbearbeitung überarbeitet',
   'Darstellung des Bearbeitungsformulars verbessert',
  ],
 ],
 '2.8.0'=>[
  'title'=>'Was ist neu in Betrio 2.8.0?',
  'intro'=>'Betrio informiert jetzt nach einem Update einmalig über wichtige Neuerungen. Die Meldung wird für jeden Benutzer getrennt gespeichert.',
  'items'=>[
   'Neue „Was ist neu?“-Meldung nach einem Betrio-Update',
   'Mit × schließen: Für diese Version wird die Meldung für den angemeldeten Benutzer nicht erneut angezeigt',
   'Dauerhafte Versionshistorie unter Hilfe → Was ist neu?',
   'Arbeitszeitbereich mit Arbeitszeitkonto, Stichtag, individuellen Sollzeiten, Abwesenheiten und Personalplanung abgeschlossen',
   'Arbeitszeitlogiken geprüft und Hilfetexte/Tooltips ergänzt',
  ],
 ],
 '2.7.9'=>[
  'title'=>'Betrio 2.7.9 – Arbeitszeit Abschlussprüfung',
  'intro'=>'Abschlussprüfung und Dokumentation des Mitarbeiter- und Arbeitszeitbereichs.',
  'items'=>['Individuelle Arbeitstage bei Urlaub und Abwesenheiten berücksichtigt','Überschneidungen bei Abwesenheiten und manueller Anwesenheit werden verhindert','Tooltips und Dokumentation für Arbeitszeit, Planung und Abwesenheiten ergänzt'],
 ],
 '2.7.8'=>[
  'title'=>'Betrio 2.7.8 – Mitarbeiterprofile kompakt',
  'intro'=>'Die Verwaltung der Arbeitszeitprofile wurde übersichtlicher gestaltet.',
  'items'=>['Kompakte Mitarbeiterübersicht','Arbeitszeitprofil erst beim Bearbeiten aufklappen','Konsistente Wochen-Sollstunden aus den Tageswerten'],
 ],
 '2.7.7'=>[
  'title'=>'Betrio 2.7.7 – Arbeitszeitkonto Stichtag',
  'intro'=>'Der Start des Arbeitszeitkontos wird als harte Berechnungsgrenze verwendet.',
  'items'=>['Projektzeiten vor dem Stichtag bleiben erhalten','Soll/Ist und Wochenübersicht rechnen erst ab dem Stichtag'],
 ],
 '2.7.6'=>[
  'title'=>'Betrio 2.7.6 – Arbeitszeitmodell',
  'intro'=>'Individuelle Sollzeiten und ein Startsaldo wurden ergänzt.',
  'items'=>['Sollstunden je Wochentag','Arbeitszeitkonto ab festem Datum','Startsaldo für bestehende Arbeitszeitkonten'],
 ],
 '2.7.5'=>[
  'title'=>'Betrio 2.7.5 – Arbeitszeitkonto & Plan/Ist',
  'intro'=>'Arbeitszeit, Projektzeit und Personalplanung können gemeinsam ausgewertet werden.',
  'items'=>['Arbeitszeitkonto','Tagesübersicht','Planzeit und Projektzeit getrennt auswerten'],
 ],
 '2.7.4'=>[
  'title'=>'Betrio 2.7.4 – Kalender-Dubletten-Fix',
  'intro'=>'Synchronisierte Betrio- und Nextcloud-Termine werden in den Betrio-Ansichten zusammengeführt.',
  'items'=>['Dubletten im Dashboard reduziert','Dubletten im Teamkalender und in der Personalplanung zusammengeführt'],
 ],
 '2.7.3'=>[
  'title'=>'Betrio 2.7.3 – Personalplanung & Teamkalender',
  'intro'=>'Personalplanung, Abwesenheiten und Teamkalender wurden enger verbunden.',
  'items'=>['Personalplanung im Teamkalender','Abwesenheiten im Teamkalender','Kalender-Zeitzonenbehandlung verbessert'],
 ],
 '2.7.2'=>[
  'title'=>'Betrio 2.7.2 – Personalplanung Komfort',
  'intro'=>'Die Wochenplanung wurde kompakter und besser bearbeitbar.',
  'items'=>['Kompakte Einsatzkarten','Einsätze bearbeiten und löschen','Konfliktwarnungen bei Abwesenheit und Doppelbelegung'],
 ],
 '2.7.1'=>[
  'title'=>'Betrio 2.7.1 – Personalplanung',
  'intro'=>'Mitarbeiter können projektbezogen für Tage und Uhrzeiten eingeplant werden.',
  'items'=>['Wochenansicht je Mitarbeiter','Projektbezogene Einsatzplanung','Planung bleibt getrennt von tatsächlicher Arbeitszeit'],
 ],
 '2.7.0'=>[
  'title'=>'Betrio 2.7.0 – Urlaub & Abwesenheiten',
  'intro'=>'Urlaub und weitere Abwesenheiten wurden in den Mitarbeiterbereich integriert.',
  'items'=>['Urlaubsanträge mit Genehmigung','Krankheit, Schulung und sonstige Abwesenheiten','Urlaubskonto und Sollzeitgutschriften'],
 ],
];
