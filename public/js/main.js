// public/js/main.js

function printImageOnly(image_url) {
    let printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print QSL Card</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #fff; }');
    printWindow.document.write('img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; margin: auto; }');
    printWindow.document.write('@page { margin: 0; }'); // This targets page margins in print
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body onload="window.print();">');
    printWindow.document.write('<img src="' + image_url + '">');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
}



$(document).ready(function() {
    console.log("QSL Manager JS loaded.");

    // Add the S21AF credit when the document is ready
    addS21AFCredit();

    // Set up a periodic check (e.g., every 5 seconds)
    // IMPORTANT: This is for demonstration and is NOT secure.
    // A user can easily bypass this by disabling JavaScript or modifying it
    // in their browser's developer tools.
    setInterval(checkS21AFCreditJS, 5000);


    // Event listener for the print button
    $(document).on('click', '.print-qsl-btn', function() {
        const imageUrl = $(this).data('image-url');
        if (imageUrl) {
            printImageOnly(imageUrl);
        } else {
            console.error('Image URL not found for printing.');
        }
    });

    let templatesCache = null;

    // Handle the dynamic QSL template dropdown in the logbook
    // We use event delegation on the document because the dropdowns are dynamically loaded
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
                error: function() {
                    dropdownMenu.find('.dropdown-item-text').text('AJAX Error').addClass('text-danger');
                }
            });
        }
    });

    // Handle Profile Update Form Submission
    $('#profile-update-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const form = $(this);
        const messageDiv = $('#profile-update-message');
        messageDiv.empty(); // Clear previous messages

        $.ajax({
            url: 'ajax_update_profile.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                    // Optionally update username in navbar immediately
                    $('.navbar-nav #navbarUserDropdown').text($('#username').val());
                } else {
                    messageDiv.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                messageDiv.html('<div class="alert alert-danger">An error occurred: ' + (jqXHR.responseJSON?.message || textStatus) + '</div>');
            }
        });
    });

    // Handle Profile Picture Form Submission
    $('#profile-picture-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const form = $(this);
        const messageDiv = $('#profile-picture-message');
        messageDiv.empty(); // Clear previous messages

        const formData = new FormData(this); // Use FormData for file uploads

        $.ajax({
            url: 'ajax_upload_profile_picture.php',
            type: 'POST',
            data: formData,
            processData: false, // Don't process the files
            contentType: false, // Set content type to false as jQuery will tell the server its a multipart request
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                    // Update the image preview
                    $('#profile-picture-preview').attr('src', response.file_url);
                } else {
                    messageDiv.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                messageDiv.html('<div class="alert alert-danger">An error occurred: ' + (jqXHR.responseJSON?.message || textStatus) + '</div>');
            }
        });
    });

    // Handle Password Reset Form Submission
    $('#password-reset-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const form = $(this);
        const messageDiv = $('#password-reset-message');
        messageDiv.empty(); // Clear previous messages

        $.ajax({
            url: 'ajax_reset_password.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                    form[0].reset(); // Clear the form fields
                } else {
                    messageDiv.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                messageDiv.html('<div class="alert alert-danger">An error occurred: ' + (jqXHR.responseJSON?.message || textStatus) + '</div>');
            }
        });
    });
});
