<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';
require_once 'includes/assignment_repository.php';

$pdo = db();

$message = '';

// Formularverarbeitung: Beim Klick auf "Speichern" wird die gesamte Matrix verarbeitet.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    try {
        save_assignments($pdo, $_POST['geraet'] ?? []);
        $message = "Speichern erfolgreich.";
    } catch (Exception $e) {
        $message = "Fehler beim Speichern: " . $e->getMessage();
    }
    redirect_with_message($message);
}

// Falls eine Nachricht via GET übergeben wurde
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

$pageData = load_assignment_page_data($pdo);
$riegen = $pageData['riegen'];
$durchgangGroups = $pageData['durchgangGroups'];
$geraete = $pageData['geraete'];
$geraeteById = $pageData['geraeteById'];
$assignments = $pageData['assignments'];
render_header('Verbindung Durchgaenge Riegen Geraete');
?>
<div class="container my-4 page-wrap assignment-page">
  <div class="assignment-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <h1 class="m-0">Riegenlaufplan</h1>
      <div class="text-muted">Zuordnung von Riegen, Durchgängen und Geräten</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-secondary" id="markEmptyButton">Leere markieren</button>
      <button type="submit" form="zuordnungForm" class="btn btn-primary">Speichern</button>
    </div>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
  <?php endif; ?>
  
  <form method="post" action="<?= h_attr($_SERVER['PHP_SELF']) ?>" id="zuordnungForm">
    <input type="hidden" name="action" value="save">
    <?php if (!$durchgangGroups): ?>
      <div class="alert alert-warning">Keine Durchgänge gefunden.</div>
    <?php else: ?>
      <div class="panel assignment-panel">
        <ul class="nav nav-tabs assignment-tabs" id="durchgangTabs" role="tablist">
          <?php $groupIndex = 0; ?>
          <?php foreach ($durchgangGroups as $groupLabel => $groupDurchgaenge): ?>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link <?= $groupIndex === 0 ? 'active' : '' ?>"
                id="tab-<?= h_attr((string)$groupIndex) ?>"
                data-bs-toggle="tab"
                data-bs-target="#panel-<?= h_attr((string)$groupIndex) ?>"
                type="button"
                role="tab"
                aria-controls="panel-<?= h_attr((string)$groupIndex) ?>"
                aria-selected="<?= $groupIndex === 0 ? 'true' : 'false' ?>"
              >
                <?= h($groupLabel) ?>
              </button>
            </li>
            <?php $groupIndex++; ?>
          <?php endforeach; ?>
        </ul>

        <div class="tab-content assignment-tab-content" id="durchgangTabContent">
          <?php $groupIndex = 0; ?>
          <?php foreach ($durchgangGroups as $groupLabel => $groupDurchgaenge): ?>
            <section
              class="tab-pane fade <?= $groupIndex === 0 ? 'show active' : '' ?>"
              id="panel-<?= h_attr((string)$groupIndex) ?>"
              role="tabpanel"
              aria-labelledby="tab-<?= h_attr((string)$groupIndex) ?>"
              tabindex="0"
              data-group-index="<?= h_attr((string)$groupIndex) ?>"
            >
              <div class="assignment-section-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                  <h2 class="h5 m-0"><?= h($groupLabel) ?></h2>
                  <div class="text-muted small"><?= h(count($groupDurchgaenge)) ?> Gerätepositionen, <?= h(count($riegen)) ?> Riegen</div>
                </div>
                <div class="assignment-tools d-flex flex-wrap gap-2">
                  <select class="form-select form-select-sm copy-source-select" aria-label="Quelle für Durchgang kopieren">
                    <?php $sourceIndex = 0; ?>
                    <?php foreach ($durchgangGroups as $sourceLabel => $_sourceDurchgaenge): ?>
                      <option value="<?= h_attr((string)$sourceIndex) ?>" <?= $sourceIndex === max(0, $groupIndex - 1) ? 'selected' : '' ?>>
                        <?= h($sourceLabel) ?>
                      </option>
                      <?php $sourceIndex++; ?>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="btn btn-sm btn-outline-secondary copy-tab-button">Durchgang kopieren</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary reset-tab-button">Zurücksetzen</button>
                </div>
              </div>

              <div class="table-responsive assignment-table-wrap">
                <table class="table table-bordered assignment-table table-mobile align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Riege</th>
                      <?php foreach ($groupDurchgaenge as $durchgang): ?>
                        <th><?= h(durchgang_slot_label($durchgang)) ?></th>
                      <?php endforeach; ?>
                      <th class="action-column">Aktionen</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riegen as $riegeIndex => $riege): ?>
                      <?php $riegeParts = riegen_label_parts((string)$riege['Beschreibung']); ?>
                      <tr data-riege-index="<?= h_attr((string)$riegeIndex) ?>">
                        <td data-label="Riege" class="assignment-riege-cell">
                          <div class="assignment-riege-title" title="<?= h_attr($riege['Beschreibung']) ?>"><?= h($riegeParts['title']) ?></div>
                          <?php if ($riegeParts['meta'] !== ''): ?>
                            <div class="assignment-riege-meta"><?= h($riegeParts['meta']) ?></div>
                          <?php endif; ?>
                        </td>
                        <?php foreach ($groupDurchgaenge as $durchgang): ?>
                          <?php
                            $selected = $assignments[$riege['RiegenID']][$durchgang['DurchgangID']] ?? '';
                            $selectedType = isset($geraeteById[$selected]) ? (int)($geraeteById[$selected]['GeraeteTypID'] ?? 0) : 0;
                          ?>
                          <td data-label="<?= h_attr(durchgang_slot_label($durchgang)) ?>">
                            <select
                              class="form-select matrix-select assignment-select <?= $selected === '' ? 'is-empty' : '' ?>"
                              name="geraet[<?= h($riege['RiegenID']) ?>][<?= h($durchgang['DurchgangID']) ?>]"
                              data-original="<?= h_attr($selected) ?>"
                              data-device-type="<?= h_attr((string)$selectedType) ?>"
                            >
                              <option value="" data-device-type="0">-- bitte wählen --</option>
                              <?php foreach ($geraete as $geraet): ?>
                                <?php $deviceType = (int)($geraet['GeraeteTypID'] ?? 0); ?>
                                <option
                                  value="<?= h($geraet['GeraetID']) ?>"
                                  data-device-type="<?= h_attr((string)$deviceType) ?>"
                                  <?= ($geraet['GeraetID'] == $selected ? 'selected' : '') ?>
                                >
                                  <?= h($geraet['Beschreibung']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                        <?php endforeach; ?>
                        <td data-label="Aktionen" class="action-cell">
                          <button type="button" class="btn btn-sm btn-outline-secondary copy-previous-row-button" <?= $riegeIndex === 0 ? 'disabled' : '' ?>>
                            Vorzeile übernehmen
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </section>
            <?php $groupIndex++; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="assignment-savebar">
      <div class="text-muted small" id="assignmentStatus">Änderungen werden erst nach dem Speichern übernommen.</div>
      <button type="submit" class="btn btn-primary">Speichern</button>
    </div>
  </form>
</div>
<script>
  (function() {
    const form = document.getElementById('zuordnungForm');
    const markEmptyButton = document.getElementById('markEmptyButton');
    const status = document.getElementById('assignmentStatus');
    let markEmpty = false;

    const selects = () => Array.from(document.querySelectorAll('.assignment-select'));

    const typeClassPrefix = 'device-type-';
    const updateSelectState = (select) => {
      select.classList.toggle('is-empty', select.value === '');
      Array.from(select.classList).forEach((className) => {
        if (className.startsWith(typeClassPrefix)) {
          select.classList.remove(className);
        }
      });

      const selectedOption = select.options[select.selectedIndex];
      const type = selectedOption ? selectedOption.dataset.deviceType : '0';
      select.dataset.deviceType = type || '0';
      if (select.value !== '' && type && type !== '0') {
        select.classList.add(typeClassPrefix + (Number(type) % 10));
      }
    };

    const updateEmptyHighlights = () => {
      selects().forEach((select) => {
        select.classList.toggle('highlight-empty', markEmpty && select.value === '');
      });
    };

    selects().forEach((select) => {
      updateSelectState(select);
      select.addEventListener('change', () => {
        updateSelectState(select);
        updateEmptyHighlights();
        if (status) status.textContent = 'Ungespeicherte Änderungen vorhanden.';
      });
    });

    if (markEmptyButton) {
      markEmptyButton.addEventListener('click', () => {
        markEmpty = !markEmpty;
        markEmptyButton.classList.toggle('btn-warning', markEmpty);
        markEmptyButton.classList.toggle('btn-outline-secondary', !markEmpty);
        markEmptyButton.textContent = markEmpty ? 'Markierung aus' : 'Leere markieren';
        updateEmptyHighlights();
      });
    }

    document.querySelectorAll('.copy-previous-row-button').forEach((button) => {
      button.addEventListener('click', () => {
        const row = button.closest('tr');
        const previousRow = row ? row.previousElementSibling : null;
        if (!row || !previousRow) return;

        const targetSelects = Array.from(row.querySelectorAll('.assignment-select'));
        const sourceSelects = Array.from(previousRow.querySelectorAll('.assignment-select'));
        targetSelects.forEach((select, index) => {
          if (sourceSelects[index]) {
            select.value = sourceSelects[index].value;
            updateSelectState(select);
          }
        });
        updateEmptyHighlights();
        if (status) status.textContent = 'Vorzeile übernommen. Speichern nicht vergessen.';
      });
    });

    document.querySelectorAll('.reset-tab-button').forEach((button) => {
      button.addEventListener('click', () => {
        const pane = button.closest('.tab-pane');
        if (!pane) return;
        pane.querySelectorAll('.assignment-select').forEach((select) => {
          select.value = select.dataset.original || '';
          updateSelectState(select);
        });
        updateEmptyHighlights();
        if (status) status.textContent = 'Durchgang auf gespeicherten Stand zurückgesetzt.';
      });
    });

    document.querySelectorAll('.copy-tab-button').forEach((button) => {
      button.addEventListener('click', () => {
        const targetPane = button.closest('.tab-pane');
        const sourceSelect = targetPane ? targetPane.querySelector('.copy-source-select') : null;
        const sourcePane = sourceSelect ? document.querySelector('[data-group-index="' + sourceSelect.value + '"]') : null;
        if (!targetPane || !sourcePane || targetPane === sourcePane) return;

        const targetRows = Array.from(targetPane.querySelectorAll('tbody tr'));
        const sourceRows = Array.from(sourcePane.querySelectorAll('tbody tr'));
        targetRows.forEach((targetRow, rowIndex) => {
          const targetSelects = Array.from(targetRow.querySelectorAll('.assignment-select'));
          const sourceSelects = sourceRows[rowIndex] ? Array.from(sourceRows[rowIndex].querySelectorAll('.assignment-select')) : [];
          targetSelects.forEach((select, index) => {
            if (sourceSelects[index]) {
              select.value = sourceSelects[index].value;
              updateSelectState(select);
            }
          });
        });
        updateEmptyHighlights();
        if (status) status.textContent = 'Durchgang kopiert. Speichern nicht vergessen.';
      });
    });

    if (form) {
      form.addEventListener('submit', () => {
        if (status) status.textContent = 'Speichern...';
      });
    }
  })();
</script>
<?php render_footer(); ?>
