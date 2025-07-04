<?php if ($edit_prescription && !empty($edit_prescription_details)): ?>
    <?php foreach ($edit_prescription_details as $index => $detail): ?>
        <div class="card card-outline card-secondary mb-2 prescription-detail-item">
            <div class="card-header">
                <h5 class="card-title">Thuốc <?php echo $index + 1; ?></h5>
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
                            <select class="form-control medicine-select" name="medicine_ids[]" required>
                                <option value="">Chọn thuốc</option>
                                <?php foreach ($medicines as $medicine): ?>
                                    <option value="<?php echo $medicine['id']; ?>" 
                                            data-price="<?php echo $medicine['selling_price']; ?>"
                                            data-unit="<?php echo htmlspecialchars($medicine['unit']); ?>"
                                            data-available="<?php echo $medicine['available_quantity']; ?>"
                                            <?php echo ($detail['medicine_id'] == $medicine['id']) ? 'selected' : ''; ?>>
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
                                   name="quantities_prescribed[]" min="1" 
                                   value="<?php echo $detail['quantity_prescribed']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>SL đã cấp</label>
                            <input type="number" class="form-control" 
                                   name="quantities_dispensed[]" min="0" 
                                   value="<?php echo $detail['quantity_dispensed']; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Đơn giá</label>
                            <input type="number" class="form-control unit-price" 
                                   name="unit_prices[]" step="0.01" readonly
                                   value="<?php echo $detail['unit_price']; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Thành tiền</label>
                            <input type="text" class="form-control total-price" readonly
                                   value="<?php echo number_format($detail['total_price'], 0, ',', '.'); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Hướng dẫn sử dụng</label>
                            <input type="text" class="form-control" name="dosage_instructions[]" 
                                   placeholder="VD: Uống sau ăn"
                                   value="<?php echo htmlspecialchars($detail['dosage_instructions']); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tần suất</label>
                            <input type="text" class="form-control" name="frequencies[]" 
                                   placeholder="VD: 2 lần/ngày"
                                   value="<?php echo htmlspecialchars($detail['frequency']); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Số ngày</label>
                            <input type="number" class="form-control" name="duration_days[]" 
                                   min="1" placeholder="7"
                                   value="<?php echo $detail['duration_days']; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select class="form-control" name="detail_status[]">
                                <option value="pending" <?php echo ($detail['status'] === 'pending') ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="partial" <?php echo ($detail['status'] === 'partial') ? 'selected' : ''; ?>>Đã xử lý một phần</option>
                                <option value="completed" <?php echo ($detail['status'] === 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card card-outline card-secondary mb-2 prescription-detail-item">
        <div class="card-header">
            <h5 class="card-title">Thuốc 1</h5>
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
                        <select class="form-control medicine-select" name="medicine_ids[]" required>
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
                               name="quantities_prescribed[]" min="1" required>
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
<?php endif; ?>