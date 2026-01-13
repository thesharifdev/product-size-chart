jQuery(document).ready(function($) {
    var file_frame;
    
    $(document).on('click', '.upload_size_chart_button', function(e) {
        e.preventDefault();
        
        if (file_frame) {
            file_frame.open();
            return;
        }
        
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select Size Chart Image',
            button: {
                text: 'Use this image',
            },
            multiple: false
        });
        
        file_frame.on('select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            
            $('#_size_chart_image_id').val(attachment.id);
            $('#size_chart_image_preview').html('<img src="' + attachment.url + '" style="max-width:150px;height:auto;" />');
            $('.remove_size_chart_button').show();
        });
        
        file_frame.open();
    });
    
    $(document).on('click', '.remove_size_chart_button', function(e) {
        e.preventDefault();
        
        $('#_size_chart_image_id').val('');
        $('#size_chart_image_preview').html('');
        $(this).hide();
    });
});