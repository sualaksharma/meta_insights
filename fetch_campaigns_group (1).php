<?php

require '/home/qrl61b5s6g6w/public_html/mirror/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '/home/qrl61b5s6g6w/public_html/mirror/vendor/phpmailer/phpmailer/src/SMTP.php';
require '/home/qrl61b5s6g6w/public_html/mirror/vendor/phpmailer/phpmailer/src/Exception.php';

// Start output buffering
ob_start();

$accessToken = $_POST['accessToken'];
$accountId = $_POST['accountId'];

// Construct the API request URL to get only active campaigns with required fields
$url = "https://graph.facebook.com/v13.0/{$accountId}/campaigns?fields=id,name,status&access_token={$accessToken}&status=ACTIVE";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

$campaignData = json_decode($result, true);

$htmlTable = '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
$htmlTable .= '<tr>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Campaign ID</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Campaign Name</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Impressions</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Reach</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Confirmed Action</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Cost per Confirmed Action</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Total Spend</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Date Start</th>';
$htmlTable .= '<th style="border: 1px solid #dddddd; background-color: #f2f2f2; padding: 8px;">Date Stop</th>';
$htmlTable .= '</tr>';

if (isset($campaignData['data'])) {
    foreach ($campaignData['data'] as $campaign) {
        $campaignId = $campaign['id'];
        $campaignName = $campaign['name'];

        // Fetch insights for each campaign
        $insightsUrl = "https://graph.facebook.com/v13.0/{$campaignId}/insights?fields=impressions,reach,actions,cost_per_action_type,spend,date_start,date_stop&access_token={$accessToken}";

        $insightsCh = curl_init();
        curl_setopt($insightsCh, CURLOPT_URL, $insightsUrl);
        curl_setopt($insightsCh, CURLOPT_RETURNTRANSFER, true);
        $insightsResult = curl_exec($insightsCh);
        curl_close($insightsCh);

        $insightsData = json_decode($insightsResult, true);

        // Check if insights are available
        if (isset($insightsData['data'][0])) {
            $impressions = $insightsData['data'][0]['impressions'];
            $reach = $insightsData['data'][0]['reach'];

            // Calculate Confirmed Action, Cost per Confirmed Action, etc.
            $confirmedCalls = 0; // Default value if not found

            if (isset($insightsData['data'][0]['actions'])) {
                foreach ($insightsData['data'][0]['actions'] as $action) {
                    if ($action['action_type'] === 'click_to_call_call_confirm' || $action['action_type'] === 'purchase' || $action['action_type'] === 'lead') {
                        $confirmedCalls += $action['value'];
                    }
                }
            }

            $costPerConfirmedCall = ($confirmedCalls > 0 && $insightsData['data'][0]['spend'] > 0) ? number_format($insightsData['data'][0]['spend'] / $confirmedCalls, 2) : 'Not Available';

            // Add the data to the HTML table
            $htmlTable .= '<tr>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $campaignId . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $campaignName . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $impressions . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $reach . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $confirmedCalls . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $costPerConfirmedCall . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $insightsData['data'][0]['spend'] . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $insightsData['data'][0]['date_start'] . '</td>';
            $htmlTable .= '<td style="border: 1px solid #dddddd; padding: 8px;">' . $insightsData['data'][0]['date_stop'] . '</td>';
            $htmlTable .= '</tr>';

            // Add values to totals
            $totals['impressions'] += $impressions;
            $totals['reach'] += $reach;
            $totals['confirmed_calls'] += $confirmedCalls;
            $totals['spend'] += $insightsData['data'][0]['spend'];
        }
    }
} else {
    $htmlTable .= '<tr><td colspan="9" style="border: 1px solid #dddddd; padding: 8px;">No campaigns found or error in fetching data.</td></tr>';
}

$htmlTable .= '</table>';

// Clean the output buffer and discard the buffer content
ob_clean();

// Generate PDF
require 'vendor/autoload.php'; // Include Composer autoload (assuming you have TCPDF library installed via Composer)

use TCPDF as TCPDF;

// Create PDF document
$pdf = new TCPDF('L', 'mm', 'A4'); // Set orientation to landscape
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add HTML content to PDF using writeHTML
$pdf->writeHTML($htmlTable);

// Output the PDF
$pdf->Output('campaign_insights_report.pdf', 'F'); // Save the PDF to a file

// Include PHPMailer for sending email
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create PHPMailer instance
$mail = new PHPMailer(true); // Enable exceptions

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtpout.secureserver.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'connect@velocityventure.co.in';
    $mail->Password = 'billionaire@291100';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // Recipients
    $mail->setFrom('connect@velocityventure.co.in', 'Velocity Venture');
    $mail->addAddress('sualaksharma@gmail.com', 'Sualak Sharma');

    // Attach generated PDF
    $mail->addAttachment('campaign_insights_report.pdf');

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Campaign Insights Report';
    $mail->Body = 'Please find the attached campaign insights report.';

    // Send email
    $mail->send();
    echo 'Email has been sent successfully.';
} catch (Exception $e) {
    echo 'Email could not be sent. Error: ', $mail->ErrorInfo;
}
?>
