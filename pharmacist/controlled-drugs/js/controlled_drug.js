$(document).ready(function() {
    // Initialize DataTable
    $('#controlledDrugTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 25,
        "order": [[8, "desc"]], // Sort by datetime column
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [10] }, // Disable sorting for action column
            { "className": "text-center", "targets": [0, 4, 9, 10] },
            { "className": "text-right", "targets": [5] }
        ],
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm'
            }
        ]
    });

    // View Detail Button
    $(document).on('click', '.view-detail', function() {
        const logId = $(this).data('log-id');
        
        $.ajax({
            url: window.location.href,
            method: 'GET',
            data: { 
                action: 'detail', 
                id: logId 
            },
            dataType: 'json',
            beforeSend: function() {
                $('#detailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');
                $('#detailModal').modal('show');
            },
            success: function(data) {
                if (data) {
                    const content = `
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle text-info"></i> Thông tin chung</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Mã log:</strong></td><td><code>${data.log_code}</code></td></tr>
                                    <tr><td><strong>Thời gian bán:</strong></td><td>${data.formatted_sold_at}</td></tr>
                                    <tr><td><strong>Nhân viên bán:</strong></td><td>${data.sold_by_name}</td></tr>
                                    <tr><td><strong>Mã giao dịch:</strong></td><td><code>${data.sale_code}</code></td></tr>
                                    <tr><td><strong>Tổng tiền GD:</strong></td><td><strong>${new Intl.NumberFormat('vi-VN').format(data.sale_total)}đ</strong></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-pills text-warning"></i> Thông tin thuốc</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Mã thuốc:</strong></td><td><code>${data.medicine_code}</code></td></tr>
                                    <tr><td><strong>Tên thuốc:</strong></td><td>${data.medicine_name}</td></tr>
                                    <tr><td><strong>Hoạt chất:</strong></td><td>${data.generic_name || 'N/A'}</td></tr>
                                    <tr><td><strong>Hàm lượng:</strong></td><td>${data.strength || 'N/A'}</td></tr>
                                    <tr><td><strong>Dạng bào chế:</strong></td><td>${data.dosage_form || 'N/A'}</td></tr>
                                    <tr><td><strong>Số lô:</strong></td><td><code>${data.batch_number}</code></td></tr>
                                    <tr><td><strong>Hạn sử dụng:</strong></td><td>${data.formatted_expiry_date}</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-user text-primary"></i> Thông tin bệnh nhân</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Mã BN:</strong></td><td><code>${data.patient_code}</code></td></tr>
                                    <tr><td><strong>Họ tên:</strong></td><td>${data.patient_name}</td></tr>
                                    <tr><td><strong>Điện thoại:</strong></td><td>${data.patient_phone || 'N/A'}</td></tr>
                                    <tr><td><strong>Địa chỉ:</strong></td><td>${data.patient_address || 'N/A'}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-user-md text-success"></i> Thông tin đơn thuốc</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Mã đơn:</strong></td><td><code>${data.prescription_code}</code></td></tr>
                                    <tr><td><strong>Bác sĩ:</strong></td><td>${data.doctor_name}</td></tr>
                                    <tr><td><strong>Giấy phép:</strong></td><td>${data.doctor_license || 'N/A'}</td></tr>
                                    <tr><td><strong>Cơ sở khám:</strong></td><td>${data.hospital_clinic || 'N/A'}</td></tr>
                                    <tr><td><strong>Chẩn đoán:</strong></td><td>${data.diagnosis || 'N/A'}</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <h5><i class="fas fa-calculator text-info"></i> Chi tiết số lượng & giá</h5>
                                <table class="table table-bordered">
                                    <tr class="bg-light">
                                        <td><strong>Số lượng:</strong></td>
                                        <td><span class="badge badge-primary">${new Intl.NumberFormat('vi-VN').format(data.quantity)}</span></td>
                                        <td><strong>Đơn giá:</strong></td>
                                        <td><span class="text-info">${new Intl.NumberFormat('vi-VN').format(data.unit_price)}đ</span></td>
                                        <td><strong>Thành tiền:</strong></td>
                                        <td><span class="text-danger font-weight-bold">${new Intl.NumberFormat('vi-VN').format(data.total_price)}đ</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <h5><i class="fas fa-check-circle text-success"></i> Trạng thái phê duyệt</h5>
                                <div class="alert ${data.supervisor_approved_by ? 'alert-success' : 'alert-warning'}">
                                    ${data.supervisor_approved_by ? 
                                        `<i class="fas fa-check-circle"></i> <strong>Đã được phê duyệt</strong> bởi: ${data.supervisor_name}` : 
                                        `<i class="fas fa-hourglass-half"></i> <strong>Chờ phê duyệt</strong>`
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                    $('#detailContent').html(content);
                } else {
                    $('#detailContent').html('<div class="alert alert-danger">Không tìm thấy thông tin chi tiết.</div>');
                }
            },
            error: function() {
                $('#detailContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải thông tin.</div>');
            }
        });
    });

    // Approve Log Button
    $(document).on('click', '.approve-log', function() {
        const logId = $(this).data('log-id');
        const button = $(this);
        
        if (confirm('Bạn có chắc chắn muốn phê duyệt log này không?')) {
            $.ajax({
                url: window.location.href,
                method: 'GET',
                data: { 
                    action: 'approve', 
                    id: logId 
                },
                beforeSend: function() {
                    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                },
                success: function() {
                    // Reload page to show updated status
                    location.reload();
                },
                error: function() {
                    alert('Có lỗi xảy ra khi phê duyệt!');
                    button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                }
            });
        }
    });

    // Export Excel Button
    $('#exportExcel').click(function() {
        window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'action=export';
    });

    // Print Report Button
    $('#printReport').click(function() {
        const printWindow = window.open(
            window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'action=print',
            '_blank',
            'width=800,height=600'
        );
    });

    // Show Statistics Button
    $('#showStatistics').click(function() {
        $.ajax({
            url: 'statistics.php', // You'll need to create this file
            method: 'GET',
            beforeSend: function() {
                $('#statisticsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải thống kê...</div>');
                $('#statisticsModal').modal('show');
            },
            success: function(data) {
                $('#statisticsContent').html(data);
            },
            error: function() {
                $('#statisticsContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải thống kê.</div>');
            }
        });
    });

    // Auto-refresh for pending approvals (every 5 minutes)
    setInterval(function() {
        const pendingCount = $('.badge-warning:contains("Chờ duyệt")').length;
        if (pendingCount > 0) {
            // Update badge count if needed
            $('.badge.badge-danger').text(pendingCount);
        }
    }, 300000); // 5 minutes

    // Search functionality enhancement
    $('#controlledDrugTable_filter input').attr('placeholder', 'Tìm kiếm theo tên thuốc, bệnh nhân, bác sĩ...');

    // Tooltip initialization
    $('[title]').tooltip();
});