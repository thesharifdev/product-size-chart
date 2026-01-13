jQuery(document).ready(function($) {
    // Create modal HTML
    if ($('#size-chart-modal').length === 0) {
        $('body').append('<div id="size-chart-modal" class="size-chart-modal"><div class="size-chart-modal-content"><span class="size-chart-close">&times;</span><img class="size-chart-image" src="" alt="Size Chart" /></div></div>');
    }
    
    // Handle button click
    $(document).on('click', '.size-chart-button', function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        
        $.ajax({
            url: sizeChartData.ajax_url,
            type: 'POST',
            data: {
                action: 'get_size_chart',
                product_id: productId,
                nonce: sizeChartData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#size-chart-modal .size-chart-image').attr('src', response.data.image_url);
                    $('#size-chart-modal').fadeIn();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Close modal
    $(document).on('click', '.size-chart-close, #size-chart-modal', function(e) {
        if (e.target === this) {
            $('#size-chart-modal').fadeOut();
        }
    });
    
    // Close on ESC key
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('#size-chart-modal').fadeOut();
        }
    });
});