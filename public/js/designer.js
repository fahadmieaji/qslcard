$(document).ready(function() {
    const canvasWrapper = $('.canvas-wrapper');
    
    // --- Canvas Initialization ---
    const canvas = new fabric.Canvas('qsl-canvas', {
        // Initially dimensionless, will be sized by image
        width: 0,
        height: 0,
    });
    
    // --- Global State ---
    let logicalWidth = 0;
    let logicalHeight = 0;
    let currentBackgroundImageURL = '';
    const textControls = $('#text-controls');
    const fontSizeInput = $('#font-size');
    const fontColorInput = $('#font-color');
    const removeObjectButton = $('#remove-object');

    // Initialize remove button as disabled
    removeObjectButton.prop('disabled', true);

    // --- Core Responsive Logic ---
    function fitCanvasToContainer() {
        if (logicalWidth === 0 || logicalHeight === 0) return;

        const containerWidth = canvasWrapper.width();
        const scale = containerWidth / logicalWidth;

        canvas.setZoom(scale);
        canvas.setWidth(logicalWidth * scale);
        canvas.setHeight(logicalHeight * scale);
        canvas.renderAll();
    }
    
    // Debounced version of the resize function
    const debouncedFitCanvas = _.debounce(fitCanvasToContainer, 150);
    $(window).on('resize', debouncedFitCanvas);

    // --- Data and Image Loading ---
    function loadBackground(imageUrl, relativeFields = null) {
        let img = new Image();
        img.onload = function() {
            canvas.clear();
            logicalWidth = this.width;
            logicalHeight = this.height;

            fabric.Image.fromURL(imageUrl, function(fabricImg) {
                canvas.setBackgroundImage(fabricImg, function() {
                    // After background is set, THEN fit and add objects
                    fitCanvasToContainer();
                    if (relativeFields) {
                        relativeFields.forEach(field => {
                            const newText = new fabric.IText(field.text, {
                                left: field.left * logicalWidth,
                                top: field.top * logicalHeight,
                                fontSize: field.fontSize * logicalHeight,
                                fill: field.fill,
                                fontFamily: field.fontFamily,
                                angle: field.angle,
                                qsoField: field.qsoField 
                            });
                            canvas.add(newText);
                        });
                    }
                    canvas.renderAll();
                }, {
                    scaleX: 1, scaleY: 1
                });
            });
        };
        img.onerror = function() { alert("Could not load the background image."); };
        img.src = imageUrl;
    }

    // --- Initial Load (Edit Mode) ---
    if (typeof templateToLoad !== 'undefined' && templateToLoad) {
        $('#template-name').val(templateToLoad.template_name);
        currentBackgroundImageURL = templateToLoad.background_image;
        const relativeFields = JSON.parse(templateToLoad.fields);
        loadBackground(templateToLoad.background_image, relativeFields);
    }

    // --- Event Listeners ---
    $('#background-upload').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('background_image', file);

        $.ajax({
            url: 'ajax_upload_bg.php', type: 'POST', data: formData,
            processData: false, contentType: false,
            success: function(response) {
                if (response.success) {
                    currentBackgroundImageURL = response.url;
                    loadBackground(response.url);
                } else { alert('Error: ' + response.message); }
            },
            error: function(jqXHR) { alert('AJAX Error: ' + (jqXHR.responseJSON?.message || 'Upload failed')); }
        });
    });

    canvas.on({
        'selection:created': updateControls,
        'selection:updated': updateControls,
        'selection:cleared': hideControls
    });

    function updateControls(e) {
        if (e.target && e.target.type === 'i-text') {
            const fontSize = e.target.get('fontSize');
            // Recalculate font size based on logical height for display
            const relativeFontSize = fontSize / logicalHeight;
            
            // textControls.removeClass('d-none'); // No longer hiding/showing the whole panel
            fontSizeInput.val(Math.round(fontSize)); 
            fontColorInput.val(e.target.get('fill'));
            removeObjectButton.prop('disabled', false); // Enable remove button
        } else {
            hideControls();
        }
    }

    function hideControls() {
        // textControls.addClass('d-none'); // No longer hiding/showing the whole panel
        removeObjectButton.prop('disabled', true); // Disable remove button
    }
    
    $('.add-text').on('click', function() {
        if(logicalWidth === 0) {
            alert('Please upload a background image first.');
            return;
        }
        const fieldName = $(this).data('text');
        const fontSize = logicalHeight * 0.04; // Default to 4% of image height
        const newText = new fabric.IText(fieldName, {
            left: 50, top: 50, fontSize: fontSize,
            fill: '#000000', fontFamily: 'Arial', qsoField: fieldName,
            selectable: true, // Ensure the object can be selected
            evented: true     // Ensure the object can receive events (like dragging)
        });
        canvas.add(newText);
        canvas.setActiveObject(newText);
    });

    fontSizeInput.on('input', function() {
        const activeObject = canvas.getActiveObject();
        if (activeObject && activeObject.type === 'i-text') {
            activeObject.set('fontSize', parseInt($(this).val(), 10));
            canvas.renderAll();
        }
    });

    fontColorInput.on('input', function() {
        const activeObject = canvas.getActiveObject();
        if (activeObject && activeObject.type === 'i-text') {
            activeObject.set('fill', $(this).val());
            canvas.renderAll();
        }
    });

    $('#remove-object').on('click', function() {
        const activeObject = canvas.getActiveObject();
        if (activeObject) { 
            canvas.remove(activeObject); 
            canvas.discardActiveObject(); // Clear selection after removing
            hideControls(); 
        }
    });

    // --- Save Logic ---
    $('#save-template').on('click', function() {
        const templateName = $('#template-name').val();
        if (!templateName) { alert('Please enter a template name.'); return; }
        if (!currentBackgroundImageURL) { alert('Please upload a background image.'); return; }

        const objectsToSave = [];
        canvas.getObjects().forEach(obj => {
            if (obj.type === 'i-text') {
                objectsToSave.push({
                    text: obj.text,
                    left: obj.left / logicalWidth,
                    top: obj.top / logicalHeight,
                    fontSize: obj.fontSize / logicalHeight, // Save as ratio of height
                    fill: obj.fill, fontFamily: obj.fontFamily,
                    angle: obj.angle, qsoField: obj.qsoField
                });
            }
        });

        if (objectsToSave.length === 0) { alert('Please add at least one QSO field.'); return; }

        const ajaxData = {
            template_id: $('#template-id').val(),
            template_name: templateName,
            background_image: currentBackgroundImageURL,
            fields: JSON.stringify(objectsToSave)
        };

        $.ajax({
            url: 'ajax_save_template.php', type: 'POST', data: ajaxData,
            success: function(response) {
                if (response.success) {
                    alert('Template saved successfully!');
                    window.location.href = 'templates.php'; 
                } else { alert('Error: ' + response.message); }
            },
            error: function(jqXHR) { alert('AJAX Error: ' + (jqXHR.responseJSON?.message || 'Save failed')); }
        });
    });
});
