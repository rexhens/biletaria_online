<?php

use JetBrains\PhpStorm\NoReturn;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require '../assets/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
function sendEmail(string $email, string $subject, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();

        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPAuth = true;
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];

        $mail->setFrom($_ENV['SMTP_USER'], "Teatri Metropol");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $template = $body;

        $mail->Body = $template;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function checkAdmin($conn): bool {

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();

    if ($role !== 'admin') {
        return false;
    }

    return true;
}

function redirectIfNotLoggedIn(): void {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: ../auth/login.php");
        exit;
    }
}

function redirectIfNotAdmin($conn): void {
    if(!checkAdmin($conn)) {
        header("Location: ../auth/no-access.php");
        exit;
    }
}

function groupDates($dates): array {
    if (empty($dates)) return [];

    $grouped = [];
    $start = $end = new DateTime($dates[0]);

    for ($i = 1; $i < count($dates); $i++) {
        $current = new DateTime($dates[$i]);
        $diff = (int)$end->diff($current)->format("%a");

        if ($diff === 1) {
            $end = $current;
        } else {
            $grouped[] = formatDateRange($start, $end);
            $start = $end = $current;
        }
    }

    $grouped[] = formatDateRange($start, $end);
    return $grouped;
}

function formatDateRange($start, $end): string {
    $muajiStart = muajiNeShqip($start->format('M'));
    $muajiEnd = muajiNeShqip($end->format('M'));

    if ($start == $end) {
        return $start->format('j') . " " . $muajiStart;
    } elseif ($muajiStart === $muajiEnd) {
        return $start->format('j') . "-" . $end->format('j') . " " . $muajiStart;
    } else {
        return $start->format('j') . " " . $muajiStart . " - " . $end->format('j') . " " . $muajiEnd;
    }
}

function muajiNeShqip($muajiAnglisht): string {
    $muajt = [
        'Jan' => 'Janar', 'Feb' => 'Shkurt', 'Mar' => 'Mars',
        'Apr' => 'Prill', 'May' => 'Maj', 'Jun' => 'Qershor',
        'Jul' => 'Korrik', 'Aug' => 'Gusht', 'Sep' => 'Shtator',
        'Oct' => 'Tetor', 'Nov' => 'Nëntor', 'Dec' => 'Dhjetor'
    ];
    return $muajt[$muajiAnglisht] ?? $muajiAnglisht;
}

function showError($error): void {
    echo "<!DOCTYPE html>
      <html lang='sq'>
      <head>";
    require '../includes/links.php';
    echo "<title>Teatri Metropol | Mesazh</title>
      <link rel='icon' type='image/x-icon' href='../assets/img/metropol_icon.png'>
      <link rel='stylesheet' href='../assets/css/styles.css'>
      <style>
          body {
            background: url('../assets/img/error.png') no-repeat center center fixed;
            background-size: cover;
            justify-content: center;
          }
      </style>
      </head>
      <body>
      <div class='errors show'>
            <p>$error</p>
      </div>
      </body>
      </html>";
    exit;
}