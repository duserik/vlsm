<?php
ob_start();
#require_once('../../startup.php');




$general = new \Vlsm\Models\General($db);
$id = base64_decode($_GET['id']);


if (isset($_GET['type']) && $_GET['type'] == 'vl') {
    $refTable = "vl_request_form";
    $refPrimaryColumn = "vl_sample_id";
    $patientIdColumn = 'patient_art_no';
    $worksheetName = 'Viral Load Test Worksheet';
} else if (isset($_GET['type']) && $_GET['type'] == 'eid') {
    $refTable = "eid_form";
    $refPrimaryColumn = "eid_id";
    $patientIdColumn = 'child_id';
    $worksheetName = 'EID Test Worksheet';
}else if (isset($_GET['type']) && $_GET['type'] == 'covid19') {
    $refTable = "form_covid19";
    $refPrimaryColumn = "covid19_id";
    $patientIdColumn = 'patient_id';
    $worksheetName = 'Covid-19 Test Worksheet';
}


$barcodeFormat = $general->getGlobalConfig('barcode_format');

$barcodeFormat = isset($barcodeFormat) && $barcodeFormat != null ? $barcodeFormat : 'C39';

if ($id > 0) {

    if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "barcode") && !is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "barcode")) {
        mkdir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "barcode");
    }
    $lQuery = "SELECT * from global_config where name='logo'";
    $lResult = $db->query($lQuery);

    $tQuery = "SELECT * from global_config where name='header'";
    $tResult = $db->query($tQuery);

    $bQuery = "SELECT * from batch_details as b_d LEFT JOIN import_config as i_c ON i_c.config_id=b_d.machine where batch_id=$id";
    $bResult = $db->query($bQuery);

    $dateQuery = "SELECT sample_tested_datetime,result_reviewed_datetime from $refTable where sample_batch_id='" . $id . "' AND (sample_tested_datetime IS NOT NULL AND sample_tested_datetime not like '' AND sample_tested_datetime!= '00000-00-00 00:00:00') LIMIT 1";
    $dateResult = $db->query($dateQuery);
    $resulted = '';
    $reviewed = '';
    if (isset($dateResult[0]['sample_tested_datetime']) && $dateResult[0]['sample_tested_datetime'] != '' && $dateResult[0]['sample_tested_datetime'] != NULL && $dateResult[0]['sample_tested_datetime'] != '0000-00-00 00:00:00') {
        $sampleTestedDate = explode(" ", $dateResult[0]['sample_tested_datetime']);
        $resulted = $general->humanDateFormat($sampleTestedDate[0]) . " " . $sampleTestedDate[1];
    }
    if (isset($dateResult[0]['result_reviewed_datetime']) && $dateResult[0]['result_reviewed_datetime'] != '' && $dateResult[0]['result_reviewed_datetime'] != NULL && $dateResult[0]['result_reviewed_datetime'] != '0000-00-00 00:00:00') {
        $resultReviewdDate = explode(" ", $dateResult[0]['result_reviewed_datetime']);
        $reviewed = $general->humanDateFormat($resultReviewdDate[0]) . " " . $resultReviewdDate[1];
    }
    if (count($bResult) > 0) {
        // Extend the TCPDF class to create custom Header and Footer
        class MYPDF extends TCPDF
        {
            public function setHeading($logo, $text, $batch, $resulted, $reviewed,$worksheetName)
            {
                $this->logo = $logo;
                $this->text = $text;
                $this->batch = $batch;
                $this->resulted = $resulted;
                $this->reviewed = $reviewed;
                $this->worksheetName = $worksheetName;
            }
            //Page header
            public function Header()
            {
                // Logo
                //$image_file = K_PATH_IMAGES.'logo_example.jpg';
                //$this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                // Set font
                if (trim($this->logo) != "") {
                    if (file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $this->logo)) {
                        $image_file = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $this->logo;
                        $this->Image($image_file, 15, 10, 15, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
                    }
                }
                $this->SetFont('helvetica', '', 7);
                $this->writeHTMLCell(30, 0, 10, 26, $this->text, 0, 0, 0, true, 'A', true);
                $this->SetFont('helvetica', '', 13);
                $this->writeHTMLCell(0, 0, 0, 10, 'Batch Number/Code : ' . $this->batch, 0, 0, 0, true, 'C', true);
                $this->writeHTMLCell(0, 0, 0, 20, $this->worksheetName, 0, 0, 0, true, 'C', true);
                $this->SetFont('helvetica', '', 9);
                $this->writeHTMLCell(0, 0, 144, 10, 'Result On : ' . $this->resulted, 0, 0, 0, true, 'C', true);
                $this->writeHTMLCell(0, 0, 144, 16, 'Reviewed On : ' . $this->reviewed, 0, 0, 0, true, 'C', true);
                $html = '<hr/>';
                $this->writeHTMLCell(0, 0, 10, 32, $html, 0, 0, 0, true, 'J', true);
            }

            // Page footer
            public function Footer()
            {
                // Position at 15 mm from bottom
                $this->SetY(-15);
                // Set font
                $this->SetFont('helvetica', 'I', 8);
                // Page number
                $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }

        // create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setHeading($lResult[0]['value'], $tResult[0]['value'], $bResult[0]['batch_code'], $resulted, $reviewed, $worksheetName);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('VLSM');
        $pdf->SetTitle('VLSM BATCH');
        $pdf->SetSubject('VLSM BATCH');
        $pdf->SetKeywords('VLSM BATCH');

        $pdf->SetMargins(0, 0, 0);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 36, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // set font
        $pdf->SetFont('helvetica', '', 10);

        // add a page
        $pdf->AddPage();

        $tbl = '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">
                <tr style="border-bottom:1px solid #333 !important;">
                    <th align="center" width="6%"><strong>Pos.</strong></th>
                    <th align="center" width="20%"><strong>Sample ID</strong></th>
                    <th align="center" width="35%"><strong>BARCODE</strong></th>
                    <th align="center" width="13%"><strong>Patient Code</strong></th>
                    <th align="center" width="13%"><strong>Lot No. / <br>Exp. Date</strong></th>
                    <th align="center" width="13%"><strong>Test Result</strong></th>
                </tr>';
        $tbl .= '</table>';

        if (isset($bResult[0]['label_order']) && trim($bResult[0]['label_order']) != '') {
            $jsonToArray = json_decode($bResult[0]['label_order'], true);
            $sampleCounter = 1;
            for ($j = 0; $j < count($jsonToArray); $j++) {
                // if($pdf->getY()>=250){
                //     $pdf->AddPage();
                // }
                $xplodJsonToArray = explode("_", $jsonToArray[$j]);
                if (count($xplodJsonToArray) > 1 && $xplodJsonToArray[0] == "s") {
                    $sampleQuery = "SELECT sample_code,result,lot_number,lot_expiration_date,$patientIdColumn from $refTable where $refPrimaryColumn =$xplodJsonToArray[1]";
                    $sampleResult = $db->query($sampleQuery);

                    $params = $pdf->serializeTCPDFtagParameters(array($sampleResult[0]['sample_code'], $barcodeFormat, '', '', '', 7, 0.25, array('border' => false, 'align' => 'C', 'padding' => 1, 'fgcolor' => array(0, 0, 0), 'bgcolor' => array(255, 255, 255), 'text' => false, 'font' => 'helvetica', 'fontsize' => 7, 'stretchtext' => 2), 'N'));
                    $lotDetails = '';
                    $lotExpirationDate = '';
                    if (isset($sampleResult[0]['lot_expiration_date']) && $sampleResult[0]['lot_expiration_date'] != '' && $sampleResult[0]['lot_expiration_date'] != NULL && $sampleResult[0]['lot_expiration_date'] != '0000-00-00') {
                        if (trim($sampleResult[0]['lot_number']) != '') {
                            $lotExpirationDate .= '<br>';
                        }
                        $lotExpirationDate .= $general->humanDateFormat($sampleResult[0]['lot_expiration_date']);
                    }
                    $lotDetails = $sampleResult[0]['lot_number'] . $lotExpirationDate;
                    $tbl .= '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">';
                    $tbl .= '<tr nobr="true" style="border-bottom:1px solid #333;width:100%;">';
                    $tbl .= '<td  align="center" width="6%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sampleCounter . '.</td>';
                    $tbl .= '<td  align="center" width="18%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sampleResult[0]['sample_code'] . '</td>';
                    $tbl .= '<td  align="center" width="35%" style="vertical-align:middle !important;border-bottom:1px solid #333;"><tcpdf method="write1DBarcode" params="' . $params . '" /></td>';
                    $tbl .= '<td  align="center" width="15%" style="vertical-align:middle;border-bottom:1px solid #333;font-size:0.9em;">' . $sampleResult[0][$patientIdColumn] . '</td>';
                    $tbl .= '<td  align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $lotDetails . '</td>';
                    $tbl .= '<td  align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sampleResult[0]['result'] . '</td>';
                    $tbl .= '</tr>';
                    $tbl .= '</table>';
                } else {
                    $label = str_replace("_", " ", $jsonToArray[$j]);
                    $label = str_replace("in house", "In-House", $label);
                    $label = ucwords(str_replace("no of ", " ", $label));
                    $tbl .= '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">';
                    $tbl .= '<tr nobr="true" style="border-bottom:1px solid #333;width:100%;">';
                    $tbl .= '<td align="center" width="6%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sampleCounter . '.</td>';
                    $tbl .= '<td align="center" width="20%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $label . '</td>';
                    $tbl .= '<td align="center" width="35%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>';
                    $tbl .= '<td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>';
                    $tbl .= '<td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>';
                    $tbl .= '<td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>';
                    $tbl .= '</tr>';
                    $tbl .= '</table>';
                }
                $sampleCounter++;
            }
        } else {
            $noOfInHouseControls = 0;
            if (isset($bResult[0]['number_of_in_house_controls']) && $bResult[0]['number_of_in_house_controls'] != '' && $bResult[0]['number_of_in_house_controls'] != NULL) {
                $noOfInHouseControls = $bResult[0]['number_of_in_house_controls'];
                for ($i = 1; $i <= $bResult[0]['number_of_in_house_controls']; $i++) {
                    $tbl .= '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">';
                    $tbl .= '<tr nobr="true" style="border-bottom:1px solid #333;width:100%;">
                            <td align="center" width="6%" style="vertical-align:middle;border-bottom:1px solid #333">' . $i . '.</td>
                            <td align="center" width="20%" style="vertical-align:middle;border-bottom:1px solid #333">In-House Controls ' . $i . '</td>
                            <td align="center" width="35%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                            <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                            <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                            <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                        </tr>';
                    $tbl .= '</table>';
                }
            }
            $noOfManufacturerControls = 0;
            if (isset($bResult[0]['number_of_manufacturer_controls']) && $bResult[0]['number_of_manufacturer_controls'] != '' && $bResult[0]['number_of_manufacturer_controls'] != NULL) {
                $noOfManufacturerControls = $bResult[0]['number_of_manufacturer_controls'];
                for ($i = 1; $i <= $bResult[0]['number_of_manufacturer_controls']; $i++) {
                    $sNo = $noOfInHouseControls + $i;
                    $tbl .= '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">';
                    $tbl .= '<tr nobr="true" style="border-bottom:1px solid #333;width:100%;">
                    <td align="center" width="6%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sNo . '.</td>
                    <td align="center" width="20%" style="vertical-align:middle;border-bottom:1px solid #333">Manfacturing Controls ' . $i . '</td>
                    <td align="center" width="35%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                    <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                    <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                    <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333"></td>
                    </tr>';
                    $tbl .= '</table>';
                }
            }
            $noOfCalibrators = 0;
            if (isset($bResult[0]['number_of_calibrators']) && $bResult[0]['number_of_calibrators'] != '' && $bResult[0]['number_of_calibrators'] != NULL) {
                $noOfCalibrators = $bResult[0]['number_of_calibrators'];
                for ($i = 1; $i <= $bResult[0]['number_of_calibrators']; $i++) {
                    $sNo = $noOfInHouseControls + $noOfManufacturerControls + $i;
                    $tbl .= '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">';
                    $tbl .= '<tr nobr="true" style="border-bottom:1px solid #333;width:100%;">
                    <td align="center" width="6%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sNo . '.</td>
                    <td align="center" width="20%" style="vertical-align:middle;border-bottom:1px solid #333;">Calibrators ' . $i . '</td>
                    <td align="center" width="35%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>
                    <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>
                    <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>
                    <td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;"></td>
                    </tr>';
                    $tbl .= '</table>';
                }
            }
            $sampleCounter = ($noOfInHouseControls + $noOfManufacturerControls + $noOfCalibrators + 1);
            $sQuery = "SELECT sample_code,lot_number,lot_expiration_date,result,$patientIdColumn from $refTable where sample_batch_id=$id";
            $result = $db->query($sQuery);
            foreach ($result as $sample) {
                // if($pdf->getY()>=250){
                //   $pdf->AddPage();
                // }
                $params = $pdf->serializeTCPDFtagParameters(array($sample['sample_code'], $barcodeFormat, '', '', '', 7, 0.25, array('border' => false, 'align' => 'C', 'padding' => 1, 'fgcolor' => array(0, 0, 0), 'bgcolor' => array(255, 255, 255), 'text' => false, 'font' => 'helvetica', 'fontsize' => 7, 'stretchtext' => 2), 'N'));
                $lotDetails = '';
                $lotExpirationDate = '';
                if (isset($sample['lot_expiration_date']) && $sample['lot_expiration_date'] != '' && $sample['lot_expiration_date'] != NULL && $sample['lot_expiration_date'] != '0000-00-00') {
                    if (trim($sample['lot_number']) != '') {
                        $lotExpirationDate .= '<br>';
                    }
                    $lotExpirationDate .= $general->humanDateFormat($sample['lot_expiration_date']);
                }
                $lotDetails = $sample['lot_number'] . $lotExpirationDate;

                $tbl .= '<table nobr="true" cellspacing="0" cellpadding="2" style="width:100%;">';
                $tbl .= '<tr nobr="true" style="border-bottom:1px solid #333 !important;width:100%;">';
                $tbl .= '<td align="center" width="6%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sampleCounter . '.</td>';
                $tbl .= '<td align="center" width="20%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sample['sample_code'] . '</td>';
                $tbl .= '<td align="center" width="35%" style="vertical-align:middle;border-bottom:1px solid #333;"><tcpdf method="write1DBarcode" params="' . $params . '" /></td>';
                $tbl .= '<td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sample[$patientIdColumn] . '</td>';
                $tbl .= '<td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $lotDetails . '</td>';
                $tbl .= '<td align="center" width="13%" style="vertical-align:middle;border-bottom:1px solid #333;">' . $sample['result'] . '</td>';
                $tbl .= '</tr>';
                $tbl .= '</table>';

                $sampleCounter++;
            }
        }


        $pdf->writeHTML($tbl, true, false, false, false, '');
        //$pdf->writeHTMLCell('', '', 12,$pdf->getY(),$tbl, 0, 1, 0, true, 'C', true);
        $filename = "VLSM-" . trim($bResult[0]['batch_code']) . '-' . date('d-m-Y-h-i-s') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
        /* $pdf->Output(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'barcode' . DIRECTORY_SEPARATOR . $filename, "F");
        echo $filename; */
    }
}
