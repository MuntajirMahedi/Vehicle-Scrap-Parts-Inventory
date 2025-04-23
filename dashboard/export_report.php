<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Get export parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'stock_status';
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';

// Function to get report title
function getReportTitle($type) {
    $titles = [
        'stock_status' => 'Stock Status Report',
        'sales_history' => 'Sales History Report',
        'warehouse_inventory' => 'Warehouse Inventory Report',
        'category_analysis' => 'Category Analysis Report',
        'car_statistics' => 'Car Statistics Report',
        'exchange_parts' => 'Exchange Parts Report'
    ];
    return $titles[$type] ?? 'Report';
}

// Function to get report data
function getReportData($conn, $type) {
    switch ($type) {
        case 'stock_status':
            return $conn->query("
                SELECT
                    c.category_name,
                    p.part_name,
                    COUNT(CASE WHEN s.stock_status = 'in_stock' THEN 1 END) as in_stock,
                    COUNT(CASE WHEN s.stock_status = 'sold' THEN 1 END) as sold,
                    COUNT(*) as total
                FROM tbl_part p
                LEFT JOIN tbl_category c ON p.part_category_id = c.category_id
                LEFT JOIN tbl_stock s ON p.part_id = s.stock_part_id
                GROUP BY p.part_id
                ORDER BY c.category_name, p.part_name
            ");

        case 'sales_history':
            return $conn->query("
                SELECT
                    s.stock_sold_date,
                    CONCAT(b.brand_name, ' ', m.model_name, ' (', c.car_year, ')') as car_details,
                    c.car_reg_number,
                    cat.category_name,
                    p.part_name,
                    s.stock_exchange_received,
                    s.stock_customer,
                    s.stock_customer_mobileno
                FROM tbl_stock s
                JOIN tbl_car c ON s.stock_car_id = c.car_id
                JOIN tbl_brand b ON c.car_brand_id = b.brand_id
                JOIN tbl_model m ON c.car_model_id = m.model_id
                JOIN tbl_part p ON s.stock_part_id = p.part_id
                JOIN tbl_category cat ON p.part_category_id = cat.category_id
                WHERE s.stock_status = 'sold'
                ORDER BY s.stock_sold_date DESC
            ");

        // Add other report types here...
    }
}

// Get report data
$result = getReportData($conn, $type);
$title = getReportTitle($type);

if ($format === 'excel') {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('NOOR AUTO SCRAP')
        ->setLastModifiedBy('NOOR AUTO SCRAP')
        ->setTitle($title)
        ->setSubject($title)
        ->setDescription('Generated on ' . date('Y-m-d H:i:s'));

    // Style for header
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4E73DF'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];

    // Set headers based on report type
    switch ($type) {
        case 'stock_status':
            $headers = ['Category', 'Part Name', 'In Stock', 'Sold', 'Total'];
            break;

        case 'sales_history':
            $headers = ['Date', 'Car Details', 'Reg Number', 'Category', 'Part', 'Exchange', 'Customer', 'Mobile'];
            break;

        // Add headers for other report types...
    }

    // Add title
    $sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');
    $sheet->setCellValue('A1', $title);
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // Add generation date
    $sheet->mergeCells('A2:' . chr(64 + count($headers)) . '2');
    $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['italic' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // Add headers
    $col = 'A';
    $row = 4;
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    $sheet->getStyle('A4:' . chr(64 + count($headers)) . '4')->applyFromArray($headerStyle);

    // Add data
    $row = 5;
    while ($data = $result->fetch_assoc()) {
        $col = 'A';
        switch ($type) {
            case 'stock_status':
                $sheet->setCellValue($col++ . $row, $data['category_name']);
                $sheet->setCellValue($col++ . $row, $data['part_name']);
                $sheet->setCellValue($col++ . $row, $data['in_stock']);
                $sheet->setCellValue($col++ . $row, $data['sold']);
                $sheet->setCellValue($col++ . $row, $data['total']);
                break;

            case 'sales_history':
                $sheet->setCellValue($col++ . $row, date('d M Y', strtotime($data['stock_sold_date'])));
                $sheet->setCellValue($col++ . $row, $data['car_details']);
                $sheet->setCellValue($col++ . $row, $data['car_reg_number']);
                $sheet->setCellValue($col++ . $row, $data['category_name']);
                $sheet->setCellValue($col++ . $row, $data['part_name']);
                $sheet->setCellValue($col++ . $row, $data['stock_exchange_received']);
                $sheet->setCellValue($col++ . $row, $data['stock_customer']);
                $sheet->setCellValue($col++ . $row, $data['stock_customer_mobileno']);
                break;

            // Add data handling for other report types...
        }
        $row++;
    }

    // Style the data
    $dataRange = 'A5:' . chr(64 + count($headers)) . ($row - 1);
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    // Set response headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $title . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Save file to PHP output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else {
    // PDF Export
    require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, $this->title, 0, true, 'C', 0);
            $this->SetFont('helvetica', 'I', 10);
            $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, true, 'C', 0);
            $this->Ln(10);
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
        }
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->title = $title;

    // Set document information
    $pdf->SetCreator('NOOR AUTO SCRAP');
    $pdf->SetAuthor('NOOR AUTO SCRAP');
    $pdf->SetTitle($title);

    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 20, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Get headers and data based on report type
    switch ($type) {
        case 'stock_status':
            $headers = ['Category', 'Part Name', 'In Stock', 'Sold', 'Total'];
            $widths = [40, 60, 30, 30, 30];
            break;

        case 'sales_history':
            $headers = ['Date', 'Car Details', 'Reg Number', 'Category', 'Part', 'Exchange', 'Customer', 'Mobile'];
            $widths = [25, 40, 30, 30, 35, 20, 35, 30];
            break;

        // Add headers and widths for other report types...
    }

    // Add table headers
    $pdf->SetFillColor(78, 115, 223);
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);

    $x = $pdf->GetX();
    $y = $pdf->GetY();
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 10, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Add table data
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(245, 245, 245);
    $fill = false;

    while ($data = $result->fetch_assoc()) {
        switch ($type) {
            case 'stock_status':
                $pdf->Cell($widths[0], 8, $data['category_name'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[1], 8, $data['part_name'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[2], 8, $data['in_stock'], 1, 0, 'C', $fill);
                $pdf->Cell($widths[3], 8, $data['sold'], 1, 0, 'C', $fill);
                $pdf->Cell($widths[4], 8, $data['total'], 1, 0, 'C', $fill);
                break;

            case 'sales_history':
                $pdf->Cell($widths[0], 8, date('d M Y', strtotime($data['stock_sold_date'])), 1, 0, 'C', $fill);
                $pdf->Cell($widths[1], 8, $data['car_details'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[2], 8, $data['car_reg_number'], 1, 0, 'C', $fill);
                $pdf->Cell($widths[3], 8, $data['category_name'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[4], 8, $data['part_name'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[5], 8, $data['stock_exchange_received'], 1, 0, 'C', $fill);
                $pdf->Cell($widths[6], 8, $data['stock_customer'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[7], 8, $data['stock_customer_mobileno'], 1, 0, 'C', $fill);
                break;

            // Add data handling for other report types...
        }
        $pdf->Ln();
        $fill = !$fill;
    }

    // Output the PDF
    $pdf->Output($title . '_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}
?>
