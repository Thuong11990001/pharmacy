var medicinesList = [];
$(document).ready(function() {
    // Initialize DataTable
    loadMedicinesList();
    $('#salesTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "ordering": true,
        "info": true,
        "paging": true,
        "searching": true,
        "pageLength": 25,
        "language": {
            "search": "Tìm kiếm:",
            "lengthMenu": "Hiển thị _MENU_ bản ghi",
            "info": "Hiển thị _START_ đến _END_ của _TOTAL_ bản ghi",
            "infoEmpty": "Hiển thị 0 đến 0 của 0 bản ghi",
            "infoFiltered": "(lọc từ _MAX_ tổng số bản ghi)",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "Tiếp",
                "previous": "Trước"
            },
            "emptyTable": "Không có dữ liệu trong bảng",
            "zeroRecords": "Không tìm thấy bản ghi nào khớp"
        }
    });

    // Auto-generate sale code
    $('#generateCode').click(function() {
        $.ajax({
            url: 'generate_sale_code.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#sale_code').val(response.sale_code);
                } else {
                    alert('Không thể tạo mã giao dịch: ' + (response.message || 'Lỗi không xác định'));
                }
            },
            error: function() {
                alert('Lỗi khi tạo mã giao dịch!');
            }
        });
    });
    function loadMedicinesList() {
        $.ajax({
            url: 'get_medicines_list.php', 
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    medicinesList = response.medicines;
                } else {
                    console.error('Error loading medicines list:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading medicines list:', error);
            }
        });
    }

    // Patient selection change - Load prescriptions for selected patient
    $('#patient_id').change(function() {
        var patientId = $(this).val();
        var prescriptionSelect = $('#prescription_id');
        
        // Clear current prescription options
        prescriptionSelect.empty().append('<option value="">-- Chọn đơn thuốc --</option>');
        
        if (patientId) {
            // Show loading
            prescriptionSelect.append('<option value="">Đang tải...</option>');
            
            // Load prescriptions for selected patient
            $.ajax({
                url: 'get_patient_prescriptions.php',
                type: 'GET',
                data: { patient_id: patientId },
                dataType: 'json',
                success: function(response) {
                    // Clear loading option
                    prescriptionSelect.empty().append('<option value="">-- Chọn đơn thuốc --</option>');
                    
                    if (response.length > 0) {
                        $.each(response, function(index, prescription) {
                            prescriptionSelect.append(
                                '<option value="' + prescription.id + '">' + 
                                prescription.prescription_code + 
                                '</option>'
                            );
                        });
                    } else {
                        prescriptionSelect.append('<option value="">Không có đơn thuốc nào</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading prescriptions:', error);
                    prescriptionSelect.empty().append('<option value="">-- Chọn đơn thuốc --</option>');
                    prescriptionSelect.append('<option value="">Lỗi tải dữ liệu</option>');
                }
            });
        }
    });

  


$('#prescription_id').change(function() {
    var prescriptionId = $(this).val();
    
    if (prescriptionId) {
        // Show loading message
        $('#sale_details_container').append('<div id="loading-message" class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Đang tải chi tiết đơn thuốc...</div>');
        
        // Debug: Log prescription ID
        console.log('Loading prescription details for ID:', prescriptionId);
        
        // Load prescription details
        $.ajax({
            url: 'get_prescription_details.php',
            type: 'GET',
            data: { prescription_id: prescriptionId },
            dataType: 'json',
            success: function(response) {
                $('#loading-message').remove();
                
                // Debug: Log response
                console.log('Response received:', response);
                
                if (response.success && response.details.length > 0) {
                    // Confirm before auto-filling
                    if (confirm('Bạn có muốn tự động điền thông tin thuốc từ đơn thuốc không?\n\nLưu ý: Điều này sẽ xóa các dòng chi tiết hiện tại.')) {
                        autoFillPrescriptionDetails(response.details);
                    }
                } else {
                    alert('Không tìm thấy chi tiết đơn thuốc hoặc đơn thuốc trống\nChi tiết: ' + (response.message || 'Không có thông tin'));
                }
            },
            error: function(xhr, status, error) {
                $('#loading-message').remove();
                
                // Debug: Log detailed error info
                console.error('AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                
                // Show detailed error message
                var errorMessage = 'Lỗi khi tải chi tiết đơn thuốc\n';
                errorMessage += 'Status: ' + status + '\n';
                errorMessage += 'Error: ' + error + '\n';
                errorMessage += 'HTTP Status: ' + xhr.status + '\n';
                
                if (xhr.responseText) {
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        errorMessage += 'Server Message: ' + (errorResponse.message || 'Không có thông tin');
                    } catch (e) {
                        errorMessage += 'Response: ' + xhr.responseText.substring(0, 200);
                    }
                }
                
                alert(errorMessage);
            }
        });
    }
});

    // NEW: Function to auto-fill prescription details
  function autoFillPrescriptionDetails(details) {
        var container = $('#sale_details_container');
        
        // Clear existing rows
        container.empty();
        
        // Add rows for each prescription detail
        $.each(details, function(index, detail) {
            var newRow = createDetailRow(index, detail);
            container.append(newRow);
        });
        
        // Calculate totals
        calculateTotals();
        
        // Show success message
        showNotification('success', 'Đã tự động điền ' + details.length + ' loại thuốc từ đơn thuốc');
    }

    // NEW: Create detail row with prescription data
        function createDetailRow(index, detail = null) {
        var medicineOptions = getMedicineOptions();
        var selectedMedicine = detail ? detail.medicine_id : '';
        var quantity = detail ? detail.prescribed_quantity : '';
        var unitPrice = detail ? detail.selling_price : '';
        var costPrice = detail ? detail.cost_price : 0;
        var prescriptionDetailId = detail ? detail.prescription_detail_id : '';
        
        var prescriptionInfo = '';
        if (detail) {
            prescriptionInfo = `
                <div class="alert alert-info alert-sm mb-2">
                    <small>
                        <i class="fas fa-prescription"></i> 
                        <strong>Từ đơn thuốc:</strong> ${detail.medicine_code} - ${detail.medicine_name}<br>
                        <strong>Tồn kho:</strong> ${detail.available_quantity} | 
                        <strong>Liều dùng:</strong> ${detail.dosage || 'Không có'} | 
                        <strong>Tần suất:</strong> ${detail.frequency || 'Không có'}
                    </small>
                </div>
            `;
        }
        
        var row = `
            <div class="sale-detail-row mb-3 p-3 border rounded">
                ${prescriptionInfo}
                <div class="row">
                    <div class="col-md-4">
                        <label>Thuốc *</label>
                        <select class="form-control medicine-select" name="sale_details[${index}][medicine_id]" required>
                            ${medicineOptions}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Số lượng *</label>
                        <input type="number" class="form-control quantity-input" 
                               name="sale_details[${index}][quantity]" min="1" 
                               value="${quantity}" ${detail && detail.available_quantity ? 'max="' + detail.available_quantity + '"' : ''} required>
                        ${detail ? '<small class="text-muted">Đơn thuốc: ' + detail.prescribed_quantity + '</small>' : ''}
                    </div>
                    <div class="col-md-3">
                        <label>Đơn giá *</label>
                        <input type="number" class="form-control unit-price-input" 
                               name="sale_details[${index}][unit_price]" step="0.01" value="${unitPrice}" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-detail mt-4">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="sale_details[${index}][cost_price]" value="${costPrice}">
                <input type="hidden" name="sale_details[${index}][discount_amount]" value="0">
                <input type="hidden" name="sale_details[${index}][total_price]" class="total-price-input" value="0">
                <input type="hidden" name="sale_details[${index}][prescription_detail_id]" value="${prescriptionDetailId}">
            </div>
        `;
        
        var $row = $(row);
        
        // Set selected medicine if provided
        if (selectedMedicine) {
            $row.find('.medicine-select').val(selectedMedicine);
            // Trigger change event để load thông tin thuốc
            setTimeout(function() {
                $row.find('.medicine-select').trigger('change');
            }, 100);
        }
        
        // Calculate initial total
        setTimeout(function() {
            calculateRowTotal($row);
        }, 200);
        
        return $row;
    }

    // NEW: Show notification
    function showNotification(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var iconClass = type === 'success' ? 'fa-check' : 'fa-exclamation-triangle';
        
        var notification = `
            <div class="alert ${alertClass} alert-dismissible auto-dismiss">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas ${iconClass}"></i> ${type === 'success' ? 'Thành công!' : 'Lỗi!'}</h5>
                ${message}
            </div>
        `;
        
        $('.content-fluid').prepend(notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.auto-dismiss').fadeOut();
        }, 5000);
    }

    // Clear customer name when patient is selected
    $('#patient_id').change(function() {
        if ($(this).val()) {
            $('#customer_name').val('').prop('readonly', true);
            $('#customer_phone').val('').prop('readonly', true);
        } else {
            $('#customer_name').prop('readonly', false);
            $('#customer_phone').prop('readonly', false);
        }
    });

    // Clear patient when customer name is entered
    $('#customer_name').on('input', function() {
        if ($(this).val().trim()) {
            $('#patient_id').val('').trigger('change');
        }
    });

    // Medicine selection change - Load price and stock info
    $(document).on('change', '.medicine-select', function() {
        var medicineId = $(this).val();
        var row = $(this).closest('.sale-detail-row');
        var unitPriceInput = row.find('.unit-price-input');
        var quantityInput = row.find('.quantity-input');
        
        if (medicineId) {
            $.ajax({
                url: 'get_medicine_info.php',
                type: 'GET',
                data: { medicine_id: medicineId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Only update price if it's empty (to preserve prescription prices)
                        if (!unitPriceInput.val()) {
                            unitPriceInput.val(response.selling_price || 0);
                        }
                        
                        quantityInput.attr('max', response.available_quantity || 0);
                        
                        // Store cost price for calculation
                        row.find('input[name*="[cost_price]"]').val(response.cost_price || 0);
                        
                        // Update total when price is loaded
                        calculateRowTotal(row);
                    }
                },
                error: function() {
                    console.error('Error loading medicine info');
                }
            });
        } else {
            if (!row.find('input[name*="[prescription_detail_id]"]').val()) {
                unitPriceInput.val('');
            }
            quantityInput.removeAttr('max');
            row.find('input[name*="[cost_price]"]').val('0');
        }
    });

    // Add new sale detail row
    $('#addSaleDetail').click(function() {
        var container = $('#sale_details_container');
        var rowCount = container.find('.sale-detail-row').length;
        
        var newRow = createDetailRow(rowCount);
        container.append(newRow);
    });

    // Remove sale detail row
    $(document).on('click', '.remove-detail', function() {
        var container = $('#sale_details_container');
        if (container.find('.sale-detail-row').length > 1) {
            $(this).closest('.sale-detail-row').remove();
            updateRowIndexes();
            calculateTotals();
        } else {
            alert('Phải có ít nhất một dòng chi tiết!');
        }
    });

    // Calculate row total when quantity or price changes
    $(document).on('input', '.quantity-input, .unit-price-input', function() {
        var row = $(this).closest('.sale-detail-row');
        calculateRowTotal(row);
    });

    // Calculate totals when discount or tax changes
    $('#discount_amount, #tax_amount').on('input', function() {
        calculateTotals();
    });

    // Form validation
    $('#saleForm').submit(function(e) {
        var hasPatient = $('#patient_id').val();
        var hasCustomer = $('#customer_name').val().trim();
        var hasDetails = $('#sale_details_container .sale-detail-row').length > 0;
        var allDetailsValid = true;

        // Check if either patient or customer is selected
        if (!hasPatient && !hasCustomer) {
            alert('Vui lòng chọn bệnh nhân hoặc nhập tên khách hàng!');
            e.preventDefault();
            return false;
        }

        // Check if sale details are filled
        if (!hasDetails) {
            alert('Vui lòng thêm ít nhất một dòng chi tiết!');
            e.preventDefault();
            return false;
        }

        // Validate each detail row
        $('#sale_details_container .sale-detail-row').each(function() {
            var medicine = $(this).find('.medicine-select').val();
            var quantity = $(this).find('.quantity-input').val();
            var unitPrice = $(this).find('.unit-price-input').val();

            if (!medicine || !quantity || quantity <= 0 || !unitPrice || unitPrice <= 0) {
                allDetailsValid = false;
                return false;
            }
        });

        if (!allDetailsValid) {
            alert('Vui lòng điền đầy đủ thông tin cho tất cả các dòng chi tiết!');
            e.preventDefault();
            return false;
        }

        return true;
    });

    // Helper function to get medicine options
    function getMedicineOptions() {
        var options = '<option value="">-- Chọn thuốc --</option>';
        
        if (medicinesList.length > 0) {
            // Sử dụng danh sách từ biến global
            $.each(medicinesList, function(index, medicine) {
                options += '<option value="' + medicine.id + '">' + 
                          medicine.medicine_code + ' - ' + medicine.name + 
                          ' (Tồn: ' + medicine.available_quantity + ')' +
                          '</option>';
            });
        } else {
            // Fallback - lấy từ select có sẵn nếu có
            if ($('#sale_details_container .medicine-select:first').length > 0) {
                $('#sale_details_container .medicine-select:first option').each(function() {
                    options += '<option value="' + $(this).val() + '">' + $(this).text() + '</option>';
                });
            } else {
                // Load từ server nếu chưa có
                loadMedicinesList();
                options += '<option value="">Đang tải danh sách thuốc...</option>';
            }
        }
        return options;
    }

    // Calculate row total
    function calculateRowTotal(row) {
        var quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        var unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
        var discount = parseFloat(row.find('input[name*="[discount_amount]"]').val()) || 0;
        
        var total = (quantity * unitPrice) - discount;
        row.find('.total-price-input').val(total.toFixed(2));
        
        calculateTotals();
    }

    // Calculate overall totals
    function calculateTotals() {
        var subtotal = 0;
        
        $('#sale_details_container .total-price-input').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        
        var discount = parseFloat($('#discount_amount').val()) || 0;
        var tax = parseFloat($('#tax_amount').val()) || 0;
        var total = subtotal - discount + tax;
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#total_amount').val(total.toFixed(2));
    }

    // Update row indexes after removing a row
    function updateRowIndexes() {
        $('#sale_details_container .sale-detail-row').each(function(index) {
            $(this).find('input, select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Initialize calculations for edit mode
    if ($('#sale_details_container .sale-detail-row').length > 0) {
        $('#sale_details_container .sale-detail-row').each(function() {
            calculateRowTotal($(this));
        });
    }

    // Trigger patient change on page load if editing
    if ($('#patient_id').val()) {
        $('#patient_id').trigger('change');
    }
});