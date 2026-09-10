<?php
/**
 * Project: LMOnext
 * Filename: addon/team-notizen/lmo-teaminfo.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Frontend-Handler: Team-Info ──────────────────────────────────────────────
 *
 * Zeigt die Stammdaten und Trainer-Historie eines Teams an.
 *
 * Einbindung per IFrame (über den zentralen Standalone-Controller, siehe
 * addon-run.php im Projekt-Root - direkte Aufrufe der .php-Datei sind aus
 * Sicherheitsgründen gesperrt):
 *   <iframe src=".../addon-run.php?addon=team-notizen&file=lmo-teaminfo.php&team_id=5"
 *           frameborder="0" width="860" height="500" scrolling="auto"></iframe>
 *
 * Einbindung per include():
 *   <?php $team_id = 5; include('/pfad/addon/team-notizen/lmo-teaminfo.php'); ?>
 *
 * Parameter:
 *   team_id           Team-ID aus teams_global (Pflicht)
 *   teaminfo_template Template-Name (Standard: 'standard')
 */
declare(strict_types=1);

$tiIsDirectCall = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'lmo-teaminfo.php';

require_once __DIR__ . '/../../frontend/bootstrap.php';

if (!headers_sent()) {
    header_remove('X-Frame-Options');
    header_remove('Content-Security-Policy');
}

require_once __DIR__ . '/../../frontend/data_liga.php';

// ── Tabellen sicherstellen (unabhaengig vom Admin-Handler) ───────────────────
function tiEnsureTables(): void
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
    } catch (Throwable) {}
}

tiEnsureTables();

// ── Parameter ───────────────────────────────────────────────────────────────
$tiTeamId = (int)($_REQUEST['team_id'] ?? ($team_id ?? 0));
$tiTemplateName = str_replace('..', '', basename((string)($_REQUEST['teaminfo_template'] ?? ($teaminfo_template ?? ''))));
if ($tiTemplateName === '') {
    $tiTemplateName = 'standard';
}

// ── URL-Praefix (wie meister/ewige) ───────────────────────────────────────────
function tiProjectRootUrlPrefix(): string
{
    static $prefix = null;
    if ($prefix !== null) return $prefix;

    $projectRootDisk = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/');
    $scriptFilename  = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName      = (string)($_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptFilename !== '' && $scriptName !== '' && str_ends_with($scriptFilename, $scriptName)) {
        $documentRootDisk = rtrim(substr($scriptFilename, 0, -strlen($scriptName)), '/');
        if ($documentRootDisk !== '' && str_starts_with($projectRootDisk, $documentRootDisk)) {
            $prefix = substr($projectRootDisk, strlen($documentRootDisk)) . '/';
            return $prefix;
        }
    }
    $prefix = basename($_SERVER['SCRIPT_NAME'] ?? '') === basename(__FILE__) ? '../../' : '';
    return $prefix;
}

function tiLogoImg(int $teamId): string
{
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    return '<img src="' . h(tiProjectRootUrlPrefix() . $path) . '" alt="" style="height:32px;width:auto;vertical-align:middle">';
}

// ── Daten laden ──────────────────────────────────────────────────────────────
$db = getDB();

$tiTeam = null;
$s = $db->prepare('SELECT id, name, kurz, mittel FROM ' . tbl('teams_global') . ' WHERE id = ?');
$s->execute([$tiTeamId]);
$tiTeam = $s->fetch();

$tiInfo = null;
if ($tiTeam) {
    $sInfo = $db->prepare('SELECT * FROM ' . tbl('team_notizen') . ' WHERE team_id = ?');
    $sInfo->execute([$tiTeamId]);
    $tiInfo = $sInfo->fetch();

    $sTrainer = $db->prepare(
        'SELECT * FROM ' . tbl('team_trainer') . ' WHERE team_id = ? ORDER BY bis IS NULL DESC, COALESCE(von, "1900-01-01") DESC, sort_order DESC'
    );
    $sTrainer->execute([$tiTeamId]);
    $tiTrainer = $sTrainer->fetchAll();
} else {
    $tiTrainer = [];
}

// ── Template laden ────────────────────────────────────────────────────────────
$tiTplPath = '';
foreach ([__DIR__ . '/templates/' . $tiTemplateName . '.tpl.php', __DIR__ . '/templates/standard.tpl.php'] as $c) {
    if (is_file($c)) { $tiTplPath = $c; break; }
}

$tiCopyright = function_exists('renderCopyrightNotice') ? renderCopyrightNotice() : '';

// SICHERHEITSFIX (Beitrag: Integrationsprüfung des Addons vor Übernahme):
// die Homepage-URL wurde bisher nur per h() HTML-escaped, aber NICHT auf
// ein sicheres Protokoll geprüft, bevor sie als href-Wert ausgegeben wird
// (weiter unten bei "<!--Homepage-->"). h() verhindert zwar, dass aus dem
// href-Attribut ausgebrochen werden kann, verhindert aber NICHT, dass der
// Attributwert selbst ein "javascript:"-Pseudo-Protokoll enthält - ein
// entsprechend gespeicherter Wert (z.B. "javascript:alert(1)") hätte beim
// Klick im Frontend (für JEDEN Besucher, nicht nur eingeloggte Admins)
// beliebigen JavaScript-Code im Kontext der Seite ausgeführt. Nur
// http(s)-URLs werden jetzt als Link ausgegeben, alles andere (inkl.
// leerem Wert) wie "keine Homepage hinterlegt" behandelt.
$tiHomepageUrl = '';
if ($tiInfo && $tiInfo['homepage'] !== '' && preg_match('#^https?://#i', (string)$tiInfo['homepage'])) {
    $tiHomepageUrl = (string)$tiInfo['homepage'];
}

if ($tiIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><title>" . h(tf('ti_page_title')) . "</title>"
        . "<style>html,body{margin:0;padding:0;background:transparent;}</style></head><body>\n";
}

if ($tiTeam === null) {
    echo '<p style="font-family:sans-serif;color:#a33;padding:12px">' . h(tf('ti_team_not_found', ['id' => $tiTeamId])) . '</p>';
} elseif ($tiTplPath !== '' && ($tiTpl = (string)file_get_contents($tiTplPath)) !== '') {
    // Template-Platzhalter ersetzen
    $tiErfolge = $tiInfo && $tiInfo['erfolge'] !== '' ? explode("\n", $tiInfo['erfolge']) : [];

    $tiErfolgeHtml = '';
    if (!empty($tiErfolge)) {
        $tiErfolgeHtml = '<ul class="ti-erfolge">';
        foreach ($tiErfolge as $erfolg) {
            $erfolg = trim($erfolg);
            if ($erfolg !== '') {
                $tiErfolgeHtml .= '<li>' . h($erfolg) . '</li>';
            }
        }
        $tiErfolgeHtml .= '</ul>';
    } else {
        $tiErfolgeHtml = '<p class="ti-empty">' . h(tf('ti_no_erfolge')) . '</p>';
    }

    $tiTrainerHtml = '';
    if (!empty($tiTrainer)) {
        $tiTrainerHtml = '<table class="ti-trainer-table"><tbody>';
        foreach ($tiTrainer as $t) {
            $isCurrent = $t['bis'] === null;
            $vonTxt = $t['von'] ? date('d.m.Y', strtotime($t['von'])) : '';
            $bisTxt = $t['bis'] ? date('d.m.Y', strtotime($t['bis'])) : '';
            $zeitraum = $vonTxt !== '' || $bisTxt !== ''
                ? h($vonTxt) . '&ndash;' . ($t['bis'] ? h($bisTxt) : '<span class="ti-aktuell">' . h(tf('ti_heute')) . '</span>')
                : '';
            $tiTrainerHtml .= '<tr>'
                . '<td class="ti-trainer-name">' . ($isCurrent ? '<span class="ti-aktuell-badge">&#9679;</span> ' : '') . h($t['trainer_name']) . '</td>'
                . '<td class="ti-trainer-zeit">' . $zeitraum . '</td>'
                . '<td class="ti-trainer-notiz">' . h($t['notiz'] ?? '') . '</td>'
                . '</tr>';
        }
        $tiTrainerHtml .= '</tbody></table>';
    } else {
        $tiTrainerHtml = '<p class="ti-empty">' . h(tf('ti_no_trainer')) . '</p>';
    }

    $replacements = [
        '<!--TeamName-->'      => h($tiTeam['name']),
        '<!--TeamKurz-->'      => h($tiTeam['kurz']),
        '<!--TeamLogo-->'      => tiLogoImg($tiTeamId),
        '<!--LabelGruendung-->' => h(tf('ti_label_gruendung')),
        '<!--LabelStadion-->'   => h(tf('ti_label_stadion')),
        '<!--LabelFarben-->'    => h(tf('ti_label_farben')),
        '<!--LabelHomepage-->'  => h(tf('ti_label_homepage')),
        '<!--HeadingUeber-->'   => h(tf('ti_heading_ueber')),
        '<!--HeadingErfolge-->' => h(tf('ti_heading_erfolge')),
        '<!--HeadingTrainer-->' => h(tf('ti_heading_trainer')),
        '<!--Gruendung-->'    => $tiInfo && $tiInfo['gruendung'] !== '' ? h($tiInfo['gruendung']) : '<span class="ti-empty">—</span>',
        '<!--Stadion-->'       => $tiInfo && $tiInfo['stadion'] !== '' ? h($tiInfo['stadion']) : '<span class="ti-empty">—</span>',
        '<!--Farben-->'       => $tiInfo && $tiInfo['vereinsfarben'] !== '' ? h($tiInfo['vereinsfarben']) : '<span class="ti-empty">—</span>',
        '<!--Homepage-->'     => $tiHomepageUrl !== ''
            ? '<a href="' . h($tiHomepageUrl) . '" target="_blank" rel="noopener">' . h($tiHomepageUrl) . '</a>'
            : '<span class="ti-empty">—</span>',
        '<!--InfoText-->'     => $tiInfo && $tiInfo['info_text'] !== '' ? nl2br(h($tiInfo['info_text'])) : '<p class="ti-empty">' . h(tf('ti_no_info_text')) . '</p>',
        '<!--Erfolge-->'      => $tiErfolgeHtml,
        '<!--TrainerListe-->' => $tiTrainerHtml,
        '<!--Copyright-->'    => $tiCopyright,
    ];

    echo str_replace(array_keys($replacements), array_values($replacements), $tiTpl);
} else {
    // Fallback: direkte HTML-Ausgabe ohne Template
    echo '<div style="font-family:sans-serif;max-width:820px;margin:0 auto;padding:12px">';
    echo '<h2 style="color:#153A8C">' . tiLogoImg($tiTeamId) . ' ' . h($tiTeam['name']) . '</h2>';
    if ($tiInfo) {
        if ($tiInfo['gruendung'] !== '') echo '<p><b>' . h(tf('ti_label_gruendung')) . ':</b> ' . h($tiInfo['gruendung']) . '</p>';
        if ($tiInfo['stadion'] !== '') echo '<p><b>' . h(tf('ti_label_stadion')) . ':</b> ' . h($tiInfo['stadion']) . '</p>';
        if ($tiInfo['vereinsfarben'] !== '') echo '<p><b>' . h(tf('ti_label_farben')) . ':</b> ' . h($tiInfo['vereinsfarben']) . '</p>';
        if ($tiInfo['info_text']) echo '<p>' . nl2br(h($tiInfo['info_text'])) . '</p>';
        if ($tiInfo['erfolge']) echo '<p><b>' . h(tf('ti_heading_erfolge')) . ':</b><br>' . nl2br(h($tiInfo['erfolge'])) . '</p>';
    }
    if ($tiTrainer) {
        echo '<h3>' . h(tf('ti_heading_trainer')) . '</h3><ul>';
        foreach ($tiTrainer as $t) {
            $von = $t['von'] ? date('d.m.Y', strtotime($t['von'])) : '';
            $bis = $t['bis'] ? date('d.m.Y', strtotime($t['bis'])) : tf('ti_heute');
            echo '<li>' . h($t['trainer_name']) . ' (' . h($von) . '–' . h($bis) . ')</li>';
        }
        echo '</ul>';
    }
    echo '<p style="text-align:center;font-size:.72rem;color:#9098a8;margin-top:12px">' . $tiCopyright . '</p>';
    echo '</div>';
}

if ($tiIsDirectCall) {
    echo "\n</body></html>";
}
