<?php
/** @var mysqli $conn */
require $_SERVER['DOCUMENT_ROOT'] . '/biletaria_online/config/db_connect.php';
require $_SERVER['DOCUMENT_ROOT'] . '/biletaria_online/auth/auth.php';
require $_SERVER['DOCUMENT_ROOT'] . '/biletaria_online/includes/functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-event'])) {
    $id = $_POST['eventId'];

    $conn->begin_transaction();

    try {
        $sql = "DELETE FROM event_dates WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Nuk u fshinë datat e eventit!");
        }
        $stmt->close();

        if (!deletePoster($conn, "events", $id)) {
            throw new Exception("Posteri nuk u fshi!");
        }

        $sql = "DELETE FROM events WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Eventi nuk u fshi!");
        }
        $stmt->close();

        $conn->commit();

        header('Location: index.php?update=success');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Një problem ndodhi! " . $e->getMessage();
        $encodedMessage = urlencode($message);
        header('Location: index.php?update=error&message=' . $encodedMessage);
        exit();
    }

} else {
    $message = "Nuk ka informacion mbi të dhënat që duhen fshirë!";
    $encodedMessage = urlencode($message);
    header('Location: index.php?update=error&message=' . $encodedMessage);
    exit();
}