<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-<?php echo $edit_prescription ? 'edit' : 'plus'; ?>"></i>
            <?php echo $edit_prescription ? 'Sửa đơn thuốc' : 'Thêm đơn thuốc mới'; ?>
        </h3>
        <?php if ($edit_prescription): ?>
            <div class="card-tools">
                <a href="<?php echo BASE_URL; ?>manager/prescriptions/" class="btn btn-tool">
                    <i class="fas fa-times"></i> Hủy
                </a>
            </div>
        <?php endif; ?>
    </div>
    <form method="POST" id="prescriptionForm">
        <div class="card-body">
            <?php if ($edit_prescription): ?>
                <input type="hidden" name="prescription_id" value="<?php echo $edit_prescription['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="prescription_code">Mã đơn thuốc</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="prescription_code" name="prescription_code" 
                                   value="<?php echo htmlspecialchars($edit_prescription['prescription_code'] ?? generatePrescriptionCode($pdo)); ?>" 
                                   placeholder="Để trống để tự động tạo" maxlength="10">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                    <i class="fas fa-magic"></i> Tự động
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Mã đơn thuốc tự động theo thứ tự PRES000001, PRES000002... Để trống để hệ thống tự tạo.
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="patient_id">Bệnh nhân <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-control" id="patient_id" name="patient_id" required>
                                <option value="">Chọn bệnh nhân</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" 
                                            <?php echo ($edit_prescription && $edit_prescription['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addPatientModal">
                                    <i class="fas fa-user-plus"></i> Thêm BN
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="doctor_name">Tên bác sĩ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="doctor_name" name="doctor_name" 
                               value="<?php echo htmlspecialchars($edit_prescription['doctor_name'] ?? ''); ?>" 
                               placeholder="Nhập tên bác sĩ" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="doctor_license">Số giấy phép bác sĩ</label>
                        <input type="text" class="form-control" id="doctor_license" name="doctor_license" 
                               value="<?php echo htmlspecialchars($edit_prescription['doctor_license'] ?? ''); ?>" 
                               placeholder="Nhập số giấy phép">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="hospital_clinic">Bệnh viện/Phòng khám</label>
                        <input type="text" class="form-control" id="hospital_clinic" name="hospital_clinic" 
                               value="<?php echo htmlspecialchars($edit_prescription['hospital_clinic'] ?? ''); ?>" 
                               placeholder="Nhập tên bệnh viện/phòng khám">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="prescription_date">Ngày kê đơn <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="prescription_date" name="prescription_date" 
                               value="<?php echo htmlspecialchars($edit_prescription['prescription_date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="diagnosis">Chẩn đoán</label>
                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" 
                          placeholder="Nhập chẩn đoán"><?php echo htmlspecialchars($edit_prescription['diagnosis'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="notes">Ghi chú</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" 
                          placeholder="Nhập ghi chú"><?php echo htmlspecialchars($edit_prescription['notes'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="status">Trạng thái</label>
                <select class="form-control" id="status" name="status">
                    <option value="pending" <?php echo (!$edit_prescription || $edit_prescription['status'] === 'pending') ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="partial" <?php echo ($edit_prescription && $edit_prescription['status'] === 'partial') ? 'selected' : ''; ?>>Đã xử lý một phần</option>
                    <option value="completed" <?php echo ($edit_prescription && $edit_prescription['status'] === 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="cancelled" <?php echo ($edit_prescription && $edit_prescription['status'] === 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                    <option value="expired" <?php echo ($edit_prescription && $edit_prescription['status'] === 'expired') ? 'selected' : ''; ?>>Hết hạn</option>
                </select>
            </div>

            <div class="form-group">
                <label>Chi tiết đơn thuốc</label>
                <div id="prescription-details">
                    <?php include 'prescription_details.php'; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success" id="add-medicine">
                            <i class="fas fa-plus"></i> Thêm thuốc
                        </button>
                    </div>
                    <div class="col-md-6 text-right">
                        <h4>Tổng tiền: <span id="grand-total" class="text-primary">0 VNĐ</span></h4>
                    </div>
                </div>
            </div>

            <div id="medicine-template" style="display: none;">
                <div class="card card-outline card-secondary mb-2 prescription-detail-item">
                    <div class="card-header">
                        <h5 class="card-title">Thuốc <span class="medicine-number"></span></h5>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-danger remove-detail">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tên thuốc</label>
                                    <select class="form-control medicine-select" name="medicine_ids[]">
                                        <option value="">Chọn thuốc</option>
                                        <?php foreach ($medicines as $medicine): ?>
                                            <option value="<?php echo $medicine['id']; ?>" 
                                                    data-price="<?php echo $medicine['selling_price']; ?>"
                                                    data-unit="<?php echo htmlspecialchars($medicine['unit']); ?>"
                                                    data-available="<?php echo $medicine['available_quantity']; ?>">
                                                <?php echo htmlspecialchars($medicine['name']); ?> 
                                                (<?php echo $medicine['available_quantity']; ?> <?php echo htmlspecialchars($medicine['unit']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>SL kê đơn</label>
                                    <input type="number" class="form-control quantity-prescribed" 
                                           name="quantities_prescribed[]" min="1">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>SL đã cấp</label>
                                    <input type="number" class="form-control" 
                                           name="quantities_dispensed[]" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Đơn giá</label>
                                    <input type="number" class="form-control unit-price" 
                                           name="unit_prices[]" step="0.01" readonly>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Thành tiền</label>
                                    <input type="text" class="form-control total-price" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hướng dẫn sử dụng</label>
                                    <input type="text" class="form-control" name="dosage_instructions[]" 
                                           placeholder="VD: Uống sau ăn">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tần suất</label>
                                    <input type="text" class="form-control" name="frequencies[]" 
                                           placeholder="VD: 2 lần/ngày">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Số ngày</label>
                                    <input type="number" class="form-control" name="duration_days[]" 
                                           min="1" placeholder="7">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select class="form-control" name="detail_status[]">
                                        <option value="pending">Chờ xử lý</option>
                                        <option value="partial">Đã xử lý một phần</option>
                                        <option value="completed">Hoàn thành</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_prescription ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
                <?php if ($edit_prescription): ?>
                    <a href="<?php echo BASE_URL; ?>manager/prescriptions/" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                <?php else: ?>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Làm mới
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Include Patient Modal -->
<?php include 'patient_modal.php'; ?>

<script>
$(document).ready(function() {
    // Auto-generate prescription code
    $('#generateCode').click(function() {
        $.ajax({
            url: '<?php echo BASE_URL; ?>api/generate_prescription_code.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#prescription_code').val(response.code);
                }
            },
            error: function() {
                alert('Không thể tạo mã tự động');
            }
        });
    });

    // Auto-generate patient code in modal
    $('#generatePatientCode').click(function() {
        $.ajax({
            url: '<?php echo BASE_URL; ?>api/generate_patient_code.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#patient_code_modal').val(response.code);
                }
            },
            error: function() {
                alert('Không thể tạo mã tự động');
            }
        });
    });

    // Handle add patient form submission
    $('#addPatientForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>api/add_patient.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Add new patient to select dropdown
                    const newOption = `<option value="${response.patient.id}" selected>
                        ${response.patient.full_name}
                    </option>`;
                    $('#patient_id').append(newOption);
                    
                    // Close modal and reset form
                    $('#addPatientModal').modal('hide');
                    $('#addPatientForm')[0].reset();
                    
                    // Show success message
                    toastr.success('Thêm bệnh nhân thành công!');
                } else {
                    toastr.error(response.message || 'Có lỗi xảy ra khi thêm bệnh nhân');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Có lỗi xảy ra khi thêm bệnh nhân';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            }
        });
    });

    // Reset form when modal is closed
    $('#addPatientModal').on('hidden.bs.modal', function() {
        $('#addPatientForm')[0].reset();
        $('#addPatientForm .form-control').removeClass('is-invalid');
        $('#addPatientForm .invalid-feedback').remove();
    });

    // Medicine management functions
    let medicineCounter = 0;

    // Add medicine detail
    $('#add-medicine').click(function() {
        medicineCounter++;
        const template = $('#medicine-template').html();
        const newDetail = $(template);
        newDetail.find('.medicine-number').text(medicineCounter);
        $('#prescription-details').append(newDetail);
        updateTotalAmount();
    });

    // Remove medicine detail
    $(document).on('click', '.remove-detail', function() {
        $(this).closest('.prescription-detail-item').remove();
        updateTotalAmount();
    });

    // Update price when medicine or quantity changes
    $(document).on('change', '.medicine-select, .quantity-prescribed', function() {
        const row = $(this).closest('.prescription-detail-item');
        const medicineSelect = row.find('.medicine-select');
        const quantityInput = row.find('.quantity-prescribed');
        const unitPriceInput = row.find('.unit-price');
        const totalPriceInput = row.find('.total-price');

        if (medicineSelect.val() && quantityInput.val()) {
            const selectedOption = medicineSelect.find(':selected');
            const unitPrice = parseFloat(selectedOption.data('price')) || 0;
            const quantity = parseInt(quantityInput.val()) || 0;
            const totalPrice = unitPrice * quantity;

            unitPriceInput.val(unitPrice);
            totalPriceInput.val(totalPrice.toLocaleString('vi-VN') + ' VNĐ');
        } else {
            unitPriceInput.val('');
            totalPriceInput.val('');
        }
        
        updateTotalAmount();
    });

    // Update total amount
    function updateTotalAmount() {
        let grandTotal = 0;
        $('.prescription-detail-item').each(function() {
            const unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
            const quantity = parseInt($(this).find('.quantity-prescribed').val()) || 0;
            grandTotal += unitPrice * quantity;
        });
        $('#grand-total').text(grandTotal.toLocaleString('vi-VN') + ' VNĐ');
    }

    // Initialize with one medicine detail if not editing
    <?php if (!$edit_prescription): ?>
    $('#add-medicine').click();
    <?php endif; ?>
});
</script>