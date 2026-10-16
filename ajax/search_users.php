<?php
// Tippsuche nach Nutzern für die Compliance-Seite (Admin).
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();
require_capability('local/railvieweradmin:viewprogress', context_system::instance());

header('Content-Type: application/json');
echo json_encode(\local_railvieweradmin\rides::search_users(optional_param('q', '', PARAM_TEXT), 0));
