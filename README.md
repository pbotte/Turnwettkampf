# Turnwettkampf

Verwalte einen Turnwettkampf übers Internet mit Leichtigkeit. Voraussetzung: Webhosting mit PHP und einer SQL-Datenbank.

Autor: Peter Otte (peter.ote@gmx.de)

## Verbesserungen

...sind gern gesehen:
Verbesserungen am besten per PR oder per E-Mail.

## Noch ausstehende Arbeiten 
- Berechung der Ausführung und Gesamtwertung in Datenbank aufnehmen. Aktualisierung durch PHP-Seite beim Eintragen der Wertungen (spart SQL-Rechenzeit).
- D-Note auf NULL setzen beim Eintragen neuer Wertungen. Ist einfach auf dem handy, sonst muss erst die 0,00 gelöscht werden.
- Ausgabe von Riegenlisten mit QR-Codes / PIN-Absicherung
- Eingeben von Wertungen für Kampfrichter
- Ausgabe der Wertungen mit Platzierung
- Beschränkung der Ausgabe der Wertungen für Wettkampfbüro
- Wettkampfpasswort und PIN für Kampfrichter über Webseite änderbar
- Die Riegenführer brauchen keinen ausgedruckten Zettel mehr. Die Riegen müssen lediglich ihre Gerätreihenfolge wissen. Wichtig ist, dass die Kampfrichter eine Riegenliste der aktuellen Riege vorliegen haben. In meiner Excel-Auswertung werden dazu die Riegen bei jedem Gerät entsprechend sortiert angezeigt. Du hast in deiner Lösung ja den Geräten bereits eine Reihenfolge zugewiesen. Genauso könnte man Durchgänge anlegen und innerhalb von Durchgängen den Riegen eine Reihenfolge zuweisen oder diese anhand jeweils eines Startgeräts aus der Gerätreihenfolge ableiten.
- Kampfrichter, Eingabe von Wertungen: Ich wäre dafür, dass die Kari zumindest eine kurze PIN zur Autorisierung eingeben müssen.
- Ausgabe für Zuschauer auf deren Handys à Genau. Das macht uns unabhängig von Bildschirmen/Hardware beim Wettkampf. Falls Bildschirme vorhanden sind, kann ja genau diese Zuschaueransicht dort angezeigt werden.
- Es sollte möglich sein, als übergeordnetes Objekt eine „Veranstaltung“ anzulegen, für die dann alles weitere konfiguriert wird.

## Rechteverwaltung 
Es gibt folgende unterschiedliche Benutzer:

- Wettkampfleitung (kann alles)
- Meldende Vereine (können Turner anmelden)
- Kampfrichter (Können Wertungen eingeben)
- Zuschauer (Können Wertungen sehen)


## Erledigt

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


