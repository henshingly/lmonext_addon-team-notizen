<?php
/**
 * Project: LMOnext
 * Filename: addon/team-notizen/lang/de.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Sprachdatei für Team-Notizen (Deutsch). Vollständig überarbeitet -
 * ursprünglich enthielt diese Datei nur den Navigationseintrag, die
 * eigentliche Funktionalität (Formulare, Meldungen, Frontend-Anzeige) war
 * fest auf Deutsch programmiert. tn_-Präfix für Admin-Schlüssel (t()),
 * ti_-Präfix für Frontend-Schlüssel (tf()) - konsistent mit den bereits im
 * Code verwendeten Variablen-Präfixen ($tiTeam etc.).
 */

return [
    // ── Navigation ──────────────────────────────────────────────────────────
    'nav_team_notizen' => 'Team-Notizen',

    // ── Admin: allgemein (view_notizen.php) ──────────────────────────────────
    'tn_page_title'          => 'Team-Notizen',
    'tn_label_select_team'   => 'Team auswählen',
    'tn_option_choose_team'  => '— Team wählen —',
    'tn_team_fallback_name'  => 'Team #{id}',
    'tn_heading_stammdaten'  => 'Stammdaten: {team}',
    'tn_label_gruendung'     => 'Gründung',
    'tn_label_stadion'       => 'Stadion',
    'tn_label_vereinsfarben' => 'Vereinsfarben',
    'tn_label_homepage'      => 'Homepage',
    'tn_label_erfolge'       => 'Erfolge (ein Eintrag pro Zeile)',
    'tn_label_info'          => 'Info / Beschreibung',
    'tn_btn_save_stammdaten' => '✓ Stammdaten speichern',
    'tn_heading_trainer_edit' => 'Trainer bearbeiten',
    'tn_heading_trainer_add' => 'Trainer hinzufügen',
    'tn_label_trainer_name'  => 'Trainer-Name',
    'tn_label_von'           => 'Von',
    'tn_label_bis'           => 'Bis',
    'tn_placeholder_bis_leer' => 'leer = aktuell',
    'tn_label_notiz'         => 'Notiz (optional)',
    'tn_btn_speichern'       => 'Speichern',
    'tn_btn_hinzufuegen'     => 'Hinzufügen',
    'tn_btn_abbrechen'       => 'Abbrechen',
    'tn_empty_trainer'       => '👤 Noch keine Trainer eingetragen.',
    'tn_col_trainer'         => 'Trainer',
    'tn_col_notiz'           => 'Notiz',
    'tn_aktuell'             => 'aktuell',
    'tn_title_edit'          => 'Bearbeiten',
    'tn_confirm_delete_trainer' => 'Trainer wirklich löschen?',
    'tn_title_delete'        => 'Löschen',
    'tn_empty_hint'          => 'Wähle oben ein Team aus, um Stammdaten und Trainer-Historie zu pflegen.',
    'tn_empty_features'      => 'Gründung · Stadion · Vereinsfarben · Erfolge · Info-Text · Trainer-Verlauf',

    // ── Admin: Fehler-/Erfolgsmeldungen (handler_notizen.php) ────────────────
    'tn_error_team_id_missing'        => 'Team-ID fehlt.',
    'tn_error_trainer_name_required'  => 'Trainer-Name darf nicht leer sein.',
    'tn_flash_team_saved'             => 'Team-Stammdaten gespeichert.',
    'tn_error_db'                     => 'Datenbankfehler: {msg}',
    'tn_flash_trainer_saved'          => 'Trainer gespeichert.',
    'tn_error_invalid_trainer_id'     => 'Ungültige Trainer-ID.',
    'tn_flash_trainer_deleted'        => 'Trainer gelöscht.',
    'tn_error_delete'                 => 'Fehler beim Löschen: {msg}',

    // ── Frontend (lmo-teaminfo.php + templates/standard.tpl.php) ─────────────
    'ti_page_title'      => 'Team-Info',
    'ti_team_not_found'  => 'Team nicht gefunden (ID {id})',
    'ti_no_erfolge'      => 'Keine Erfolge eingetragen.',
    'ti_no_trainer'      => 'Keine Trainer eingetragen.',
    'ti_heute'           => 'heute',
    'ti_label_gruendung' => 'Gründung',
    'ti_label_stadion'   => 'Stadion',
    'ti_label_farben'    => 'Vereinsfarben',
    'ti_label_homepage'  => 'Homepage',
    'ti_heading_ueber'   => 'Über den Verein',
    'ti_heading_erfolge' => 'Erfolge',
    'ti_heading_trainer' => 'Trainer-Historie',
    'ti_no_info_text'    => 'Keine Beschreibung vorhanden.',
];
