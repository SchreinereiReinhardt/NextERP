<?php
return [
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
