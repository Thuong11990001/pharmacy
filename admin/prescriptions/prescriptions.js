$(function () {
    // Initialize DataTable
    $('#prescriptionsTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "order": [[1, "asc"]], // Sort by prescription code
        "columnDefs": [
            { "orderable": false, "targets": [9] } // Disable sorting for action column
        ]
    });

    // Auto-generate prescription code
    $('#generateCode').click(function() {
        $('#prescription_code').val('');
        $('#prescription_code').attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
    });

    // Format prescription code input
    $('#prescription_code').on('input', function() {
        let value = $(this).val().toUpperCase();
        if (value.startsWith('PRES')) {
            value = 'PRES' + value.substring(4).replace(/[^0-9]/g, '');
        } else {
            value = value.replace(/[^A-Z0-9]/g, '');
        }
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        $(this).val(value);
    });

    $('#add-medicine').click(function() {
        var template = $('#medicine-template').html();
        var medicineCount = $('#prescription-details .prescription-detail-item').length + 1;
        
        // Replace medicine number in template
        template = template.replace('<span class="medicine-number"></span>', medicineCount);
        
        $('#prescription-details').append(template);
        updateMedicineNumbers();
        calculateGrandTotal();
    });

    // Remove medicine functionality
    $(document).on('click', '.remove-detail', function() {
        $(this).closest('.prescription-detail-item').remove();
        updateMedicineNumbers();
        calculateGrandTotal();
    });

    // Update price when medicine is selected
    $(document).on('change', '.medicine-select', function() {
        var selectedOption = $(this).find('option:selected');
        var price = parseFloat(selectedOption.data('price') || 0);
        var available = parseInt(selectedOption.data('available') || 0);
        
        var detailItem = $(this).closest('.prescription-detail-item');
        detailItem.find('.unit-price').val(price);
        
        // Update quantity limit
        var quantityInput = detailItem.find('.quantity-prescribed');
        quantityInput.attr('max', available);
        
        // Clear previous validation
        quantityInput.removeClass('is-invalid');
        quantityInput.siblings('.invalid-feedback').remove();
        
        // Show warning if quantity exceeds available
        if (parseInt(quantityInput.val()) > available && quantityInput.val() !== '') {
            quantityInput.addClass('is-invalid');
            quantityInput.after('<div class="invalid-feedback">Số lượng vượt quá tồn kho (' + available + ' ' + selectedOption.data('unit') + ')</div>');
        }
        
        calculateRowTotal(detailItem);
        calculateGrandTotal();
    });

    // Update total when quantity changes
    $(document).on('input', '.quantity-prescribed', function() {
        var detailItem = $(this).closest('.prescription-detail-item');
        var medicineSelect = detailItem.find('.medicine-select');
        var selectedOption = medicineSelect.find('option:selected');
        var available = parseInt(selectedOption.data('available') || 0);
        var quantity = parseInt($(this).val());
        
        // Clear previous validation
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
        
        // Only validate if medicine is selected and quantity is entered
        if (medicineSelect.val() !== '' && !isNaN(quantity) && quantity > available) {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">Số lượng vượt quá tồn kho (' + available + ' ' + selectedOption.data('unit') + ')</div>');
        }
        
        calculateRowTotal(detailItem);
        calculateGrandTotal();
    });

    // Calculate row total
    function calculateRowTotal(detailItem) {
        var quantity = parseInt(detailItem.find('.quantity-prescribed').val()) || 0;
        var unitPrice = parseFloat(detailItem.find('.unit-price').val()) || 0;
        var total = quantity * unitPrice;
        
        detailItem.find('.total-price').val(formatNumber(total));
    }

    // Calculate grand total
    function calculateGrandTotal() {
        var grandTotal = 0;
        
        $('.prescription-detail-item').each(function() {
            var quantity = parseInt($(this).find('.quantity-prescribed').val()) || 0;
            var unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
            grandTotal += quantity * unitPrice;
        });
        
        $('#grand-total').text(formatNumber(grandTotal) + ' VNĐ');
    }

    // Update medicine numbers
    function updateMedicineNumbers() {
        $('#prescription-details .prescription-detail-item').each(function(index) {
            $(this).find('.medicine-number, .card-title').html('Thuốc ' + (index + 1));
        });
    }

    // Format number with thousand separators
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Initialize calculations for existing items
    $('.prescription-detail-item').each(function() {
        calculateRowTotal($(this));
    });
    calculateGrandTotal();

    // Form validation - SỬA LỖI CHÍNH TẠI ĐÂY
    $('#prescriptionForm').submit(function(e) {
        let isValid = true;
        let errors = [];

        // Clear all previous validation states first
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Validate patient
        if ($('#patient_id').val() === '') {
            errors.push('Vui lòng chọn bệnh nhân');
            $('#patient_id').addClass('is-invalid');
            isValid = false;
        }

        // Validate doctor name
        if ($('#doctor_name').val().trim() === '') {
            errors.push('Tên bác sĩ không được để trống');
            $('#doctor_name').addClass('is-invalid');
            isValid = false;
        }

        // Validate prescription date
        if ($('#prescription_date').val() === '') {
            errors.push('Ngày kê đơn không được để trống');
            $('#prescription_date').addClass('is-invalid');
            isValid = false;
        }

        // Validate prescription code format if provided
        let prescriptionCode = $('#prescription_code').val().trim();
        if (prescriptionCode !== '' && !/^PRES\d{6}$/.test(prescriptionCode)) {
            errors.push('Mã đơn thuốc phải có định dạng PRES + 6 chữ số (VD: PRES000001)');
            $('#prescription_code').addClass('is-invalid');
            isValid = false;
        }

        // Validate medicine count
        var medicineCount = $('#prescription-details .prescription-detail-item').length;
        if (medicineCount === 0) {
            errors.push('Vui lòng thêm ít nhất một loại thuốc vào đơn');
            isValid = false;
        }
        
        // Validate each medicine detail - CHỈ VALIDATE CÁC FIELD VISIBLE
        $('#prescription-details .prescription-detail-item').each(function(index) {
            var $item = $(this);
            
            // Skip validation for hidden items
            if ($item.is(':hidden')) {
                return true; // continue to next iteration
            }
            
            var medicineSelect = $item.find('.medicine-select');
            var quantityInput = $item.find('.quantity-prescribed');
            
            // Validate medicine selection
            if (medicineSelect.val() === '') {
                errors.push('Vui lòng chọn thuốc cho mục ' + (index + 1));
                medicineSelect.addClass('is-invalid');
                isValid = false;
            }
            
            // Validate quantity
            var quantity = parseInt(quantityInput.val());
            if (quantityInput.val() === '' || isNaN(quantity) || quantity <= 0) {
                errors.push('Vui lòng nhập số lượng hợp lệ cho thuốc ' + (index + 1));
                quantityInput.addClass('is-invalid');
                isValid = false;
            } else if (medicineSelect.val() !== '') {
                // Check if quantity exceeds available stock (only if medicine is selected)
                var available = parseInt(medicineSelect.find('option:selected').data('available') || 0);
                if (quantity > available) {
                    errors.push('Số lượng thuốc ' + (index + 1) + ' vượt quá tồn kho (' + available + ')');
                    quantityInput.addClass('is-invalid');
                    isValid = false;
                }
            }
        });

        if (!isValid) {
            e.preventDefault();
            
            // Focus on first invalid field that is visible and focusable
            var firstInvalidField = $('.is-invalid').filter(':visible, :not([disabled])').first();
            if (firstInvalidField.length > 0) {
                // Scroll to the field first
                $('html, body').animate({
                    scrollTop: firstInvalidField.offset().top - 100
                }, 500, function() {
                    // Then focus after scroll animation
                    try {
                        firstInvalidField.focus();
                    } catch(e) {
                        console.log('Cannot focus on field:', e);
                    }
                });
            }
            
            // Show error message
            let errorMessage = 'Vui lòng kiểm tra lại thông tin:\n• ' + errors.join('\n• ');
            
            // Use a more user-friendly notification instead of alert
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage);
            } else {
                alert(errorMessage);
            }
            
            return false;
        }
        
        // If validation passes, show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        return true;
    });

    // Add at least one medicine row by default for new prescriptions
    if ($('#prescription-details .prescription-detail-item').length === 0 && !$('input[name="prescription_id"]').val()) {
        $('#add-medicine').click();
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Tooltip initialization
    $('[title]').tooltip();

    // Fix for Bootstrap validation classes
    $(document).on('focus', '.form-control', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    });
    
    // Handle form reset
    $('#prescriptionForm').on('reset', function() {
        setTimeout(function() {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            calculateGrandTotal();
        }, 100);
    });
});