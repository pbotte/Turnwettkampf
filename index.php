<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Turnwettkampf-Verwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      body {
        background: #f6f7fb;
      }
      .page-wrap {
        max-width: 1200px;
      }
      .panel {
        background: #fff;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
      }
      .link-btn {
        text-align: left;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        white-space: normal;
      }
      .section-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 12px;
      }
    </style>
  </head>
  <body>
    <script src="menu.js"></script>
    <div class="container my-4 page-wrap">
      <div class="panel mb-4">
        <h1 class="mb-1">Turnwettkampf-Verwaltung</h1>
        <div class="text-muted">Schnellzugriff</div>
      </div>

      <div class="panel mb-4">
        <div class="section-title">Übersicht Funktionen</div>
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-primary w-100 link-btn" href="riegen.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-people"></i>Riegen</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-primary w-100 link-btn" href="kari.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-pencil-square"></i>Kari-Wertungseingabe</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-primary w-100 link-btn" href="ergebnisse.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-clipboard-data"></i>Wettkampf Ergebnisse</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="section-title">Wettkampfleitung Übersicht Funktionen</div>
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="anzeige.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-display"></i>Wertungsanzeige</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="geraete_verwaltung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-tools"></i>Geräte Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="geraetetypen_verwaltung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-tags"></i>Gerätetypen Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="durchgaenge.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-calendar3"></i>Durchgänge Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="riegen_verwaltung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-people-fill"></i>Riegen Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="durchgaenge_zuordnung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-diagram-3"></i>Riegen &lt;-&gt; Geräte &lt;-&gt; Durchgänge Zuordnung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="wettkaempfe_verwaltung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-trophy"></i>Wettkämpfe Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="vereine_verwaltung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-building"></i>Vereine Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="turner_verwaltung.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-person-badge"></i>Turner Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="wertungen.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-list-check"></i>Wertungen Verwaltung</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
          <div class="col-12 col-md-4">
            <a class="btn btn-outline-secondary w-100 link-btn" href="berechnungen.php">
              <span class="d-flex align-items-center gap-2"><i class="bi bi-calculator"></i>Punkte zusammenzählen und Plätze vergeben</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
