<?php
require_once '../../vendor/autoload.php';

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest; // <--- เพิ่มบรรทัดนี้

$property_id = '497553877';
$credentials_path = __DIR__ . '/keydataperfumewebsite/spatial-vision-470405-u2-1572283a8a60.json';

try {
    $client = new BetaAnalyticsDataClient([
        'credentials' => $credentials_path,
    ]);

    // สร้าง RunReportRequest object ก่อน
    $request = (new RunReportRequest())
        ->setProperty('properties/' . $property_id)
        ->setDateRanges([
            new DateRange([
                'start_date' => '7daysAgo',
                'end_date' => 'today',
            ]),
        ])
        ->setDimensions([
            new Dimension(['name' => 'date']),
        ])
        ->setMetrics([
            new Metric(['name' => 'activeUsers']),
        ]);

    // ส่ง object เข้าไปในฟังก์ชัน runReport()
    $response = $client->runReport($request); // <--- แก้ไขบรรทัดนี้

    // แสดงผลลัพธ์
    echo "Report is successful! <br>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}
?>