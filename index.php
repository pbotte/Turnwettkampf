<?php
require_once 'includes/layout.php';

render_header('Turnwettkampf-Verwaltung', [
    'extraCss' => '    @import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css");',
]);
?>
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
<?php render_footer(); ?>
