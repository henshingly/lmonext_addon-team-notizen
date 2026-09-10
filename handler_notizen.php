<?php
/**
 * Project: LMOnext
 * Filename: addon/team-notizen/handler_notizen.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── POST-Handler fuer Team-Notizen ──────────────────────────────────────────
 *
 * Behandelt alle POST-Actions:
 *   - team_info_save:   Team-Stammdaten anlegen/aktualisieren
 *   - trainer_save:     Trainer anlegen/aktualisieren
 *   - trainer_delete:   Trainer loeschen
 *
 * Beim ersten Aufruf werden die DB-Tabellen automatisch angelegt (Lazy Migration).
 */
declare(strict_types=1);

// ── Datenbanktabellen anlegen (Lazy Migration) ──────────────────────────────
function ensureTeamNotizenTables(): void
{
    static $done = false;
    if ($done) return;
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS " . tbl('team_notizen') . " (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            team_id       INT NOT NULL,
            gruendung     VARCHAR(20)  NOT NULL DEFAULT '',
            stadion       VARCHAR(200) NOT NULL DEFAULT '',
            vereinsfarben VARCHAR(100) NOT NULL DEFAULT '',
            homepage      VARCHAR(500) NOT NULL DEFAULT '',
            info_text     TEXT NULL,
            erfolge       TEXT NULL,
            erstellt_von  VARCHAR(100) NULL,
            erstellt_am   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            geaendert_am  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_team (team_id),
            INDEX idx_team (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS " . tbl('team_trainer') . " (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            team_id      INT NOT NULL,
            trainer_name VARCHAR(200) NOT NULL DEFAULT '',
            von          DATE NULL,
            bis          DATE NULL,
            notiz        TEXT NULL,
            sort_order   INT NOT NULL DEFAULT 0,
            erstellt_am  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_team (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $done = true;
    } catch (Throwable $e) {
        // Verbesserung im Rahmen der i18n-Überarbeitung: Fehler wird jetzt
        // geloggt statt komplett stillschweigend verschluckt zu werden -
        // schlägt die Tabellenerstellung fehl (z.B. fehlende DB-Rechte),
        // sah der Nutzer bisher nur einen unspezifischen Datenbankfehler
        // beim Speichern, ohne dass die eigentliche Ursache irgendwo
        // sichtbar gewesen wäre.
        error_log('[team-notizen] ensureTeamNotizenTables() fehlgeschlagen: ' . $e->getMessage());
    }
}

// ── Hook: Notizen loeschen, wenn ein Team geloescht wird ─────────────────────
function teamNotizenOnTeamDeleted(array $data): array
{
    $teamId = (int)($data['team_id'] ?? 0);
    if ($teamId <= 0) return $data;
    try {
        $db = getDB();
        $db->prepare('DELETE FROM ' . tbl('team_notizen') . ' WHERE team_id = ?')->execute([$teamId]);
        $db->prepare('DELETE FROM ' . tbl('team_trainer') . ' WHERE team_id = ?')->execute([$teamId]);
    } catch (Throwable) {}
    return $data;
}

// ── POST-Handler: Team-Stammdaten speichern ──────────────────────────────────
if ($action === 'team_info_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // SICHERHEITSFIX (Beitrag: Integrationsprüfung des Addons vor
    // Übernahme): requireLogin() fehlte hier - anders als requireCsrf()
    // (das zentral in admin.php für JEDEN POST-Request geprüft wird) gibt
    // es KEINE zentrale, unbedingte Login-Prüfung im Core - jeder Admin-
    // Handler MUSS sie selbst als erste Zeile aufrufen (siehe admin/
    // handler_liga.php als durchgängiges Vorbild). Ohne diese Zeile hätte
    // jeder, auch ohne Login, mit einem gültigen (login-unabhängig
    // erzeugbaren) CSRF-Token die Team-Notizen-Datenbank manipulieren
    // können.
    requireLogin();
    requireCsrf();
    $teamId       = (int)($_POST['team_id'] ?? 0);
    $gruendung    = trim($_POST['gruendung'] ?? '');
    $stadion      = trim($_POST['stadion'] ?? '');
    $vereinsfarben = trim($_POST['vereinsfarben'] ?? '');
    $homepage     = trim($_POST['homepage'] ?? '');
    $infoText     = trim($_POST['info_text'] ?? '');
    $erfolge      = trim($_POST['erfolge'] ?? '');

    if ($teamId <= 0) {
        flash(t('tn_error_team_id_missing'), 'error');
        redirect('?action=team_notizen');
    }

    ensureTeamNotizenTables();
    try {
        $db = getDB();
        // Upsert: gibt es schon einen Datensatz?
        $check = $db->prepare('SELECT id FROM ' . tbl('team_notizen') . ' WHERE team_id = ?');
        $check->execute([$teamId]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $db->prepare(
                'UPDATE ' . tbl('team_notizen') . '
                    SET gruendung = ?, stadion = ?, vereinsfarben = ?, homepage = ?, info_text = ?, erfolge = ?
                  WHERE team_id = ?'
            );
            $stmt->execute([$gruendung, $stadion, $vereinsfarben, $homepage, $infoText, $erfolge, $teamId]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO ' . tbl('team_notizen') . '
                    (team_id, gruendung, stadion, vereinsfarben, homepage, info_text, erfolge, erstellt_von)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$teamId, $gruendung, $stadion, $vereinsfarben, $homepage, $infoText, $erfolge, $_SESSION['admin_user'] ?? '']);
        }
        flash(t('tn_flash_team_saved'), 'success');
    } catch (Throwable $e) {
        flash(t('tn_error_db', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=team_notizen&team_id=' . $teamId);
}

// ── POST-Handler: Trainer speichern (neu oder bearbeiten) ───────────────────
if ($action === 'trainer_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // SICHERHEITSFIX: siehe ausführlicher Kommentar bei team_info_save oben.
    requireLogin();
    requireCsrf();
    $id         = (int)($_POST['id'] ?? 0);
    $teamId     = (int)($_POST['team_id'] ?? 0);
    $trainerName = trim($_POST['trainer_name'] ?? '');
    $von        = trim($_POST['von'] ?? '');
    $bis        = trim($_POST['bis'] ?? '');
    $notiz      = trim($_POST['notiz'] ?? '');

    if ($teamId <= 0) {
        flash(t('tn_error_team_id_missing'), 'error');
        redirect('?action=team_notizen');
    }
    if ($trainerName === '') {
        flash(t('tn_error_trainer_name_required'), 'error');
        redirect('?action=team_notizen&team_id=' . $teamId);
    }

    // Datum-Validierung (leer = NULL)
    $vonSql = ($von !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $von)) ? $von : null;
    $bisSql = ($bis !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis)) ? $bis : null;

    ensureTeamNotizenTables();
    try {
        $db = getDB();
        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE ' . tbl('team_trainer') . '
                    SET trainer_name = ?, von = ?, bis = ?, notiz = ?
                  WHERE id = ?'
            );
            $stmt->execute([$trainerName, $vonSql, $bisSql, $notiz, $id]);
        } else {
            // sort_order = hoechster + 1
            $maxOrder = (int)$db->query('SELECT COALESCE(MAX(sort_order), 0) FROM ' . tbl('team_trainer') . ' WHERE team_id = ' . (int)$teamId)->fetchColumn();

            $stmt = $db->prepare(
                'INSERT INTO ' . tbl('team_trainer') . '
                    (team_id, trainer_name, von, bis, notiz, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$teamId, $trainerName, $vonSql, $bisSql, $notiz, $maxOrder + 1]);
        }
        flash(t('tn_flash_trainer_saved'), 'success');
    } catch (Throwable $e) {
        flash(t('tn_error_db', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=team_notizen&team_id=' . $teamId);
}

// ── POST-Handler: Trainer loeschen ───────────────────────────────────────────
if ($action === 'trainer_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // SICHERHEITSFIX: siehe ausführlicher Kommentar bei team_info_save oben.
    requireLogin();
    requireCsrf();
    $id     = (int)($_POST['id'] ?? 0);
    $teamId = (int)($_POST['team_id'] ?? 0);

    if ($id <= 0) {
        flash(t('tn_error_invalid_trainer_id'), 'error');
        redirect('?action=team_notizen');
    }

    ensureTeamNotizenTables();
    try {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM ' . tbl('team_trainer') . ' WHERE id = ?');
        $stmt->execute([$id]);
        flash(t('tn_flash_trainer_deleted'), 'success');
    } catch (Throwable $e) {
        flash(t('tn_error_delete', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=team_notizen&team_id=' . $teamId);
}
