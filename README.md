# Turnwettkampf

Verwalte einen Turnwettkampf übers Internet mit Leichtigkeit. Voraussetzung: Webhosting mit PHP und einer SQL-Datenbank.

Autor: Peter Otte (peter.otte@gmx.de)

## Verbesserungen

...sind gern gesehen:
Verbesserungen am besten per PR oder per E-Mail.

## Rechteverwaltung 
Es gibt folgende unterschiedliche Benutzer:

- Wettkampfleitung (kann alles)
- Meldende Vereine (können Turner anmelden)
- Kampfrichter (können Wertungen eingeben)
- Zuschauer, bzw. jeder im Internet (können Wertungen sehen)


## Unterstützte Funktionen

- Verwaltung (Bearbeiten, Löschen und Neuanlagen) für Wettkampfbüro: Geraete
- Verwaltung (Bearbeiten, Löschen und Neuanlagen) für Wettkampfbüro: Geraetetypen
- Verwaltung (Bearbeiten, Löschen und Neuanlagen) für Wettkampfbüro: Riegen
- Verwaltung (Bearbeiten, Löschen und Neuanlagen) für Wettkampfbüro: Wettkaempf
- Verwaltung (Bearbeiten, Löschen und Neuanlagen) für Wettkampfbüro: Turner
- Verwaltung (Bearbeiten, Löschen und Neuanlagen) für Wettkampfbüro: Vereine
- Anmeldung für Vereine
- Wettkampf Einstellungen in extra Tabelle
- Eingeben von Wertungen für Wettkampfbüro
- Durchgänge Verwaltung
- Riegenlaufplan: Zuordnung Durchgänge, Geräte, Riegen
- Anzeige für Publikum pro Gerät
- Navigationsmenü
- Alles online im Internet zugänglich
- Die Eingabe- und Ausgabe von Werten geschieht mittels Webseiten, optimiert für Handys und Tablets. à Ja, das wäre ideal. Natürlich sollte auch eine Eingabe über Desktop-Geräte möglich sein. Manche Kari bevorzugen das.
- Gesamtpunktzahl und Platzierungen automatisch berechnen
- Protokoll
- Ergebnisse berechnen und Anzeigen
- Riegenliste 


## Anzeige.php

Wird dieser der GET-Parameter `GeraetID` übergeben, so werden nur noch von diesem Gerät die Aktualisierungen angezeigt.