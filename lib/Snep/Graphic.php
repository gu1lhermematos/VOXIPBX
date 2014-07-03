<?php

/**
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
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
require_once "includes/pChart/pData.class";
require_once "includes/pChart/pChart.class";

/**
 *
 * @see Snep_Graphic
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Iago Uilian Berndt
 * 
 */
class Snep_Graphic {

    /**
     * getGraphic
     * @param <array> $label
     * @param <array> $series
     * @param <string> $title
     * @param <boolean> $line
     * @param <boolean> $time
     * @param <int> $left
     * @return \pChart
     */
    public static function getGraphic($label, $series, $title = "", $line = true, $time = false, $left = 100) {
        ini_set('memory_limit', '-1');

        if (!count($series) || !count($label)) {
            $series = array(array("null", "", array(0)));
            $label = array("label", "", array(0));
        }
        $DataSet = new pData;

        foreach ($series as $serie) {
            $DataSet->AddPoint($serie[2], $serie[0]);
            $DataSet->AddSerie($serie[0]);
            $DataSet->SetSerieName($serie[1], $serie[0]);
        }
        if ($time) {
            $DataSet->SetYAxisFormat("time");
            $DataSet->SetYAxisName("Tempo");
        } else {
            $DataSet->SetYAxisName("Total");
        }

        $DataSet->AddPoint($label[2], $label[0]);
        $DataSet->SetAbsciseLabelSerie($label[0]);
        $DataSet->SetXAxisFormat($label[0]);
        $DataSet->SetXAxisName($label[1]);

        $Graphic = new pChart(960, 480);
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);
        $Graphic->setGraphArea(90, 30, 920, 390);
        $Graphic->drawFilledRoundedRectangle(7, 7, 953, 473, 5, 250, 250, 250);
        $Graphic->drawRoundedRectangle(5, 5, 955, 475, 5, 230, 230, 230);
        $Graphic->drawGraphArea(255, 255, 255, TRUE);
        $Graphic->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_NORMAL, 150, 150, 150, TRUE, 90, 2, TRUE, ceil(count($label[2]) / 25));
        $Graphic->drawGrid(4, TRUE, 230, 230, 230, 50);

        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);
        $Graphic->drawTreshold(0, 143, 55, 72, TRUE, TRUE);

        if ($line) {
            $Graphic->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
            $Graphic->drawPlotGraph($DataSet->GetData(), $DataSet->GetDataDescription(), 3, 2, 255, 255, 255);
        } else {
            $Graphic->drawBarGraph($DataSet->GetData(), $DataSet->GetDataDescription(), TRUE, 80);
        }

        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);
        $Graphic->drawLegend($left, 40, $DataSet->GetDataDescription(), 255, 255, 255);
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 12);
        $Graphic->drawTitle(60, 22, "$title", 50, 50, 50, 910);
        return $Graphic;
    }

    /**
     * getGraphicForAgentReport
     * @param <array> $label
     * @param <array> $series
     * @param <string> $title
     * @param <boolean> $line
     * @param <boolean> $time
     * @param <int> $left
     * @param <string> $type
     * @return \pChart
     */
    public static function getGraphicForAgentReport($label, $series, $title = "", $line = true, $time = false, $left = 100, $type) {
        ini_set('memory_limit', '-1');

        if (!count($series) || !count($label)) {
            $series = array(array("null", "", array(0)));
            $label = array("label", "", array(0));
        }
        $DataSet = new pData;

        foreach ($series as $serie) {
            $DataSet->AddPoint($serie[2], $serie[0]);
            $DataSet->AddSerie($serie[0]);
            $DataSet->SetSerieName($serie[1], $serie[0]);
        }

        if ($type == 'queue') {
            //$DataSet->SetYAxisFormat("Agent");
            $DataSet->SetYAxisName("Attendance");
        } else {
            $DataSet->SetYAxisName("Agent");
        }

        $DataSet->AddPoint($label[2], $label[0]);
        $DataSet->SetAbsciseLabelSerie($label[0]);

        $DataSet->SetXAxisFormat($label[0]);
        $DataSet->SetXAxisName($label[1]);

        //$Graphic = new pChart(960,600);
        $Graphic = new pChart(480, 300);
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);

        //$Graphic->setGraphArea(70,20,920,450);
        $Graphic->setGraphArea(70, 20, 420, 200);

        $Graphic->drawFilledRoundedRectangle(7, 7, 470, 295, 5, 250, 250, 250);

        $Graphic->drawRoundedRectangle(5, 5, 472, 297, 5, 230, 230, 230);

        $Graphic->drawGraphArea(255, 255, 255, TRUE);

        $Graphic->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_ADDALLSTART0, 150, 150, 150, TRUE, 90, 2, TRUE);

        $Graphic->drawGrid(4, TRUE, 230, 230, 230, 50);

        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);
        $Graphic->drawTreshold(0, 143, 55, 72, TRUE, TRUE);

        if ($line) {
            $Graphic->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
            $Graphic->drawPlotGraph($DataSet->GetData(), $DataSet->GetDataDescription(), 3, 2, 255, 255, 255);
        } else {
            $Graphic->drawStackedBarGraph($DataSet->GetData(), $DataSet->GetDataDescription(), TRUE);
        }
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);
        $Graphic->drawLegend($left, 210, $DataSet->GetDataDescription(), 255, 255, 255);
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 12);
        $Graphic->drawTitle(60, 22, "$title", 50, 50, 50, 410);
        return $Graphic;
    }

    /**
     * getGraphicForAttendance
     * @param <array> $label
     * @param <array> $series
     * @param <string> $title
     * @param <boolean> $line
     * @param <boolean> $time
     * @param <int> $left
     * @return \pChart
     */
    public static function getGraphicForAttendance($label, $series, $title = "", $line = true, $time = false, $left = 100) {
        ini_set('memory_limit', '-1');

        if (!count($series) || !count($label)) {
            $series = array(array("null", "", array(0)));
            $label = array("label", "", array(0));
        }
        $DataSet = new pData;

        foreach ($series as $serie) {
            $DataSet->AddPoint($serie[2], $serie[0]);
            $DataSet->AddSerie($serie[0]);
            $DataSet->SetSerieName($serie[1], $serie[0]);
        }

        if ($time) {
            $DataSet->SetYAxisFormat("time");
            $DataSet->SetYAxisName("Tempo");
        } else {
            $DataSet->SetYAxisName("Attendance");
        }

        $DataSet->AddPoint($label[2], $label[0]);
        $DataSet->SetAbsciseLabelSerie($label[0]);

        $DataSet->SetXAxisFormat($label[0]);
        $DataSet->SetXAxisName($label[1]);

        $Graphic = new pChart(260, 260);
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);


        $Graphic->setGraphArea(70, 30, 200, 200);


        $Graphic->drawFilledRoundedRectangle(7, 7, 250, 253, 5, 250, 250, 250);

        $Graphic->drawRoundedRectangle(5, 5, 253, 256, 5, 230, 230, 230);

        $Graphic->drawGraphArea(255, 255, 255, TRUE);

        $Graphic->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_ADDALLSTART0, 0, 0, 0, TRUE, 50, 2, TRUE);
        $Graphic->drawGrid(4, TRUE, 200, 200, 200, 30);

        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);

        $Graphic->drawTreshold(0, 143, 55, 72, TRUE, TRUE);

        if ($line) {

            $Graphic->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
            $Graphic->drawPlotGraph($DataSet->GetData(), $DataSet->GetDataDescription(), 3, 2, 255, 255, 255);
        } else {

            $Graphic->drawStackedBarGraph($DataSet->GetData(), $DataSet->GetDataDescription(), TRUE);
        }
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 9);
        $Graphic->drawLegend($left, 215, $DataSet->GetDataDescription(), 255, 255, 255);
        $Graphic->setFontProperties("includes/pChart/Fonts/tahoma.ttf", 12);
        $Graphic->drawTitle(100, 28, "$title", 50, 50, 50, 200);
        return $Graphic;
    }

}

?>