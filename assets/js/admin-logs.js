/**
 * Inecobank Admin Logs - Postbox collapse/expand functionality
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Custom toggle behavior for toggle button and title
        // Using event delegation for better reliability
        $(document).on('click', '.postbox .handlediv, .postbox .hndle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $postbox = $(this).closest('.postbox');
            
            // Toggle closed class
            $postbox.toggleClass('closed');
            
            // Update aria-expanded attribute on the button
            var isExpanded = !$postbox.hasClass('closed');
            $postbox.find('.handlediv').attr('aria-expanded', isExpanded);
            
            return false;
        });

        // Expand/collapse all functionality
        var $expandAllBtn = $('<button type="button" class="button expand-all" style="margin-left: 10px;">Expand All</button>');
        var $collapseAllBtn = $('<button type="button" class="button collapse-all" style="margin-left: 5px;">Collapse All</button>');
        
        $('.inecobank-logs-header form').append($expandAllBtn).append($collapseAllBtn);
        
        $expandAllBtn.on('click', function(e) {
            e.preventDefault();
            $('.postbox').removeClass('closed').find('.handlediv').attr('aria-expanded', 'true');
        });
        
        $collapseAllBtn.on('click', function(e) {
            e.preventDefault();
            $('.postbox').addClass('closed').find('.handlediv').attr('aria-expanded', 'false');
        });
    });

})(jQuery);
