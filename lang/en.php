<?php
/**
 * Project: LMOnext
 * Filename: addon/team-notizen/lang/en.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Sprachdatei für Team-Notizen (Englisch). Vollständig überarbeitet -
 * siehe lang/de.php für den ausführlichen Hintergrund. Schlüssel-Set
 * identisch, nur die Werte sind englisch.
 */

return [
    // ── Navigation ──────────────────────────────────────────────────────────
    'nav_team_notizen' => 'Team notes',

    // ── Admin: allgemein (view_notizen.php) ──────────────────────────────────
    'tn_page_title'          => 'Team notes',
    'tn_label_select_team'   => 'Select team',
    'tn_option_choose_team'  => '— choose team —',
    'tn_team_fallback_name'  => 'Team #{id}',
    'tn_heading_stammdaten'  => 'Basic info: {team}',
    'tn_label_gruendung'     => 'Founded',
    'tn_label_stadion'       => 'Stadium',
    'tn_label_vereinsfarben' => 'Club colours',
    'tn_label_homepage'      => 'Website',
    'tn_label_erfolge'       => 'Honours (one entry per line)',
    'tn_label_info'          => 'Info / description',
    'tn_btn_save_stammdaten' => '✓ Save basic info',
    'tn_heading_trainer_edit' => 'Edit coach',
    'tn_heading_trainer_add' => 'Add coach',
    'tn_label_trainer_name'  => 'Coach name',
    'tn_label_von'           => 'From',
    'tn_label_bis'           => 'To',
    'tn_placeholder_bis_leer' => 'empty = current',
    'tn_label_notiz'         => 'Note (optional)',
    'tn_btn_speichern'       => 'Save',
    'tn_btn_hinzufuegen'     => 'Add',
    'tn_btn_abbrechen'       => 'Cancel',
    'tn_empty_trainer'       => '👤 No coaches recorded yet.',
    'tn_col_trainer'         => 'Coach',
    'tn_col_notiz'           => 'Note',
    'tn_aktuell'             => 'current',
    'tn_title_edit'          => 'Edit',
    'tn_confirm_delete_trainer' => 'Really delete this coach?',
    'tn_title_delete'        => 'Delete',
    'tn_empty_hint'          => 'Choose a team above to manage its basic info and coaching history.',
    'tn_empty_features'      => 'Founded · Stadium · Club colours · Honours · Info text · Coaching history',

    // ── Admin: Fehler-/Erfolgsmeldungen (handler_notizen.php) ────────────────
    'tn_error_team_id_missing'        => 'Team ID missing.',
    'tn_error_trainer_name_required'  => 'Coach name must not be empty.',
    'tn_flash_team_saved'             => 'Team basic info saved.',
    'tn_error_db'                     => 'Database error: {msg}',
    'tn_flash_trainer_saved'          => 'Coach saved.',
    'tn_error_invalid_trainer_id'     => 'Invalid coach ID.',
    'tn_flash_trainer_deleted'        => 'Coach deleted.',
    'tn_error_delete'                 => 'Error while deleting: {msg}',

    // ── Frontend (lmo-teaminfo.php + templates/standard.tpl.php) ─────────────
    'ti_page_title'      => 'Team info',
    'ti_team_not_found'  => 'Team not found (ID {id})',
    'ti_no_erfolge'      => 'No honours recorded.',
    'ti_no_trainer'      => 'No coaches recorded.',
    'ti_heute'           => 'present',
    'ti_label_gruendung' => 'Founded',
    'ti_label_stadion'   => 'Stadium',
    'ti_label_farben'    => 'Club colours',
    'ti_label_homepage'  => 'Website',
    'ti_heading_ueber'   => 'About the club',
    'ti_heading_erfolge' => 'Honours',
    'ti_heading_trainer' => 'Coaching history',
    'ti_no_info_text'    => 'No description available.',
];
