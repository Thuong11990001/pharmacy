<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle"></i> Chi tiết đơn thuốc
        </h3>
        <div class="card-tools">
            <a href="<?php echo BASE_URL; ?>manager/prescriptions/" class="btn btn-tool">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên thuốc</th>
                        <th>Số lượng kê đơn</th>
                        <th>Số lượng đã cấp</th>
                        <th>Hướng dẫn sử dụng</th>
                        <th>Tần suất</th>
                        <th>Thời gian (ngày)</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $stt = 1; foreach ($prescription_details as $detail): ?>
                        <tr>
                            <td><?php echo $stt++; ?></td>
                            <td><?php echo htmlspecialchars($detail['medicine_name']); ?></td>
                            <td><?php echo $detail['quantity_prescribed']; ?></td>
                            <td><?php echo $detail['quantity_dispensed']; ?></td>
                            <td><?php echo htmlspecialchars($detail['dosage_instructions'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($detail['frequency'] ?? 'N/A'); ?></td>
                            <td><?php echo $detail['duration_days'] ?? 'N/A'; ?></td>
                            <td><?php echo number_format($detail['total_price'], 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <?php if ($detail['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-hourglass-half"></i> Chờ xử lý
                                    </span>
                                <?php elseif ($detail['status'] === 'partial'): ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-spinner"></i> Đã xử lý một phần
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> Hoàn thành
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>