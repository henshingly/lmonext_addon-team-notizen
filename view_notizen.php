<?php
/**
 * Project: LMOnext
 * Filename: addon/team-notizen/view_notizen.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Admin-View: Team-Notizen ──────────────────────────────────────────────────
 *
 * Team-Auswahl -> Stammdaten pflegen (Gruendung, Stadion, Farben, Erfolge, Info)
 * -> Trainer-Historie mit von/bis-Daten pflegen.
 */
declare(strict_types=1);

// SICHERHEITSFIX (Beitrag: Integrationsprüfung des Addons vor Übernahme):
// requireLogin() fehlte hier komplett - diese Ansicht (und die
// Team-Stammdaten/Trainer-Historie darin) wäre ohne diese Zeile für JEDEN
// ohne Login direkt per admin.php?action=team_notizen erreichbar gewesen.
// Siehe auch handler_notizen.php für den identischen Fix bei den drei
// POST-Handlern dieses Addons.
requireLogin();

ensureTeamNotizenTables();

// ── Team-Auswahl ─────────────────────────────────────────────────────────────
$db = getDB();
$teamId = (int)($_GET['team_id'] ?? 0);

// Alle Teams fuer Dropdown
$teamsStmt = $db->query('SELECT id, name, kurz, mittel FROM ' . tbl('teams_global') . ' ORDER BY name');
$allTeams = $teamsStmt ? $teamsStmt->fetchAll() : [];

// ── Daten fuer ausgewaehltes Team laden ──────────────────────────────────────
$teamInfo  = null;
$trainer   = [];
$editTrainer = null;

if ($teamId > 0) {
    // Team-Stammdaten
    $sInfo = $db->prepare('SELECT * FROM ' . tbl('team_notizen') . ' WHERE team_id = ?');
    $sInfo->execute([$teamId]);
    $teamInfo = $sInfo->fetch();

    // Trainer-Historie (neueste zuerst, aktuelle ohne bis oben)
    $sTrainer = $db->prepare(
        'SELECT * FROM ' . tbl('team_trainer') . ' WHERE team_id = ? ORDER BY bis IS NULL DESC, COALESCE(von, "1900-01-01") DESC, sort_order DESC'
    );
    $sTrainer->execute([$teamId]);
    $trainer = $sTrainer->fetchAll();

    // Trainer bearbeiten?
    $editId = (int)($_GET['edit_trainer'] ?? 0);
    if ($editId > 0) {
        $sEdit = $db->prepare('SELECT * FROM ' . tbl('team_trainer') . ' WHERE id = ?');
        $sEdit->execute([$editId]);
        $editTrainer = $sEdit->fetch();
    }
}

$pageTitle = '📋 ' . t('tn_page_title');
?>

<div class="content-inner" style="padding:24px">

  <!-- ── Team-Auswahl ─────────────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:20px">
    <h2 style="font-size:.8rem;font-weight:600;margin-bottom:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('tn_label_select_team')) ?></h2>
    <form method="get" action="" style="display:flex;gap:10px;align-items:center">
      <input type="hidden" name="action" value="team_notizen">
      <select name="team_id" onchange="this.form.submit()"
              style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem;min-width:260px">
        <option value="0"><?= h(t('tn_option_choose_team')) ?></option>
        <?php foreach ($allTeams as $team): ?>
          <option value="<?= (int)$team['id'] ?>" <?= $teamId === (int)$team['id'] ? 'selected' : '' ?>>
            <?= h($team['name']) ?><?= $team['kurz'] !== '' ? ' (' . h($team['kurz']) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($teamId > 0):
    $team = $allTeams[array_filter($allTeams, fn($t) => (int)$t['id'] === $teamId)[0] ?? -1] ?? null;
    $teamName = $team['name'] ?? t('tn_team_fallback_name', ['id' => $teamId]);
  ?>

    <!-- ── Stammdaten ─────────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:20px">
      <h2 style="font-size:.8rem;font-weight:600;margin-bottom:14px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">
        <?= h(t('tn_heading_stammdaten', ['team' => $teamName])) ?>
      </h2>
      <form method="post" action="?action=team_info_save">
        <?= csrfField() ?>
        <input type="hidden" name="team_id" value="<?= $teamId ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div>
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_gruendung')) ?></label>
            <input type="text" name="gruendung" placeholder="z.B. 1908" maxlength="20"
                   value="<?= $teamInfo ? h($teamInfo['gruendung']) : '' ?>"
                   style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
          <div>
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_stadion')) ?></label>
            <input type="text" name="stadion" placeholder="z.B. Allianz Arena" maxlength="200"
                   value="<?= $teamInfo ? h($teamInfo['stadion']) : '' ?>"
                   style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
          <div>
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_vereinsfarben')) ?></label>
            <input type="text" name="vereinsfarben" placeholder="z.B. Rot-Weiß" maxlength="100"
                   value="<?= $teamInfo ? h($teamInfo['vereinsfarben']) : '' ?>"
                   style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
          <div>
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_homepage')) ?></label>
            <input type="text" name="homepage" placeholder="https://..." maxlength="500"
                   value="<?= $teamInfo ? h($teamInfo['homepage']) : '' ?>"
                   style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
        </div>
        <div style="margin-bottom:14px">
          <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_erfolge')) ?></label>
          <textarea name="erfolge" placeholder="z.B.&#10;2023 Meisterschaft&#10;2021 Pokalsieger"
                  style="width:100%;min-height:70px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:10px 12px;font-size:.9rem;font-family:inherit;resize:vertical"><?= $teamInfo ? h($teamInfo['erfolge']) : '' ?></textarea>
        </div>
        <div style="margin-bottom:14px">
          <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_info')) ?></label>
          <textarea name="info_text" placeholder="Kurze Beschreibung des Vereins..."
                  style="width:100%;min-height:90px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:10px 12px;font-size:.9rem;font-family:inherit;resize:vertical"><?= $teamInfo ? h($teamInfo['info_text']) : '' ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="text-decoration:none"><?= h(t('tn_btn_save_stammdaten')) ?></button>
      </form>
    </div>

    <!-- ── Trainer-Historie ──────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:20px">
      <h2 style="font-size:.8rem;font-weight:600;margin-bottom:14px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">
        <?= $editTrainer ? h(t('tn_heading_trainer_edit')) : h(t('tn_heading_trainer_add')) ?>
      </h2>
      <form method="post" action="?action=trainer_save">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $editTrainer ? (int)$editTrainer['id'] : 0 ?>">
        <input type="hidden" name="team_id" value="<?= $teamId ?>">
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;align-items:end">
          <div style="flex:1;min-width:200px">
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_trainer_name')) ?></label>
            <input type="text" name="trainer_name" placeholder="z.B. Jürgen Klopp"
                   value="<?= $editTrainer ? h($editTrainer['trainer_name']) : '' ?>"
                   style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
          <div>
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_von')) ?></label>
            <input type="date" name="von"
                   value="<?= $editTrainer && $editTrainer['von'] ? h($editTrainer['von']) : '' ?>"
                   style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
          <div>
            <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_bis')) ?></label>
            <input type="date" name="bis"
                   value="<?= $editTrainer && $editTrainer['bis'] ? h($editTrainer['bis']) : '' ?>"
                   placeholder="<?= h(t('tn_placeholder_bis_leer')) ?>"
                   style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          </div>
        </div>
        <div style="margin-bottom:12px">
          <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('tn_label_notiz')) ?></label>
          <input type="text" name="notiz" placeholder="z.B. interim, Doppellizenz..."
                 value="<?= $editTrainer ? h($editTrainer['notiz']) : '' ?>"
                 style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
        </div>
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary btn-sm" style="text-decoration:none">
            ✓ <?= $editTrainer ? h(t('tn_btn_speichern')) : h(t('tn_btn_hinzufuegen')) ?>
          </button>
          <?php if ($editTrainer): ?>
            <a href="?action=team_notizen&team_id=<?= $teamId ?>" class="btn btn-muted btn-sm" style="text-decoration:none"><?= h(t('tn_btn_abbrechen')) ?></a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ── Trainer-Liste ─────────────────────────────────────────────── -->
    <?php if (empty($trainer)): ?>
      <div class="card" style="text-align:center;padding:24px;color:var(--muted)">
        <p style="font-size:.9rem"><?= h(t('tn_empty_trainer')) ?></p>
      </div>
    <?php else: ?>
      <div class="card" style="padding:0;overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:.88rem">
          <thead>
            <tr style="background:var(--bg-alt,#f4f6fa);border-bottom:1px solid var(--border)">
              <th style="text-align:left;padding:10px 14px;font-weight:600;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px"><?= h(t('tn_col_trainer')) ?></th>
              <th style="text-align:left;padding:10px 14px;font-weight:600;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px"><?= h(t('tn_label_von')) ?></th>
              <th style="text-align:left;padding:10px 14px;font-weight:600;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px"><?= h(t('tn_label_bis')) ?></th>
              <th style="text-align:left;padding:10px 14px;font-weight:600;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px"><?= h(t('tn_col_notiz')) ?></th>
              <th style="width:1%;padding:10px 14px"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trainer as $t):
              $isCurrent = $t['bis'] === null;
              $vonTxt = $t['von'] ? date('d.m.Y', strtotime($t['von'])) : '';
              $bisTxt = $t['bis'] ? date('d.m.Y', strtotime($t['bis'])) : '<span style="color:#22c55e;font-weight:600">' . h(t('tn_aktuell')) . '</span>';
            ?>
              <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:10px 14px;font-weight:600"><?= h($t['trainer_name']) ?></td>
                <td style="padding:10px 14px;color:var(--muted)"><?= $vonTxt ?></td>
                <td style="padding:10px 14px"><?= $bisTxt ?></td>
                <td style="padding:10px 14px;color:var(--muted)"><?= h($t['notiz'] ?? '') ?></td>
                <td style="padding:10px 14px;white-space:nowrap">
                  <a href="?action=team_notizen&team_id=<?= $teamId ?>&edit_trainer=<?= (int)$t['id'] ?>"
                     style="color:var(--muted);text-decoration:none;font-size:.85rem" title="<?= h(t('tn_title_edit')) ?>">✏️</a>
                  <form method="post" action="?action=trainer_delete" style="display:inline;margin-left:6px"
                        onsubmit="return confirm('<?= h(t('tn_confirm_delete_trainer')) ?>')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                    <button type="submit" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:.85rem;padding:0" title="<?= h(t('tn_title_delete')) ?>">🗑️</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php elseif ($teamId === 0): ?>
    <div class="card" style="text-align:center;padding:48px;color:var(--muted)">
      <p style="font-size:1.1rem">📋 <?= h(t('tn_page_title')) ?></p>
      <p style="font-size:.85rem;margin-top:8px"><?= h(t('tn_empty_hint')) ?></p>
      <p style="font-size:.78rem;margin-top:16px">
        <?= h(t('tn_empty_features')) ?>
      </p>
    </div>
  <?php endif; ?>

</div>
