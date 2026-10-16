<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Nach der Erstinstallation: Standard-Lernmethoden und -Berufserfahrungstyp anlegen.
 */
function xmldb_local_railvieweradmin_install() {
    \local_railvieweradmin\learning::seed_defaults();
    return true;
}
