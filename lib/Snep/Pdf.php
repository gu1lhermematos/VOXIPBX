<?php

/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */
require_once "includes/fpdf/fpdf.php";

/**
 * Snep main menu system
 *
 * @category  Snep
 * @package   Snep_Pdf
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Iago Uilian Berndt
 */
class Snep_Pdf extends FPDF {

    /**
     * graphic
     * @param <array> $files
     */
    public function graphic($files) {

        ini_set('memory_limit', '-1');

        if (!is_array($files))
            $files = array($files);
        $this->AddPage();
        $this->Image("modules/default/img/logo_snep_system.png", 6, 6, 22, 5);
        $top = 15;
        foreach ($files as $key => $file) {
            $size = getimagesize($file);
            $prop = $size[1] / $size[0];
            if ($top + 200 * $prop + 5 > 295) {
                $this->AddPage();
                $this->Image("modules/default/img/logo_snep_system.png", 6, 6, 22, 5);
                $top = 15;
            }
            $this->Image($file, 5, $top, 200, 200 * $prop, '', '');
            $top += 200 * $prop + 5;
        }
    }

    /**
     * table
     * @param <array> $w
     * @param <array> $header
     * @param <array> $data
     * @param <string> $orientation
     * @param <int> $rep
     */
    public function table($w, $header, $data, $orientation = 'P', $rep = 42) {
        $rep -= 1;
        ini_set('memory_limit', '-1');

        $this->AddPage($orientation);
        $i18n = Zend_Registry::get("i18n");
        $this->SetLineWidth(.3);
        $this->SetDrawColor(255, 255, 255);

        $fill = false;
        foreach ($data as $k => $row) {
            if (!($k % $rep)) {

                $this->SetTextColor(255);
                $this->SetFillColor(255, 255, 255);
                $this->Ln();
                $this->Cell(array_sum($w), 7, "", 1, 0, 'L', true);
                $this->Image("modules/default/img/logo_snep_system.png", 10, 10, 22, 5);

                $this->SetTextColor(255);
                $this->SetFillColor(35, 35, 35);
                $this->SetFont('', 'B');

                $this->Ln();
                for ($i = 0; $i < count($header); $i++)
                    $this->Cell($w[$i], 10, utf8_decode($i18n->translate($header[$i])), 1, 0, 'C', true);

                $this->SetFillColor(224, 235, 255);
                $this->SetTextColor(0);
                $this->SetFont('');
            }

            if (count($row) != count($w)) {
                $this->Ln();
                $this->SetTextColor(255);
                $this->SetFillColor(55, 55, 55);
                $this->SetFont('', 'B');

                $this->Cell(array_sum($w), 6, utf8_decode($row[0]), 1, 0, 'L', true);

                $this->SetTextColor(0);
                $this->SetFillColor(224, 235, 255);
                $this->SetFont('');
            } else {
                $this->Ln();
                foreach ($row as $key => $col) {
                    $this->Cell($w[$key], 6, utf8_decode($col), 'LR', 0, 'L', $fill);
                }
                $fill = !$fill;
            }
        }

        $this->Cell(array_sum($w), 0, '', 'T');
    }

}