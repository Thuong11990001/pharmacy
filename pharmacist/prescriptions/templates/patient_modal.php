<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addPatientModalLabel">
                    <i class="fas fa-user-plus"></i> Thêm bệnh nhân mới
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addPatientForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="patient_code_modal">Mã bệnh nhân</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="patient_code_modal" name="patient_code" 
                                           placeholder="Để trống để tự động tạo" maxlength="7">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="generatePatientCode">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Mã tự động: P000001, P000002... Để trống để hệ thống tự tạo.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name_modal">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name_modal" name="full_name" 
                                       placeholder="Nhập họ và tên" maxlength="255" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_number_modal">Số CCCD/CMND</label>
                                <input type="text" class="form-control" id="id_number_modal" name="id_number" 
                                       placeholder="Nhập số CCCD/CMND" maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone_modal">Số điện thoại</label>
                                <input type="tel" class="form-control" id="phone_modal" name="phone" 
                                       placeholder="Nhập số điện thoại" maxlength="20">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_of_birth_modal">Ngày sinh</label>
                                <input type="date" class="form-control" id="date_of_birth_modal" name="date_of_birth">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender_modal">Giới tính</label>
                                <select class="form-control" id="gender_modal" name="gender">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email_modal">Email</label>
                        <input type="email" class="form-control" id="email_modal" name="email" 
                               placeholder="Nhập địa chỉ email" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="address_modal">Địa chỉ</label>
                        <textarea class="form-control" id="address_modal" name="address" rows="2" 
                                  placeholder="Nhập địa chỉ"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact_modal">Liên hệ khẩn cấp</label>
                        <input type="text" class="form-control" id="emergency_contact_modal" name="emergency_contact" 
                               placeholder="Tên và số điện thoại người liên hệ khẩn cấp" maxlength="255">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="allergies_modal">Dị ứng</label>
                                <textarea class="form-control" id="allergies_modal" name="allergies" rows="2" 
                                          placeholder="Ghi chú về các loại dị ứng"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="medical_notes_modal">Ghi chú y tế</label>
                                <textarea class="form-control" id="medical_notes_modal" name="medical_notes" rows="2" 
                                          placeholder="Ghi chú về tình trạng sức khỏe"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Thêm bệnh nhân
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>