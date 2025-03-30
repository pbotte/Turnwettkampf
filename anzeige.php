<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');


/*
Programmiere eine php-Seite in einem modernen Design.
Es soll sich um eine öffentliche Anzeige eines Zahlenwertes (zusammen mit einzelnen kleinen weiteren Informationen) handeln.
Die Webseite soll den ganzen Bldschirm füllen.

Es soll alle 5 Sekunden via einer Socket-Verbindung eine REST API Abfrage an get_last_wertung.php durchgeführt werden.
Sie gibt z.B. zurück:
{"WertungID":9,"TurnerID":13,"GeraetID":6,"P-Stufe":5.5,"D-Note":10,"E1-Note":1,"E2-Note":null,"E3-Note":0.5,"E4-Note":null,"nA-Abzug":0,"Vorname":"Hallo","Nachname":"Sonne","Geburtsdatum":"2025-03-12","WettkampfID":null,"GeraetBeschreibung":"Sprung 2","WettkampfBeschreibung":"-","WettkampfGeschlechtID":null,"GeschlechtKurz":"-","Ausfuehrung":0.75,"Gesamtwertung":10.75,"Jahrgang":"2025"}


Angezeigt werden sollen nun folgende Werte der JSON-Datei:
- ganz groß in der Mitte (sowohl vertikal als auch horizontal): Gesamtwertung (Dezimalzahl mit 2 Nachkommastellen)
- links oben : "Nachname, Vorname (Jahrgang)"
- links unten: GeraetBeschreibung
- rechts oben: WettkampfBeschreibung


Alle Dezimalzahlen sollen mit "," und nicht mit "." dargestellt werden.

Der Hintergrund der Webseite soll weiß sein. Nachdem jedoch neue Daten via REST-API angekommen sind, die sich von den vorherigen unterscheiden, so soll der Hintergrund für 5 Sekunden grün werden (neue Daten verlängern diese Phase jeweils). Nach dieser Zeit (und ohne neue Daten) soll der Grünton innerhalb von weiteren 5 Sekunden wieder zurück zum Standard langsam überblenden. 

*/


?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Öffentliche Anzeige</title>
  <style>
    /* Basis-Styling */
    body {
      margin: 0;
      padding: 0;
      background-color: white;
      transition: background-color 5s;
      font-family: Arial, sans-serif;
    }
    .container {
      position: relative;
      width: 100vw;
      height: 100vh;
    }
    /* Zentrierte Gesamtwertung */
    .gesamtwertung {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 20vw; /* Schriftgröße anpassbar */
      font-weight: bold;
    }
    /* Positionierung der Informationen */
    .top-left {
      position: absolute;
      top: 10px;
      left: 10px;
      font-size: 2vw;
    }
    .top-right {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 2vw;
    }
    .bottom-left {
      position: absolute;
      bottom: 10px;
      left: 10px;
      font-size: 2vw;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Links oben: Nachname, Vorname (Jahrgang) -->
    <div class="top-left"></div>
    <!-- Rechts oben: WettkampfBeschreibung -->
    <div class="top-right"></div>
    <!-- Mittig: Gesamtwertung -->
    <div class="gesamtwertung"></div>
    <!-- Links unten: GeraetBeschreibung -->
    <div class="bottom-left"></div>
  </div>

  <script>
    let previousData = null;
    let backgroundTimeout = null;

    // Aktualisiert die Anzeige mit den neuen Daten
    function updateDisplay(data) {
      document.querySelector('.top-left').innerText = data.Nachname + ", " + data.Vorname + " (" + data.Jahrgang + ")";
      document.querySelector('.top-right').innerText = data.WettkampfBeschreibung;
      document.querySelector('.bottom-left').innerText = data.GeraetBeschreibung;
      // Gesamtwertung mit 2 Nachkommastellen formatieren (Dezimaltrennzeichen als Komma)
      let gesamtwertung = parseFloat(data.Gesamtwertung).toFixed(2).replace('.', ',');
      document.querySelector('.gesamtwertung').innerText = gesamtwertung;
    }

    // Prüft, ob die neuen Daten von den vorherigen abweichen und steuert den Hintergrundwechsel
    function checkForUpdates(newData) {
      if (!previousData || JSON.stringify(newData) !== JSON.stringify(previousData)) {
        // Neue Daten: Hintergrund sofort auf grün setzen
        document.body.style.transition = "none"; // Sofortiger Wechsel
        document.body.style.backgroundColor = "#00FF00"; // grün
        // Reflow erzwingen, damit der Farbwechsel sofort sichtbar wird
        void document.body.offsetWidth;
        // Transition wieder aktivieren für den anschließenden Fade-Effekt
        document.body.style.transition = "background-color 5s";

        // Vorherigen Timeout löschen, wenn vorhanden
        if (backgroundTimeout) clearTimeout(backgroundTimeout);
        // Nach 5 Sekunden (bei keiner weiteren Änderung) den Hintergrund wieder auf weiß überblenden
        backgroundTimeout = setTimeout(function(){
          document.body.style.backgroundColor = "white";
        }, 5000);
      }
      previousData = newData;
    }

    // Holt die Daten von der REST API
    function fetchData() {
      fetch('get_last_wertung.php')
        .then(response => response.json())
        .then(data => {
          updateDisplay(data);
          checkForUpdates(data);
        })
        .catch(error => {
          console.error("Fehler beim Abrufen der Daten: ", error);
        });
    }

    // Initialer Abruf und danach alle 5 Sekunden
    fetchData();
    setInterval(fetchData, 5000);
  </script>
</body>
</html>
