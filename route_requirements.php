<?php
/**
 * Anforderungen einer Strecke (Lernmethoden-Feature): Soll-Dauer je Abschnitt und Methode,
 * Anforderungsmatrix Methode x Berufserfahrungstyp und Ersetzungsregeln.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
require_capability('local/railvieweradmin:editsections', $context);

$routeid = required_param('routeid', PARAM_INT);
$route = $GLOBALS['DB']->get_record('railviewer_routes', ['id' => $routeid], '*', MUST_EXIST);

$self = new moodle_url('/local/railvieweradmin/route_requirements.php', ['routeid' => $routeid]);
$PAGE->set_context($context);
$PAGE->set_url($self);
$PAGE->set_pagelayout('admin');
$title = get_string('route_requirements', 'local_railvieweradmin') . ': ' . format_string($route->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('adminpagetitle', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/index.php'));
$PAGE->navbar->add(get_string('manage_routes_title', 'local_railvieweradmin'), new moodle_url('/local/railvieweradmin/routes.php'));
$PAGE->navbar->add(get_string('route_requirements', 'local_railvieweradmin'));

global $DB;

if (!\local_railvieweradmin\learning::enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('learning_feature_disabled', 'local_railvieweradmin'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$methods = \local_railvieweradmin\learning::methods();
$types = \local_railvieweradmin\learning::experience_types();
$sections = \local_railvieweradmin\learning::route_sections($routeid);

// Anforderungsmatrix und Ersetzungsregeln auf die verknüpfte Gegenrichtung übernehmen. Abschnitte
// und damit die Soll-Dauer sind richtungsgebunden und werden bewusst nicht kopiert.
if (optional_param('copypartner', 0, PARAM_INT) && !empty($route->pairedrouteid)) {
    require_sesskey();
    $partnerid = (int) $route->pairedrouteid;
    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('railviewer_route_reqs', ['routeid' => $partnerid]);
    foreach ($DB->get_records('railviewer_route_reqs', ['routeid' => $routeid]) as $r) {
        unset($r->id);
        $r->routeid = $partnerid;
        $DB->insert_record('railviewer_route_reqs', $r);
    }
    $DB->delete_records('railviewer_route_substs', ['routeid' => $partnerid]);
    foreach ($DB->get_records('railviewer_route_substs', ['routeid' => $routeid]) as $r) {
        unset($r->id);
        $r->routeid = $partnerid;
        $DB->insert_record('railviewer_route_substs', $r);
    }
    $transaction->allow_commit();
    redirect($self, get_string('copy_partner_done', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if (optional_param('save', '', PARAM_ALPHA) !== '') {
    require_sesskey();
    $errors = [];
    // Ersetzungsregeln zuerst prüfen: bei Fehlern wird nichts gespeichert.
    $rules = clean_param_array((array) ($_POST['subst'] ?? []), PARAM_RAW, true);
    $clean = [];
    $pairs = [];
    foreach ($rules as $r) {
        $a = (int) ($r['methodid'] ?? 0);
        $b = (int) ($r['bymethodid'] ?? 0);
        $factor = (int) ($r['factor'] ?? 1);
        if (!$a && !$b) {
            continue; // leere Zeile
        }
        if (!isset($methods[$a]) || !isset($methods[$b]) || $a === $b || $factor < 1) {
            $errors[] = get_string('subst_invalid', 'local_railvieweradmin');
            continue;
        }
        if (isset($pairs[$b . '_' . $a])) {
            $errors[] = get_string('subst_cycle', 'local_railvieweradmin');
            continue;
        }
        $pairs[$a . '_' . $b] = true;
        $clean[] = (object) ['routeid' => $routeid, 'methodid' => $a, 'bymethodid' => $b, 'factor' => $factor,
            'userchoice' => empty($r['auto']) ? 1 : 0, 'sortorder' => count($clean)];
    }
    if (!$errors) {
    $transaction = $DB->start_delegated_transaction();

    // 1) Soll-Dauer je Abschnitt x Methode (0 = Video). Leer/0 = Rückfall auf die Videodauer.
    $dur = clean_param_array((array) ($_POST['dur'] ?? []), PARAM_RAW, true);
    foreach ($sections as $s) {
        foreach ([0] + array_keys($methods) as $mid) {
            $raw = $dur[$s->id][$mid] ?? '';
            $minutes = is_numeric($raw) ? max(0, (int) $raw) : 0;
            $DB->delete_records('railviewer_section_durations', ['sectionid' => $s->id, 'methodid' => $mid]);
            // Video-Rückfall (aus der Videodatei) nicht doppelt speichern.
            $videofallback = $s->duration > 0 ? (int) ceil($s->duration / 60) : 0;
            if ($minutes > 0 && !($mid === 0 && $minutes === $videofallback)) {
                $DB->insert_record('railviewer_section_durations', (object) [
                    'sectionid' => $s->id, 'methodid' => $mid, 'minutes' => $minutes,
                ]);
            }
        }
    }

    // 2) Anforderungsmatrix Methode (0 = Video) x Typ.
    $req = clean_param_array((array) ($_POST['req'] ?? []), PARAM_RAW, true);
    $DB->delete_records('railviewer_route_reqs', ['routeid' => $routeid]);
    foreach ($types as $t) {
        foreach ([0] + array_keys($methods) as $mid) {
            $raw = $req[$t->id][$mid] ?? '';
            if ($raw === '' || !is_numeric($raw)) {
                continue;
            }
            $DB->insert_record('railviewer_route_reqs', (object) [
                'routeid' => $routeid, 'methodid' => $mid, 'experiencetypeid' => $t->id,
                'required_count' => max(0, (int) $raw),
            ]);
        }
    }

    $DB->delete_records('railviewer_route_substs', ['routeid' => $routeid]);
    foreach ($clean as $c) {
        $DB->insert_record('railviewer_route_substs', $c);
    }
    $transaction->allow_commit();
    redirect($self, get_string('record_saved', 'local_railvieweradmin'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
foreach (array_unique($errors ?? []) as $e) {
    echo $OUTPUT->notification($e, 'error');
}
echo html_writer::link(new moodle_url('/local/railvieweradmin/routes.php'), get_string('back_to_routes', 'local_railvieweradmin'));
echo html_writer::tag('p', get_string('route_requirements_desc', 'local_railvieweradmin'));
if (!empty($route->pairedrouteid)) {
    $partner = $DB->get_record('railviewer_routes', ['id' => $route->pairedrouteid]);
    if ($partner) {
        $copyurl = new moodle_url($self, ['copypartner' => 1, 'sesskey' => sesskey()]);
        echo html_writer::div(
            html_writer::span(get_string('partner_route_hint', 'local_railvieweradmin', \local_railvieweradmin\learning::route_label($partner)) . ' ')
            . html_writer::link($copyurl, get_string('copy_partner_btn', 'local_railvieweradmin'), [
                'class' => 'btn btn-sm btn-outline-secondary',
                'onclick' => 'return confirm(' . json_encode(get_string('copy_partner_confirm', 'local_railvieweradmin')) . ');',
            ]), 'alert alert-info');
    }
}

if (!$sections) {
    echo $OUTPUT->notification(get_string('route_has_no_sections', 'local_railvieweradmin'), 'warning');
}

// Vorhandene Werte laden.
$durations = [];
foreach ($DB->get_records('railviewer_section_durations') as $d) {
    $durations[$d->sectionid][$d->methodid] = (int) $d->minutes;
}
$reqs = [];
foreach ($DB->get_records('railviewer_route_reqs', ['routeid' => $routeid]) as $r) {
    $reqs[$r->experiencetypeid][$r->methodid] = (int) $r->required_count;
}
$rules = array_values($DB->get_records('railviewer_route_substs', ['routeid' => $routeid], 'sortorder ASC, id ASC'));

$columns = [0 => get_string('method_video', 'local_railvieweradmin')];
foreach ($methods as $m) {
    $columns[$m->id] = format_string($m->name);
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $self->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// --- Anforderungsmatrix
echo html_writer::tag('h3', get_string('requirement_matrix', 'local_railvieweradmin'));
echo html_writer::tag('p', get_string('requirement_matrix_desc', 'local_railvieweradmin'), ['class' => 'text-muted']);
$table = new html_table();
$table->head = array_merge([get_string('experience_type', 'local_railvieweradmin')], array_values($columns));
foreach ($types as $t) {
    $row = [format_string($t->name)];
    foreach ($columns as $mid => $label) {
        $row[] = html_writer::empty_tag('input', ['type' => 'number', 'min' => 0, 'style' => 'width:5em',
            'class' => 'form-control', 'name' => "req[{$t->id}][{$mid}]",
            'value' => $reqs[$t->id][$mid] ?? '']);
    }
    $table->data[] = $row;
}
echo html_writer::table($table);

// --- Dauer
echo html_writer::tag('h3', get_string('section_durations', 'local_railvieweradmin'));
echo html_writer::tag('p', get_string('section_durations_desc', 'local_railvieweradmin'), ['class' => 'text-muted']);
$table = new html_table();
$table->head = array_merge([get_string('section', 'local_railvieweradmin')], array_values($columns));
foreach ($sections as $s) {
    $row = [format_string($s->name)];
    foreach ($columns as $mid => $label) {
        $stored = $durations[$s->id][$mid] ?? '';
        $placeholder = $s->duration > 0 ? (int) ceil($s->duration / 60) : '';
        $row[] = html_writer::empty_tag('input', ['type' => 'number', 'min' => 0, 'style' => 'width:6em',
            'class' => 'form-control', 'name' => "dur[{$s->id}][{$mid}]", 'value' => $stored, 'placeholder' => $placeholder]);
    }
    $table->data[] = $row;
}
echo html_writer::table($table);

// --- Ersetzungsregeln
echo html_writer::tag('h3', get_string('substitution_rules', 'local_railvieweradmin'));
echo html_writer::tag('p', get_string('substitution_rules_desc', 'local_railvieweradmin'), ['class' => 'text-muted']);
$options = [0 => get_string('choosedots')];
foreach ($methods as $m) {
    $options[$m->id] = format_string($m->name);
}
$table = new html_table();
$table->head = [get_string('subst_replaced', 'local_railvieweradmin'), get_string('subst_by', 'local_railvieweradmin'),
    get_string('subst_factor', 'local_railvieweradmin'), get_string('subst_auto', 'local_railvieweradmin')];
$rowcount = max(count($rules) + 2, 3);
for ($i = 0; $i < $rowcount; $i++) {
    $r = $rules[$i] ?? null;
    $table->data[] = [
        html_writer::select($options, "subst[$i][methodid]", $r->methodid ?? 0, false),
        html_writer::select($options, "subst[$i][bymethodid]", $r->bymethodid ?? 0, false),
        html_writer::empty_tag('input', ['type' => 'number', 'min' => 1, 'style' => 'width:5em', 'class' => 'form-control',
            'name' => "subst[$i][factor]", 'value' => $r->factor ?? 1]),
        html_writer::checkbox("subst[$i][auto]", 1, $r && empty($r->userchoice), ''),
    ];
}
echo html_writer::table($table);

echo html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'save', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
