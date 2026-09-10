<!--
  Template: addon/team-notizen | Filename: standard.tpl.php | Fileversion: 1.1.0
  Frontend-Anzeige der Team-Stammdaten und Trainer-Historie.
  Platzhalter werden in lmo-teaminfo.php ersetzt.
-->
<style>
.ti-wrap{max-width:820px;margin:0 auto;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:#1f2430}
.ti-header{display:flex;align-items:center;gap:14px;padding:16px 0;border-bottom:2px solid #153A8C;margin-bottom:16px}
.ti-header h2{margin:0;font-size:1.3rem;font-weight:700;color:#153A8C}
.ti-header .ti-kurz{color:#697182;font-weight:400;font-size:.85rem;margin-left:6px}
.ti-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;margin-bottom:16px}
.ti-grid .ti-label{font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;color:#9098a8;font-weight:600}
.ti-grid .ti-value{font-size:.95rem;color:#1f2430;margin-bottom:6px}
.ti-section{margin:20px 0 12px}
.ti-section h3{font-size:.78rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#697182;margin:0 0 8px;border-bottom:1px solid #e3e7ee;padding-bottom:4px}
.ti-info-text{font-size:.9rem;line-height:1.5;color:#4b5261;white-space:pre-wrap}
.ti-erfolge{margin:4px 0;padding-left:18px;font-size:.9rem}
.ti-erfolge li{margin-bottom:3px;color:#4b5261}
.ti-trainer-table{width:100%;border-collapse:collapse;font-size:.88rem;margin-top:4px}
.ti-trainer-table td{padding:6px 10px;border-bottom:1px solid #eef1f6}
.ti-trainer-name{font-weight:600;white-space:nowrap}
.ti-trainer-zeit{color:#697182;white-space:nowrap;text-align:center;font-variant-numeric:tabular-nums}
.ti-trainer-notiz{color:#9098a8;font-size:.82rem}
.ti-aktuell{color:#22c55e;font-weight:600}
.ti-aktuell-badge{color:#22c55e;font-size:.6rem;vertical-align:middle}
.ti-empty{color:#9098a8;font-size:.85rem;font-style:italic}
.ti-copyright{text-align:right;padding:10px 0 6px;font-size:.68rem;color:#9098a8}
.ti-copyright a{color:#9098a8}
</style>

<div class="ti-wrap">

  <!-- ── Kopfzeile ── -->
  <div class="ti-header">
    <!--TeamLogo-->
    <h2><!--TeamName--><span class="ti-kurz">(<!--TeamKurz-->)</span></h2>
  </div>

  <!-- ── Stammdaten ── -->
  <div class="ti-grid">
    <div>
      <div class="ti-label"><!--LabelGruendung--></div>
      <div class="ti-value"><!--Gruendung--></div>
    </div>
    <div>
      <div class="ti-label"><!--LabelStadion--></div>
      <div class="ti-value"><!--Stadion--></div>
    </div>
    <div>
      <div class="ti-label"><!--LabelFarben--></div>
      <div class="ti-value"><!--Farben--></div>
    </div>
    <div>
      <div class="ti-label"><!--LabelHomepage--></div>
      <div class="ti-value"><!--Homepage--></div>
    </div>
  </div>

  <!-- ── Info-Text ── -->
  <div class="ti-section">
    <h3><!--HeadingUeber--></h3>
    <div class="ti-info-text"><!--InfoText--></div>
  </div>

  <!-- ── Erfolge ── -->
  <div class="ti-section">
    <h3><!--HeadingErfolge--></h3>
    <div class="ti-erfolge"><!--Erfolge--></div>
  </div>

  <!-- ── Trainer-Historie ── -->
  <div class="ti-section">
    <h3><!--HeadingTrainer--></h3>
    <!--TrainerListe-->
  </div>

  <!-- ── Copyright ── -->
  <div class="ti-copyright"><!--Copyright--></div>

</div>
