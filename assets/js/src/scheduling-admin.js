(function($) {
    'use strict';

    let scheduleIndex = $('.pum-schedule-row').length;

    // Add new schedule
    $(document).on('click', '.pum-add-schedule', function(e) {
        e.preventDefault();
        
        const template = $('#pum-schedule-template').html();
        const newSchedule = template.replace(/\{\{INDEX\}\}/g, scheduleIndex);
        
        $('.pum-schedules-list').append(newSchedule);
        scheduleIndex++;
        
        updateScheduleFields();
    });

    // Remove schedule
    $(document).on('click', '.pum-remove-schedule', function(e) {
        e.preventDefault();
        $(this).closest('.pum-schedule-row').remove();
    });

    // Update visible fields based on schedule type
    $(document).on('change', '.pum-schedule-type', function() {
        updateScheduleFields();
    });

    function updateScheduleFields() {
        $('.pum-schedule-row').each(function() {
            const $row = $(this);
            const type = $row.find('.pum-schedule-type').val();
            
            // Hide all fields first
            $row.find('.pum-schedule-field').hide();
            
            // Show relevant fields based on type
            switch(type) {
                case 'start_date':
                    $row.find('.start-date-field, .start-time-field').show();
                    break;
                
                case 'end_date':
                    $row.find('.end-date-field, .end-time-field').show();
                    break;
                
                case 'date_range':
                    $row.find('.start-date-field, .start-time-field, .end-date-field, .end-time-field').show();
                    break;
                
                case 'chosen_dates':
                    // Implement chosen dates UI
                    break;
                
                case 'office_hours':
                    $row.find('.office-hours-field, .start-time-field, .end-time-field').show();
                    break;
            }
        });
    }

    // Initialize on page load
    updateScheduleFields();

})(jQuery);