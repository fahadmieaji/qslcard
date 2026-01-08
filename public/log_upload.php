<?php
// public/log_upload.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';
require_once ROOT_PATH . '/src/adif.php';

secure_session_start();
require_login();

$pageTitle = 'Upload ADIF Log';
$errors = [];
$success_count = 0;
$skipped_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['adif_file']) && $_FILES['adif_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['adif_file']['tmp_name'];
        
        try {
            $adif_parser = new adif(file_get_contents($file_tmp_path));
            $records = $adif_parser->parser();
            
            if (empty($records)) {
                $errors[] = 'Could not parse any records from the file. Please check the file format.';
            } else {
                $pdo = get_db_connection();
                // Use INSERT IGNORE to automatically skip duplicates based on the UNIQUE KEY
                $stmt = $pdo->prepare(
                    'INSERT IGNORE INTO logs (user_id, qso_date, time_on, `call`, band, freq, mode, rst_sent, rst_rcvd, qsl_sent, qsl_rcvd, source) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                $total_records = count($records);
                foreach ($records as $record) {
                    $call = $record['OPERATOR'] ?? $record['CALL'] ?? null;
                    if (empty($call) || empty($record['QSO_DATE']) || empty($record['TIME_ON'])) {
                        continue;
                    }
                    
                    $band = $record['BAND'] ?? null;
                    if (empty($band) && !empty($record['FREQ'])) {
                        $band = get_band_from_frequency($record['FREQ']);
                    }

                    $stmt->execute([
                        $_SESSION['user_id'],
                        date('Y-m-d', strtotime($record['QSO_DATE'])),
                        date('H:i:s', strtotime($record['TIME_ON'])),
                        $call,
                        $band,
                        $record['FREQ'] ?? null,
                        $record['MODE'] ?? null,
                        $record['RST_SENT'] ?? null,
                        $record['RST_RCVD'] ?? null,
                        $record['QSL_SENT'] ?? 'N',
                        $record['QSL_RCVD'] ?? 'N',
                        'manual' // Set the source
                    ]);

                    if ($stmt->rowCount() > 0) {
                        $success_count++;
                    }
                }
                $skipped_count = $total_records - $success_count;
            }
        } catch (Exception $e) {
            $errors[] = 'Error parsing file: ' . $e->getMessage();
        }
    } else {
        $errors[] = 'File upload failed. Please try again.';
    }
}

include_once ROOT_PATH . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">Import ADIF Log File</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error) echo "<p class='mb-0'>" . htmlspecialchars($error) . "</p>"; ?>
                    </div>
                <?php endif; ?>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading">Import Complete!</h5>
                        <p>Successfully imported <?php echo $success_count; ?> new QSO records.</p>
                        <?php if ($skipped_count > 0): ?>
                            <p class="mb-0">Skipped <?php echo $skipped_count; ?> duplicate records.</p>
                        <?php endif; ?>
                        <hr>
                        <a href="logbook.php" class="btn btn-primary">Go to Logbook</a>
                    </div>
                <?php else: ?>
                    <p class="card-text">Select an ADIF file (.adi or .adif) to import your QSOs. Duplicates will be automatically skipped.</p>
                    <form action="log_upload.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="adif_file" class="form-label">ADIF File</label>
                            <input class="form-control" type="file" id="adif_file" name="adif_file" accept=".adi,.adif" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload and Import</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>
