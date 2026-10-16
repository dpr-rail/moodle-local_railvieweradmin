<?php
namespace local_railvieweradmin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

/**
 * PDF mit wiederkehrender Kopfzeile (Berichtstitel + Angaben zur Person/zum Bericht) und
 * Fußzeile mit Seitenzahl "Seite x von y" auf jeder Seite.
 *
 * @package local_railvieweradmin
 */
class report_pdf extends \pdf {

    /** @var string Berichtstitel in der Kopfzeile */
    public $reporttitle = '';
    /** @var string zweite Kopfzeile, z.B. "Name · ID-Nr. · Einsatzstellen" */
    public $reportsubtitle = '';
    /** @var string Text links in der Fußzeile */
    public $footertext = '';

    public function Header() {
        $this->SetY(6);
        $this->SetFont('freesans', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 5, $this->reporttitle, 0, 1, 'L');
        if ($this->reportsubtitle !== '') {
            $this->SetFont('freesans', '', 8);
            $this->SetTextColor(70, 70, 70);
            $this->Cell(0, 4, $this->reportsubtitle, 0, 1, 'L');
        }
        $y = $this->GetY() + 1;
        $this->SetDrawColor(150, 150, 150);
        $this->Line($this->original_lMargin, $y, $this->getPageWidth() - $this->original_rMargin, $y);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer() {
        $y = $this->getPageHeight() - 11;
        $this->SetY($y);
        $this->SetDrawColor(150, 150, 150);
        $this->Line($this->original_lMargin, $y, $this->getPageWidth() - $this->original_rMargin, $y);
        $this->SetFont('freesans', '', 8);
        $this->SetTextColor(70, 70, 70);
        $width = $this->getPageWidth() - $this->original_lMargin - $this->original_rMargin;
        $this->SetXY($this->original_lMargin, $y + 1);
        $this->Cell($width / 2, 5, $this->footertext, 0, 0, 'L');
        $this->Cell($width / 2, 5, get_string('pdf_page_x_of_y', 'local_railvieweradmin', (object) [
            'page' => $this->getAliasNumPage(), 'total' => $this->getAliasNbPages()]), 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
}
