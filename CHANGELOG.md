# Changelog: team-notizen-Addon (LMOnext)

Team-Notizen: Gründung, Stadion, Vereinsfarben, Erfolge, Infotext und
Trainer-Historie pro Team pflegen und im Frontend anzeigen.

Ursprünglich von Torsten Hofmann bereitgestellt. Vor der Übernahme
sicherheitsgeprüft - mehrere kritische Lücken gefunden und behoben (siehe
unten), außerdem war das Addon durch ein Manifest-Problem in der
ursprünglichen Form gar nicht erreichbar.

## Version 1.1.0 (Vollständige Mehrsprachigkeit)

- Feature (auf Wunsch): die gesamte Funktionalität ist jetzt über Sprachdateien steuerbar statt fest auf Deutsch programmiert zu sein. Betrifft alle vier Dateien: handler_notizen.php 1.1.0 (alle Flash-/Fehlermeldungen), view_notizen.php 1.1.0 (komplette Admin-Oberfläche: Labels, Buttons, Tabellen-Header, Bestätigungsdialoge), lmo-teaminfo.php 1.1.0 (Frontend-Ausgabe inkl. Fallback-Pfad ohne Template) und templates/standard.tpl.php 1.1.0 (Stammdaten-Labels und Abschnittsüberschriften, bisher fest im Template-HTML eingebettet, jetzt als Platzhalter wie <!--LabelGruendung--> analog zu den bereits bestehenden Daten-Platzhaltern).
- Neues Schlüssel-Präfix-Schema: tn_ für Admin-Schlüssel (t()), ti_ für Frontend-Schlüssel (tf()) - konsistent mit den bereits im Code verwendeten Variablen-Präfixen. lang/de.php und lang/en.php jeweils auf über 40 Schlüssel erweitert (vorher: nur der Navigationseintrag).
- Nebenbei behoben: ensureTeamNotizenTables() verschluckte Fehler bei der Tabellenerstellung bisher komplett stillschweigend - jetzt über error_log() protokolliert, damit ein fehlgeschlagenes Anlegen (z.B. wegen fehlender DB-Rechte) nicht nur als unspezifischer Datenbankfehler beim Speichern sichtbar wird.
- Gegen den Addon-Manager-Sicherheitsscanner erneut getestet: keine Treffer.

## Version 1.0.1 (Sicherheits- und Integrationsfixes)

- KRITISCHER Sicherheitsfix (view_notizen.php 1.0.2, handler_notizen.php 1.0.1): requireLogin() fehlte an allen vier zugriffsrelevanten Stellen (der Admin-Ansicht selbst sowie allen drei POST-Handlern team_info_save/trainer_save/trainer_delete). Anders als requireCsrf() (zentral in admin.php für jeden POST-Request geprüft) gibt es KEINE zentrale, unbedingte Login-Prüfung im Core - jeder Admin-Handler muss sie selbst als erste Zeile aufrufen. Ohne diesen Fix wäre die gesamte Team-Notizen-Verwaltung (Ansicht UND Schreibzugriff) für jeden ohne Login direkt erreichbar gewesen - ein gültiges CSRF-Token lässt sich unabhängig vom Login-Status erzeugen.
- Sicherheitsfix (lmo-teaminfo.php 1.0.3): die im Frontend angezeigte Homepage-URL wurde nur per h() HTML-escaped, aber nicht auf ein sicheres Protokoll geprüft, bevor sie als href-Attribut ausgegeben wird. Ein gespeicherter Wert wie "javascript:alert(1)" hätte beim Anklicken durch einen Besucher beliebigen JavaScript-Code ausgeführt (h() verhindert nur das Ausbrechen aus dem Attribut, nicht ein gefährliches Protokoll innerhalb des Attributwerts selbst). Nur noch http(s)-URLs werden als Link ausgegeben.
- KRITISCHER Erreichbarkeits-Fix (addon.json): "standalone_entrypoints" fehlte komplett, während gleichzeitig "frontend_handlers": ["lmo-teaminfo.php"] bei type "admin" deklariert war - dieser Handler wäre bei type "admin" ohnehin nie über den normalen Boot-Mechanismus geladen worden (bootFrontend() lädt frontend_handlers nur bei type frontend/both). lmo-teaminfo.php war dadurch in der ursprünglichen Form WEDER über addon-run.php (fehlende Whitelist) NOCH über den normalen Weg erreichbar. Jetzt korrekt als eigenständiger Standalone-Entrypoint deklariert (frontend_handlers geleert), Docblock-Kommentar in lmo-teaminfo.php mit der korrekten neuen iframe-URL (über addon-run.php) aktualisiert.
- Bugfix (addon.json): db_tables enthielt die Tabellennamen mit fest eingebautem "lmo_"-Präfix ("lmo_team_notizen", "lmo_team_trainer"), obwohl der tatsächliche PHP-Code über tbl('team_notizen')/tbl('team_trainer') (ohne Präfix, das Präfix wird von tbl() bzw. bei einem Addon-Datenlöschvorgang zur Laufzeit dynamisch ergänzt) arbeitet - bei einer Installation mit einem anderen DB-Präfix als "lmo_" hätte ein Datenlöschvorgang über den Addon-Manager versucht, eine falsch benannte (doppelt/falsch präfigierte) Tabelle zu löschen. Auf die Basis-Namen ohne Präfix korrigiert, konsistent mit allen anderen Addons dieses Systems.
- Vervollständigt: lang/en.php ergänzt (fehlte im ursprünglichen Paket, das nur lang/de.php enthielt - anders als bei allen anderen Addons dieses Systems). Betrifft nur den Navigationseintrag - der Rest der Funktionalität ist im Addon nicht über t()/tf() übersetzbar, sondern fest auf Deutsch programmiert (nicht behoben, siehe Hinweis unten).
- min_core_version von 1.4.0 auf 1.9.2 korrigiert (das Addon nutzt requireLogin()/requireCsrf()/doHook() und die AVAILABLE_LANGUAGES-basierte Addon-Sprachladung, die erst in neueren Core-Versionen vollständig funktionieren).
- Neuer Hook-Punkt "team.deleted" im CORE ergänzt (siehe CHANGELOG.md des Hauptsystems, admin/handler_liga.php 1.10.0) - dieses Addon registriert einen Handler dafür (teamNotizenOnTeamDeleted), der Hook wurde vom Core bisher aber nirgends gefeuert. Ohne diese Core-Ergänzung wären Notizen/Trainer-Einträge beim Löschen eines Teams als verwaiste Datensätze in der Datenbank zurückgeblieben.
- Gegen den Addon-Manager-Sicherheitsscanner getestet: keine Treffer im gesamten Addon-Paket.
- WICHTIGER ZUSATZFUND (im CORE behoben, keine Änderung an diesem Addon selbst nötig): der von diesem Addon deklarierte Hook-Handler (teamNotizenOnTeamDeleted) wäre trotz korrekter Manifest-Deklaration NIE ausgelöst worden - der Core registrierte Hooks, bevor die admin_handlers-Datei, die die Handler-Funktion definiert, überhaupt geladen war (is_callable()-Prüfung schlug dadurch immer fehl). Betraf potenziell jedes Addon mit einem im Manifest deklarierten Hook, dessen Handler-Funktion in einer eigenen Handler-Datei liegt. Siehe CHANGELOG.md des Hauptsystems, src/Addon/AddonManager.php 1.9.0, für den vollständigen Fix - mit der dortigen Korrektur funktioniert der hier deklarierte Hook jetzt wie vom Addon vorgesehen.

## Version 1.0.0

- Ursprüngliche Version von Torsten Hofmann.
