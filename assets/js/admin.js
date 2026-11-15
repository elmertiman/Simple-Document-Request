(function($) {
    'use strict';
    
    $(document).ready(function() {
        let mediaUploader;
        
        // Upload PDF button click
        $('.sdr-upload-pdf-btn').on('click', function(e) {
            e.preventDefault();
            
            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Extend the wp.media object
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Select Authenticity PDF',
                button: {
                    text: 'Use this PDF'
                },
                library: {
                    type: 'application/pdf'
                },
                multiple: false
            });
            
            // When a file is selected, grab the URL and set it as the text field's value
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#_sdr_authenticity_pdf').val(attachment.url);
                $('.sdr-remove-pdf-btn').show();
            });
            
            // Open the uploader dialog
            mediaUploader.open();
        });
        
        // Remove PDF button click
        $('.sdr-remove-pdf-btn').on('click', function(e) {
            e.preventDefault();
            $('#_sdr_authenticity_pdf').val('');
            $(this).hide();
        });
    });
    
})(jQuery);

