<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
include 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen.");
}

function custom_htmlspecialchars($string) {
    if ($string === null) {
        return "-";
    }

    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function h($value) {
    return custom_htmlspecialchars($value);
}

function h_attr($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function page_url($params = []) {
    $query = array_merge($_GET, $params);

    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        }
    }

    $url = $_SERVER['SCRIPT_NAME'];
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h_attr(csrf_token()) . '">';
}

function require_valid_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die("Ungültige Formularanfrage.");
    }
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function redirect_to_list() {
    header("Location: " . $_SERVER['SCRIPT_NAME']);
    exit;
}

function int_or_null($value) {
    if (!isset($value) || $value === '') {
        return null;
    }

    return (int)$value;
}

function get_lookup_values(PDO $pdo, $table, $idColumn, $descriptionColumn) {
    $allowed = [
        'Wettkaempfe' => ['WettkampfID' => true, 'Beschreibung' => true],
        'Geschlechter' => ['GeschlechtID' => true, 'Beschreibung' => true],
        'Riegen' => ['RiegenID' => true, 'Beschreibung' => true],
        'Vereine' => ['VereinID' => true, 'Vereinsname' => true],
    ];

    if (!isset($allowed[$table][$idColumn], $allowed[$table][$descriptionColumn])) {
        throw new InvalidArgumentException('Ungültige Nachschlagetabelle.');
    }

    $stmt = $pdo->prepare("SELECT $idColumn AS id, $descriptionColumn AS label FROM $table ORDER BY $descriptionColumn");
    $stmt->execute();

    return $stmt->fetchAll();
}

function read_turner_form() {
    return [
        'Vorname' => trim($_POST['Vorname'] ?? ''),
        'Nachname' => trim($_POST['Nachname'] ?? ''),
        'Geburtsdatum' => $_POST['Geburtsdatum'] ?? '',
        'GeschlechtID' => int_or_null($_POST['GeschlechtID'] ?? null) ?? 3,
        'VereinID' => int_or_null($_POST['VereinID'] ?? null),
        'WettkampfID' => int_or_null($_POST['WettkampfID'] ?? null),
        'RiegenID' => int_or_null($_POST['RiegenID'] ?? null),
        'MannschaftsID' => int_or_null($_POST['MannschaftsID'] ?? null),
    ];
}

function render_header($title) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= h($title) ?></title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body { background: #f6f7fb; }
        .page-wrap { max-width: 1280px; }
        .panel {
          background: #fff;
          border-radius: 8px;
          padding: 16px;
          box-shadow: 0 4px 14px rgba(0,0,0,0.07);
        }
        .form-select,
        .form-control { font-size: 1rem; }
        .filter-grid {
          display: grid;
          grid-template-columns: repeat(6, minmax(130px, 1fr));
          gap: 0.75rem;
        }
        .action-group {
          display: flex;
          gap: 0.5rem;
          align-items: center;
        }
        .search-row {
          display: flex;
          gap: 0.5rem;
          align-items: end;
          margin-top: 0.75rem;
        }
        .sort-link {
          color: inherit;
          text-decoration: none;
          white-space: nowrap;
        }
        .sort-link:hover { text-decoration: underline; }
        @media (max-width: 992px) {
          .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
          .filter-grid { grid-template-columns: 1fr; }
          .search-row {
            align-items: stretch;
            flex-direction: column;
          }
          .table-mobile thead { display: none; }
          .table-mobile tr {
            display: block;
            margin-bottom: 0.75rem;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            padding: 0.25rem 0;
            background: #fff;
          }
          .table-mobile td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0.75rem;
            border-top: 1px solid #f0f0f0;
            word-break: break-word;
          }
          .table-mobile td:first-child { border-top: 0; }
          .table-mobile td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 40%;
          }
          .table-mobile .action-cell { justify-content: flex-end; }
          .table-mobile .action-cell::before { content: ""; }
          .action-group {
            flex-direction: column;
            width: 100%;
          }
          .action-group .btn { width: 100%; }
        }
        @media print {
          .action-column,
          .action-cell {
            display: none !important;
          }
        }
      </style>
    </head>
    <body>
      <script src="menu.js"></script>
      <div class="container my-4 page-wrap">
    <?php
}

function render_footer() {
    ?>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

function render_flash($flash) {
    if (!$flash) {
        return;
    }

    $type = $flash['type'] === 'danger' ? 'danger' : 'success';
    ?>
    <div class="alert alert-<?= h_attr($type) ?> alert-dismissible fade show" role="alert">
      <?= h($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
    <?php
}

function render_options($items, $selected, $withEmpty = true) {
    if ($withEmpty) {
        echo '<option value="">-- Bitte auswählen --</option>';
    }

    foreach ($items as $item) {
        $isSelected = ((string)$item['id'] === (string)$selected) ? ' selected' : '';
        echo '<option value="' . h_attr($item['id']) . '"' . $isSelected . '>' . h($item['label']) . '</option>';
    }
}

function render_turner_form($title, $turner, $lookups) {
    render_header($title);
    ?>
    <h1 class="mb-3"><?= h($title) ?></h1>
    <div class="panel">
      <form method="post" action="">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Vorname</label>
            <input type="text" name="Vorname" class="form-control" value="<?= h_attr($turner['Vorname'] ?? '') ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Nachname</label>
            <input type="text" name="Nachname" class="form-control" value="<?= h_attr($turner['Nachname'] ?? '') ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Geburtsdatum</label>
            <input type="date" name="Geburtsdatum" class="form-control" value="<?= h_attr($turner['Geburtsdatum'] ?? '') ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Geschlecht</label>
            <select name="GeschlechtID" class="form-select">
              <?php render_options($lookups['geschlechter'], $turner['GeschlechtID'] ?? 3, false); ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Verein</label>
            <select name="VereinID" class="form-select">
              <?php render_options($lookups['vereine'], $turner['VereinID'] ?? null); ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Wettkampf</label>
            <select name="WettkampfID" class="form-select">
              <?php render_options($lookups['wettkaempfe'], $turner['WettkampfID'] ?? null); ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Riege</label>
            <select name="RiegenID" class="form-select">
              <?php render_options($lookups['riegen'], $turner['RiegenID'] ?? null); ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">MannschaftsID</label>
            <input type="number" name="MannschaftsID" class="form-control" value="<?= h_attr($turner['MannschaftsID'] ?? '') ?>">
          </div>
        </div>
        <div class="d-grid d-md-flex gap-2 mt-3">
          <button type="submit" class="btn btn-primary">Speichern</button>
          <a href="<?= h_attr($_SERVER['SCRIPT_NAME']) ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
      </form>
    </div>
    <?php
    render_footer();
}

function render_delete_page($turner) {
    render_header('Turner löschen');
    ?>
    <h1 class="mb-3">Turner löschen</h1>
    <div class="panel">
      <p>Sind Sie sicher, dass Sie folgenden Turner löschen möchten?</p>
      <p class="fw-semibold"><?= h($turner['Vorname']) ?> <?= h($turner['Nachname']) ?></p>
      <form method="post" action="">
        <?= csrf_field() ?>
        <div class="d-grid d-md-flex gap-2">
          <button type="submit" class="btn btn-danger">Löschen</button>
          <a href="<?= h_attr($_SERVER['SCRIPT_NAME']) ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
      </form>
    </div>
    <?php
    render_footer();
}

function format_date_de($date) {
    if (!$date) {
        return '-';
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed) {
        return '-';
    }

    return $parsed->format('d.m.Y');
}

function sort_link($key, $label, $sort, $direction) {
    $nextDirection = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
    $marker = '';

    if ($sort === $key) {
        $marker = $direction === 'asc' ? ' ▲' : ' ▼';
    }

    return '<a class="sort-link" href="' . h_attr(page_url(['sort' => $key, 'dir' => $nextDirection, 'page' => 1])) . '">' . h($label . $marker) . '</a>';
}

$lookups = [
    'wettkaempfe' => get_lookup_values($pdo, 'Wettkaempfe', 'WettkampfID', 'Beschreibung'),
    'geschlechter' => get_lookup_values($pdo, 'Geschlechter', 'GeschlechtID', 'Beschreibung'),
    'riegen' => get_lookup_values($pdo, 'Riegen', 'RiegenID', 'Beschreibung'),
    'vereine' => get_lookup_values($pdo, 'Vereine', 'VereinID', 'Vereinsname'),
];

$action = $_GET['action'] ?? '';

if ($action === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_valid_csrf();
        $turner = read_turner_form();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO Turner (Vorname, Nachname, Geburtsdatum, GeschlechtID, VereinID, WettkampfID, RiegenID, MannschaftsID)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $turner['Vorname'],
                $turner['Nachname'],
                $turner['Geburtsdatum'],
                $turner['GeschlechtID'],
                $turner['VereinID'],
                $turner['WettkampfID'],
                $turner['RiegenID'],
                $turner['MannschaftsID'],
            ]);
            flash('success', 'Turner wurde hinzugefügt.');
        } catch (PDOException $e) {
            flash('danger', 'Turner konnte nicht hinzugefügt werden.');
        }

        redirect_to_list();
    }

    render_turner_form('Neuen Turner hinzufügen', ['GeschlechtID' => 3], $lookups);
    exit;
}

if ($action === 'edit') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_valid_csrf();
        $turner = read_turner_form();

        try {
            $stmt = $pdo->prepare(
                "UPDATE Turner
                 SET Vorname = ?, Nachname = ?, Geburtsdatum = ?, GeschlechtID = ?, VereinID = ?, WettkampfID = ?, RiegenID = ?, MannschaftsID = ?
                 WHERE TurnerID = ?"
            );
            $stmt->execute([
                $turner['Vorname'],
                $turner['Nachname'],
                $turner['Geburtsdatum'],
                $turner['GeschlechtID'],
                $turner['VereinID'],
                $turner['WettkampfID'],
                $turner['RiegenID'],
                $turner['MannschaftsID'],
                $id,
            ]);
            flash('success', 'Turner wurde gespeichert.');
        } catch (PDOException $e) {
            flash('danger', 'Turner konnte nicht gespeichert werden.');
        }

        redirect_to_list();
    }

    $stmt = $pdo->prepare(
        "SELECT TurnerID, Vorname, Nachname, Geburtsdatum, GeschlechtID, VereinID, WettkampfID, RiegenID, MannschaftsID
         FROM Turner
         WHERE TurnerID = ?"
    );
    $stmt->execute([$id]);
    $turner = $stmt->fetch();

    if (!$turner) {
        flash('danger', 'Turner wurde nicht gefunden.');
        redirect_to_list();
    }

    render_turner_form('Turner bearbeiten', $turner, $lookups);
    exit;
}

if ($action === 'delete') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $stmt = $pdo->prepare("SELECT TurnerID, Vorname, Nachname FROM Turner WHERE TurnerID = ?");
    $stmt->execute([$id]);
    $turner = $stmt->fetch();

    if (!$turner) {
        flash('danger', 'Turner wurde nicht gefunden.');
        redirect_to_list();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_valid_csrf();

        try {
            $stmt = $pdo->prepare("DELETE FROM Turner WHERE TurnerID = ?");
            $stmt->execute([$id]);
            flash('success', 'Turner wurde gelöscht.');
        } catch (PDOException $e) {
            flash('danger', 'Turner konnte nicht gelöscht werden.');
        }

        redirect_to_list();
    }

    render_delete_page($turner);
    exit;
}

$search = trim($_GET['q'] ?? '');
$filters = [
    'GeschlechtID' => int_or_null($_GET['GeschlechtID'] ?? null),
    'VereinID' => int_or_null($_GET['VereinID'] ?? null),
    'WettkampfID' => int_or_null($_GET['WettkampfID'] ?? null),
    'RiegenID' => int_or_null($_GET['RiegenID'] ?? null),
];

$sortColumns = [
    'vorname' => 't.Vorname',
    'nachname' => 't.Nachname',
    'geburtsdatum' => 't.Geburtsdatum',
    'geschlecht' => 'g.Beschreibung',
    'verein' => 'v.Vereinsname',
    'wettkampf' => 'w.Beschreibung',
    'riege' => 'r.Beschreibung',
    'mannschaft' => 't.MannschaftsID',
];

$sort = $_GET['sort'] ?? 'nachname';
if (!isset($sortColumns[$sort])) {
    $sort = 'nachname';
}

$direction = strtolower($_GET['dir'] ?? 'asc');
if (!in_array($direction, ['asc', 'desc'], true)) {
    $direction = 'asc';
}

$perPageOptions = [10, 25, 50, 100, 'all'];
$perPageParam = $_GET['per_page'] ?? 25;
if ($perPageParam !== 'all') {
    $perPageParam = (int)$perPageParam;
}
if (!in_array($perPageParam, $perPageOptions, true)) {
    $perPageParam = 25;
}
$showAll = $perPageParam === 'all';
$perPage = $showAll ? null : $perPageParam;

$page = max(1, (int)($_GET['page'] ?? 1));
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(t.Vorname LIKE ? OR t.Nachname LIKE ? OR v.Vereinsname LIKE ? OR w.Beschreibung LIKE ? OR r.Beschreibung LIKE ? OR CAST(t.MannschaftsID AS CHAR) LIKE ?)";
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term, $term, $term, $term);
}

foreach ($filters as $column => $value) {
    if ($value !== null) {
        $where[] = "t.$column = ?";
        $params[] = $value;
    }
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM Turner t
     LEFT JOIN Geschlechter g ON g.GeschlechtID = t.GeschlechtID
     LEFT JOIN Vereine v ON v.VereinID = t.VereinID
     LEFT JOIN Wettkaempfe w ON w.WettkampfID = t.WettkampfID
     LEFT JOIN Riegen r ON r.RiegenID = t.RiegenID
     $whereSql"
);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = $showAll ? 1 : max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = $showAll ? 0 : ($page - 1) * $perPage;

$listSql = "
    SELECT
        t.TurnerID,
        t.Vorname,
        t.Nachname,
        t.Geburtsdatum,
        t.GeschlechtID,
        t.VereinID,
        t.WettkampfID,
        t.RiegenID,
        t.MannschaftsID,
        g.Beschreibung AS Geschlecht,
        v.Vereinsname AS Verein,
        w.Beschreibung AS Wettkampf,
        r.Beschreibung AS Riege
    FROM Turner t
    LEFT JOIN Geschlechter g ON g.GeschlechtID = t.GeschlechtID
    LEFT JOIN Vereine v ON v.VereinID = t.VereinID
    LEFT JOIN Wettkaempfe w ON w.WettkampfID = t.WettkampfID
    LEFT JOIN Riegen r ON r.RiegenID = t.RiegenID
    $whereSql
    ORDER BY {$sortColumns[$sort]} $direction, t.Nachname ASC, t.Vorname ASC";

if (!$showAll) {
    $listSql .= " LIMIT ? OFFSET ?";
}

$listStmt = $pdo->prepare($listSql);
foreach ($params as $index => $value) {
    $listStmt->bindValue($index + 1, $value);
}
if (!$showAll) {
    $listStmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $listStmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
}
$listStmt->execute();
$turnerListe = $listStmt->fetchAll();

render_header('Turner Verwaltung');
$flash = take_flash();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <h1 class="m-0">Turner Verwaltung</h1>
  <a href="<?= h_attr(page_url(['action' => 'add', 'id' => null, 'page' => null])) ?>" class="btn btn-success">Neuen Turner hinzufügen</a>
</div>

<?php render_flash($flash); ?>

<form method="get" action="<?= h_attr($_SERVER['SCRIPT_NAME']) ?>" class="panel mb-3" id="filterForm">
  <div class="filter-grid">
    <div>
      <label class="form-label">Suche</label>
      <input type="search" name="q" class="form-control" value="<?= h_attr($search) ?>" placeholder="Name, Verein, Wettkampf">
    </div>
    <div>
      <label class="form-label">Geschlecht</label>
      <select name="GeschlechtID" class="form-select">
        <?php render_options($lookups['geschlechter'], $filters['GeschlechtID']); ?>
      </select>
    </div>
    <div>
      <label class="form-label">Verein</label>
      <select name="VereinID" class="form-select">
        <?php render_options($lookups['vereine'], $filters['VereinID']); ?>
      </select>
    </div>
    <div>
      <label class="form-label">Wettkampf</label>
      <select name="WettkampfID" class="form-select">
        <?php render_options($lookups['wettkaempfe'], $filters['WettkampfID']); ?>
      </select>
    </div>
    <div>
      <label class="form-label">Riege</label>
      <select name="RiegenID" class="form-select">
        <?php render_options($lookups['riegen'], $filters['RiegenID']); ?>
      </select>
    </div>
    <div>
      <label class="form-label">Pro Seite</label>
      <select name="per_page" class="form-select">
        <?php foreach ($perPageOptions as $option): ?>
          <option value="<?= h_attr($option) ?>" <?= $option === $perPageParam ? 'selected' : '' ?>><?= h($option === 'all' ? 'Alle' : $option) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <input type="hidden" name="sort" value="<?= h_attr($sort) ?>">
  <input type="hidden" name="dir" value="<?= h_attr($direction) ?>">
  <div class="search-row">
    <a href="<?= h_attr($_SERVER['SCRIPT_NAME']) ?>" class="btn btn-secondary">Zurücksetzen</a>
  </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <div class="text-muted">
    <?= h($totalRows) ?> Einträge
    <?php if ($totalRows > 0): ?>
      · Seite <?= h($page) ?> von <?= h($totalPages) ?>
      · <?= h($showAll ? 'Alle' : $perPage) ?> pro Seite
    <?php endif; ?>
  </div>
  <div class="text-muted small">
    Spaltenüberschriften sind anklickbar und sortierbar.
  </div>
</div>

<div class="table-responsive panel">
  <table class="table table-striped table-mobile align-middle mb-0">
    <thead>
      <tr>
        <th><?= sort_link('vorname', 'Vorname', $sort, $direction) ?></th>
        <th><?= sort_link('nachname', 'Nachname', $sort, $direction) ?></th>
        <th><?= sort_link('geburtsdatum', 'Geburtsdatum', $sort, $direction) ?></th>
        <th><?= sort_link('geschlecht', 'Geschlecht', $sort, $direction) ?></th>
        <th><?= sort_link('verein', 'Verein', $sort, $direction) ?></th>
        <th><?= sort_link('wettkampf', 'Wettkampf', $sort, $direction) ?></th>
        <th><?= sort_link('riege', 'Riege', $sort, $direction) ?></th>
        <th><?= sort_link('mannschaft', 'MannschaftsID', $sort, $direction) ?></th>
        <th class="action-column">Aktionen</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$turnerListe): ?>
        <tr>
          <td colspan="9" class="text-center text-muted py-4">Keine Einträge gefunden.</td>
        </tr>
      <?php endif; ?>
      <?php foreach ($turnerListe as $turner): ?>
      <tr>
        <td data-label="Vorname"><?= h($turner['Vorname']) ?></td>
        <td data-label="Nachname"><?= h($turner['Nachname']) ?></td>
        <td data-label="Geburtsdatum"><?= h(format_date_de($turner['Geburtsdatum'])) ?></td>
        <td data-label="Geschlecht"><?= h($turner['Geschlecht']) ?></td>
        <td data-label="Verein"><?= h($turner['Verein']) ?></td>
        <td data-label="Wettkampf"><?= h($turner['Wettkampf']) ?></td>
        <td data-label="Riege"><?= h($turner['Riege']) ?></td>
        <td data-label="MannschaftsID"><?= h($turner['MannschaftsID']) ?></td>
        <td data-label="Aktionen" class="action-cell">
          <div class="action-group">
            <button
              type="button"
              class="btn btn-primary btn-sm"
              data-bs-toggle="modal"
              data-bs-target="#editTurnerModal<?= h_attr((int)$turner['TurnerID']) ?>"
            >
              Bearbeiten
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php foreach ($turnerListe as $turner): ?>
  <?php $turnerId = (int)$turner['TurnerID']; ?>
  <div class="modal fade" id="editTurnerModal<?= h_attr($turnerId) ?>" tabindex="-1" aria-labelledby="editTurnerLabel<?= h_attr($turnerId) ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTurnerLabel<?= h_attr($turnerId) ?>">Turner bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <div class="modal-body">
          <form method="post" action="<?= h_attr(page_url(['action' => 'edit', 'id' => $turnerId])) ?>" id="editTurnerForm<?= h_attr($turnerId) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="Vorname<?= h_attr($turnerId) ?>" class="form-label">Vorname</label>
                <input type="text" name="Vorname" id="Vorname<?= h_attr($turnerId) ?>" class="form-control" value="<?= h_attr($turner['Vorname']) ?>" required>
              </div>
              <div class="col-12 col-md-6">
                <label for="Nachname<?= h_attr($turnerId) ?>" class="form-label">Nachname</label>
                <input type="text" name="Nachname" id="Nachname<?= h_attr($turnerId) ?>" class="form-control" value="<?= h_attr($turner['Nachname']) ?>" required>
              </div>
              <div class="col-12 col-md-6">
                <label for="Geburtsdatum<?= h_attr($turnerId) ?>" class="form-label">Geburtsdatum</label>
                <input type="date" name="Geburtsdatum" id="Geburtsdatum<?= h_attr($turnerId) ?>" class="form-control" value="<?= h_attr($turner['Geburtsdatum']) ?>" required>
              </div>
              <div class="col-12 col-md-6">
                <label for="GeschlechtID<?= h_attr($turnerId) ?>" class="form-label">Geschlecht</label>
                <select name="GeschlechtID" id="GeschlechtID<?= h_attr($turnerId) ?>" class="form-select">
                  <?php render_options($lookups['geschlechter'], $turner['GeschlechtID'], false); ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label for="VereinID<?= h_attr($turnerId) ?>" class="form-label">Verein</label>
                <select name="VereinID" id="VereinID<?= h_attr($turnerId) ?>" class="form-select">
                  <?php render_options($lookups['vereine'], $turner['VereinID']); ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label for="WettkampfID<?= h_attr($turnerId) ?>" class="form-label">Wettkampf</label>
                <select name="WettkampfID" id="WettkampfID<?= h_attr($turnerId) ?>" class="form-select">
                  <?php render_options($lookups['wettkaempfe'], $turner['WettkampfID']); ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label for="RiegenID<?= h_attr($turnerId) ?>" class="form-label">Riege</label>
                <select name="RiegenID" id="RiegenID<?= h_attr($turnerId) ?>" class="form-select">
                  <?php render_options($lookups['riegen'], $turner['RiegenID']); ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label for="MannschaftsID<?= h_attr($turnerId) ?>" class="form-label">MannschaftsID</label>
                <input type="number" name="MannschaftsID" id="MannschaftsID<?= h_attr($turnerId) ?>" class="form-control" value="<?= h_attr($turner['MannschaftsID']) ?>">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer justify-content-between">
          <form method="post" action="<?= h_attr(page_url(['action' => 'delete', 'id' => $turnerId])) ?>" onsubmit="return confirm('Wollen Sie diesen Turner wirklich löschen?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editTurnerForm<?= h_attr($turnerId) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($totalPages > 1): ?>
  <nav class="mt-3" aria-label="Seitennavigation">
    <ul class="pagination flex-wrap">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= h_attr(page_url(['page' => max(1, $page - 1)])) ?>">Zurück</a>
      </li>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= h_attr(page_url(['page' => $i])) ?>"><?= h($i) ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= h_attr(page_url(['page' => min($totalPages, $page + 1)])) ?>">Weiter</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<script>
  (() => {
    const form = document.getElementById('filterForm');
    if (!form) {
      return;
    }

    let searchTimer = null;
    const submitForm = () => form.requestSubmit ? form.requestSubmit() : form.submit();

    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', submitForm);
    });

    const search = form.querySelector('input[type="search"]');
    if (search) {
      search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(submitForm, 500);
      });
    }
  })();
</script>

<?php
render_footer();
