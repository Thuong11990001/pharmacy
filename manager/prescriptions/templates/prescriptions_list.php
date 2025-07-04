<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i> Danh sách đơn thuốc
        </h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng: <?php echo count($prescriptions); ?> đơn thuốc</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="prescriptionsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã đơn</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Ngày kê đơn</th>
                        <th>Chẩn đoán</th>
                        <th>Trạng thái</th>
                        <th>Người xử lý</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prescriptions)): ?>
                        <tr>
                            <td colspan="10" class="text-center">
                                <i class="fas fa-inbox"></i> Chưa có đơn thuốc nào
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $stt = 1; foreach ($prescriptions as $prescription): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($prescription['prescription_code']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($prescription['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($prescription['doctor_name']); ?></td>
                                <td>
                                    <?php 
                                    $prescription_date = new DateTime($prescription['prescription_date']);
                                    echo $prescription_date->format('d/m/Y');
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($prescription['diagnosis'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($prescription['status'] === 'pending'): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-hourglass-half"></i> Chờ xử lý
                                        </span>
                                    <?php elseif ($prescription['status'] === 'partial'): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-spinner"></i> Đã xử lý một phần
                                        </span>
                                    <?php elseif ($prescription['status'] === 'completed'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Hoàn thành
                                        </span>
                                    <?php elseif ($prescription['status'] === 'cancelled'): ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times-circle"></i> Đã hủy
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-ban"></i> Hết hạn
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($prescription['processed_by_name'] ?: 'Chưa có'); ?></td>
                                <td>
                                    <small>
                                        <?php 
                                        $created_date = new DateTime($prescription['created_at']);
                                        echo $created_date->format('d/m/Y H:i'); 
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo BASE_URL; ?>manager/prescriptions/?action=edit&id=<?php echo $prescription['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>manager/prescriptions/?action=view&id=<?php echo $prescription['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>