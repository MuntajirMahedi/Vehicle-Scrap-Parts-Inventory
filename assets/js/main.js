$(document).ready(function() {
    // Login form handling
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = response.redirect;
                } else {
                    alert(response.message || 'An error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize datepicker if exists
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    }

    // File input preview
    $('input[type="file"]').on('change', function() {
        const file = this.files[0];
        const fileReader = new FileReader();
        const preview = $(this).siblings('.image-preview');

        if (preview.length && file) {
            fileReader.onload = function() {
                preview.attr('src', fileReader.result);
            }
            fileReader.readAsDataURL(file);
        }
    });

    // Delete confirmation
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Status toggle
    $('.status-toggle').on('change', function() {
        const itemId = $(this).data('id');
        const itemType = $(this).data('type');
        const status = $(this).prop('checked') ? 'active' : 'inactive';

        $.ajax({
            type: 'POST',
            url: 'ajax/update_status.php',
            data: {
                id: itemId,
                type: itemType,
                status: status
            },
            success: function(response) {
                if (response.status !== 'success') {
                    alert('Failed to update status');
                }
            }
        });
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#dataTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Form validation
    $('form').on('submit', function() {
        const requiredFields = $(this).find('[required]');
        let isValid = true;

        requiredFields.each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        return isValid;
    });
});
