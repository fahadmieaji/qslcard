<?php
// public/logbook.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

$pageTitle = 'My Unified Logbook';
$pdo = get_db_connection();

// Pagination
$limit = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search_call = $_GET['search_call'] ?? '';
$base_query = 'FROM logs WHERE user_id = :user_id';
$params = [':user_id' => $_SESSION['user_id']];
if (!empty($search_call)) {
    $base_query .= ' AND `call` LIKE :search_call';
    $params[':search_call'] = "%$search_call%";
}

// Get total record count
$total_stmt = $pdo->prepare("SELECT COUNT(id) AS total $base_query");
$total_stmt->execute($params);
$total_records = $total_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch records for current page
$logs_stmt = $pdo->prepare("SELECT * $base_query ORDER BY qso_date DESC, time_on DESC LIMIT :limit OFFSET :offset");
$logs_stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$logs_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => &$val) { $logs_stmt->bindParam($key, $val); }
$logs_stmt->execute();
$logs = $logs_stmt->fetchAll();

include_once ROOT_PATH . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Unified Logbook</h1>
    <div class="btn-group">
        <a href="log_upload.php" class="btn btn-secondary">Upload ADIF</a>
        <a href="lotw_integration.php" class="btn btn-success">Sync with LoTW</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="logbook.php" method="GET" class="row g-3 align-items-center">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search_call" placeholder="Search by Callsign..." value="<?php echo htmlspecialchars($search_call); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Callsign</th>
                        <th>Band</th>
                        <th>Freq</th>
                        <th>Mode</th>
                        <th>Source</th>
                        <th>QSL Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['qso_date']); ?></td>
                                <td><?php echo htmlspecialchars($log['time_on']); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['call']); ?></strong></td>
                                <td><?php echo htmlspecialchars($log['band']); ?></td>
                                <td><?php echo htmlspecialchars($log['freq']); ?></td>
                                <td><?php echo htmlspecialchars($log['mode']); ?></td>
                                <td>
                                    <?php if ($log['source'] === 'lotw'): ?>
                                        <span class="badge bg-success" title="Logbook of the World">LoTW</span>
                                    <?php else: ?>
                                        <span class="badge bg-info" title="Manual Upload">Manual</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle generate-qsl-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-log-id="<?php echo $log['id']; ?>">
                                                QSL
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><span class="dropdown-item-text">Loading...</span></li>
                                            </ul>
                                        </div>
                                        <button class="btn btn-sm btn-danger delete-log-btn" data-log-id="<?php echo $log['id']; ?>">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <!-- Previous Page Link -->
        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search_call=<?php echo urlencode($search_call); ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);

        if ($start_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1&search_call=' . urlencode($search_call) . '">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search_call=<?php echo urlencode($search_call); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor;

        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search_call=' . urlencode($search_call) . '">' . $total_pages . '</a></li>';
        }
        ?>

        <!-- Next Page Link -->
        <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search_call=<?php echo urlencode($search_call); ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include_once ROOT_PATH . '/templates/footer.php'; ?>

<script>
$(document).ready(function() {
    let templatesCache = null;

    // Handle the dynamic QSL template dropdown in the logbook
    $(document).on('show.bs.dropdown', '.dropdown', function () {
        const dropdownButton = $(this).find('.generate-qsl-dropdown');
        if (dropdownButton.length === 0) return; // Not the QSL dropdown

        const logId = dropdownButton.data('log-id');
        const dropdownMenu = $(this).find('.dropdown-menu');

        const populateDropdown = (templates) => {
            dropdownMenu.empty();
            if (templates.length === 0) {
                dropdownMenu.append('<li><a class="dropdown-item" href="designer.php">Create a template first!</a></li>');
            } else {
                templates.forEach(template => {
                    const link = `generate.php?log_id=${logId}&template_id=${template.id}`;
                    dropdownMenu.append(`<li><a class="dropdown-item" href="${link}">${template.template_name}</a></li>`);
                });
            }
        };

        if (templatesCache) {
            populateDropdown(templatesCache);
        } else {
            $.ajax({
                url: 'ajax_get_templates.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        templatesCache = response.templates;
                        populateDropdown(templatesCache);
                    } else {
                        dropdownMenu.find('.dropdown-item-text').text('Error loading templates').addClass('text-danger');
                    }
                },
                error: function(jqXHR) {
                    const errorMessage = jqXHR.responseJSON ? jqXHR.responseJSON.message : 'AJAX Error';
                    dropdownMenu.find('.dropdown-item-text').text(errorMessage).addClass('text-danger');
                }
            });
        }
    });
});
</script>
