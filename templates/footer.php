<?php
// templates/footer.php
?>
</main>

<footer class="mt-auto py-4 bg-light border-top">
    <div class="container text-center">
        <p class="text-muted mb-1">&copy; <?php echo date('Y'); ?> QSL Card Manager. All rights reserved.</p>
        <p class="text-muted mb-0">Developed by <a href="https://facebook.com/S21AF" target="_blank" class="text-decoration-none text-primary fw-bold">S21AF</a></p>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Lodash (for debouncing) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>
<!-- Fabric.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo ROOT_URL; ?>/public/js/main.js"></script>

<script>
$(document).ready(function() {
    $('#public-search-form').on('submit', function(e) {
        e.preventDefault();
        const callsign = $('input[name="callsign"]').val();
        const resultsContainer = $('#search-results-container');
        resultsContainer.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        $.ajax({
            url: 'ajax_public_search.php',
            type: 'GET',
            data: { callsign: callsign },
            dataType: 'json',
            success: function(response) {
                resultsContainer.empty();
                if (response.success) {
                    let tableHtml = `
                        <h2 class="mt-4 mb-3">QSO Records for ${callsign}</h2>
                        <div class="alert alert-info">
                            Select the QSO records for which you want to generate QSL cards, then click "Generate & Download Selected QSL Cards" to get them.
                        </div>
                        <form id="qsl-generation-form" action="generate_public_qsl.php" method="POST" target="_blank">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all-qso"></th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Call</th>
                                            <th>Band</th>
                                            <th>Mode</th>
                                            <th>Logged by</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                    response.results.forEach(qso => {
                        tableHtml += `
                            <tr>
                                <td><input type="checkbox" name="selected_qso[]" value="${qso.id}"></td>
                                <td>${qso.qso_date}</td>
                                <td>${qso.time_on}</td>
                                <td>${qso.call}</td>
                                <td>${qso.band}</td>
                                <td>${qso.mode}</td>
                                <td><a href="view_profile.php?id=${qso.user_id}">${qso.username}</a></td>
                            </tr>`;
                    });
                    tableHtml += `
                                    </tbody>
                                </table>
                            </div>`;
                    if (response.has_public_template) {
                        tableHtml += `<button type="submit" class="btn btn-success mt-3" id="generate-selected-qsl-btn">Generate & Download Selected QSL Cards</button>`;
                    }
                    tableHtml += `</form>`;
                    resultsContainer.html(tableHtml);
                } else {
                    resultsContainer.html(`<div class="alert alert-info mt-4">${response.message}</div>`);
                }
            },
            error: function() {
                resultsContainer.html('<div class="alert alert-danger mt-4">An error occurred while searching.</div>');
            }
        });
    });

    $(document).on('submit', '#qsl-generation-form', function(e) {
        const selectedQSOIds = [];
        $('input[name="selected_qso[]"]:checked').each(function() {
            selectedQSOIds.push($(this).val());
        });

        if (selectedQSOIds.length > 0) {
            // Remove any previously added hidden input
            $(this).find('input[name="log_ids"]').remove();
            
            // Add a hidden input to send the selected log IDs as a JSON string
            const hiddenInput = $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'log_ids')
                .val(JSON.stringify(selectedQSOIds));
            $(this).append(hiddenInput);
        } else {
            e.preventDefault();
            alert('Please select at least one QSO record to generate QSL cards.');
        }
    });

    $(document).on('change', '#select-all-qso', function() {
        $('input[name="selected_qso[]"]').prop('checked', $(this).prop('checked'));
    });
});
</script>

<!-- Developed by S21AF -->
</body>
</html>
