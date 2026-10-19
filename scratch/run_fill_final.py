import os
import sys
import re
from docx import Document

sys.stdout.reconfigure(encoding='utf-8')

src_dir = r'D:\hotrobieumau\Mau Bieu IT'
dst_dir = r'D:\Agribank\Mau Bieu IT\Ket qua da dien'

os.makedirs(dst_dir, exist_ok=True)

DATA = {
    'name': 'Nguyễn Văn An',
    'name_upper': 'NGUYỄN VĂN AN',
    'phone': '0912345678',
    'email': 'annv@agribank.com.vn',
    'branch': 'Agribank Chi nhánh Tỉnh Đồng Tháp',
    'branch_upper': 'AGRIBANK CHI NHÁNH TỈNH ĐỒNG THÁP',
    'branch_code': '6500',
    'interbank_code': '6500',
    'dept': 'Phòng Khách hàng Doanh nghiệp',
    'dept_code': 'KHDN',
    'pos': 'Cán bộ Tín dụng',
    'emp_code': 'AGR10285',
    'ipcas': 'ANNV',
    'ad_user': 'annv',
    'smf_user': 'annv',
    'beadmin_user': 'annv',
    'cccd': '087095012345',
    'place': 'Đồng Tháp',
    'doc_no': '185/NHNo-TH',
    'date_str': '18/10/2026',
    'date_full': 'ngày 18 tháng 10 năm 2026',
    'date_place_full': 'Đồng Tháp, ngày 18 tháng 10 năm 2026',
    'time_str': '08 giờ 30 phút, ngày 18/10/2026',
    'recipient': 'Trung tâm Công nghệ Thông tin Agribank',
    'reason': 'Cấp quyền truy cập hệ thống phục vụ công tác chuyên môn theo vị trí mới',
    'reason_pwd': 'Quên mật khẩu đăng nhập hệ thống',
    'content_it': 'Yêu cầu cấp mới và mở khóa quyền truy cập hệ thống theo vị trí công tác mới',
    'other_req': 'Cấp quyền theo vị trí công tác mới tại Phòng KHDN',
    'software_name': 'Phần mềm Nghiệp vụ Agribank (PMNV)',
    'role_req': 'Giao dịch viên Tín dụng KHDN',
    'auth_method': 'PKI'
}

var_map = {
    '${TenCanBo}': DATA['name'],
    '${TenCanBoHOA}': DATA['name_upper'],
    '${HoTen}': DATA['name'],
    '${HoTenHOA}': DATA['name_upper'],
    '${CanBoYeuCau}': DATA['name'],
    '${CanBoYeuCauHOA}': DATA['name_upper'],
    '${NguoiYeuCau}': DATA['name'],
    '${TenNV}': DATA['name'],
    '${TenNVHOA}': DATA['name_upper'],
    '${GDichVien}': DATA['name'],
    '${GiaoDichVien}': DATA['name'],
    '${SoDienThoai}': DATA['phone'],
    '${SDT}': DATA['phone'],
    '${Phone}': DATA['phone'],
    '${DienThoai}': DATA['phone'],
    '${phone_no}': DATA['phone'],
    '${SoDienThoaiLienHe}': DATA['phone'],
    '${SoDienThoaiNV}': DATA['phone'],
    '${DienThoaiCanBo}': DATA['phone'],
    '${EmailNV}': DATA['email'],
    '${Email}': DATA['email'],
    '${EmailNhanVien}': DATA['email'],
    '${EmailCanBo}': DATA['email'],
    '${TenChiNhanh}': DATA['branch'],
    '${TenChiNhanhHOA}': DATA['branch_upper'],
    '${ChiNhanh}': DATA['branch'],
    '${ChiNhanhHOA}': DATA['branch_upper'],
    '${BranchName}': DATA['branch'],
    '${BranchNameHOA}': DATA['branch_upper'],
    '${DonVi}': DATA['branch'],
    '${DonViHOA}': DATA['branch_upper'],
    '${TenDonVi}': DATA['branch'],
    '${TenDonViHOA}': DATA['branch_upper'],
    '${MaChiNhanh}': DATA['branch_code'],
    '${BranchCode}': DATA['branch_code'],
    '${MaCN}': DATA['branch_code'],
    '${MaDonVi}': DATA['branch_code'],
    '${branch_code}': DATA['branch_code'],
    '${PhongBan}': DATA['dept'],
    '${BoPhan}': DATA['dept'],
    '${Department}': DATA['dept'],
    '${PhongBanBoPhan}': DATA['dept'],
    '${TenPhong}': DATA['dept'],
    '${TenPhongBan}': DATA['dept'],
    '${MaPhong}': DATA['dept_code'],
    '${MaPhongBan}': DATA['dept_code'],
    '${ChucVu}': DATA['pos'],
    '${ChucDanh}': DATA['pos'],
    '${Position}': DATA['pos'],
    '${MaNV}': DATA['emp_code'],
    '${MSNV}': DATA['emp_code'],
    '${MaNhanVien}': DATA['emp_code'],
    '${MaCanBo}': DATA['emp_code'],
    '${MaSoNV}': DATA['emp_code'],
    '${MaSoNhanVien}': DATA['emp_code'],
    '${IPCAS}': DATA['ipcas'],
    '${UserIPCAS}': DATA['ipcas'],
    '${MaNguoiDungIPCAS}': DATA['ipcas'],
    '${DiaDanh}': DATA['place'],
    '${DiaDanhChiNhanh}': DATA['place'],
    '${Place}': DATA['place'],
    '${NoiLap}': DATA['place'],
    '${NgayYeuCau}': DATA['date_str'],
    '${NgayLap}': DATA['date_str'],
    '${Ngay}': DATA['date_str'],
    '${Date}': DATA['date_str'],
    '${CCCD}': DATA['cccd'],
    '${SoCCCD}': DATA['cccd'],
    '${CMND}': DATA['cccd'],
    '${SoCMND}': DATA['cccd'],
}

regex_rules = [
    (r'(Đơn\s+vị\s+yêu\s+cầu\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['branch'], 'Đơn vị yêu cầu'),
    (r'(Đơn\s+vị\s+công\s+tác\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['branch'], 'Đơn vị công tác'),
    (r'(Mã\s+chi\s+nhánh\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['branch_code'], 'Mã chi nhánh'),
    (r'(Mã\s+đơn\s+vị\s+trên\s+IPCAS\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['branch_code'], 'Mã đơn vị IPCAS'),
    (r'(Mã\s+liên\s+ngân\s+hàng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['interbank_code'], 'Mã liên ngân hàng'),
    (r'(BranchID\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['branch_code'], 'BranchID'),
    (r'(Họ\s+(?:và\s+)?tên\s+Người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['name'], 'Họ và tên Người dùng'),
    (r'(Họ\s+(?:và\s+)?tên\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['name'], 'Họ và tên người dùng'),
    (r'(Họ\s+(?:và\s+)?tên\s+người\s+sử\s+dụng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['name'], 'Họ và tên người sử dụng'),
    (r'(Họ\s+và\s+tên\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['name'], 'Họ và tên'),
    (r'(Người\s+liên\s+hệ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['name'], 'Người liên hệ'),
    (r'(Chức\s+vụ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['pos'], 'Chức vụ'),
    (r'(Phòng\s*(?:ban)?\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['dept'], 'Phòng ban'),
    (r'(Bộ\s+phận\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['dept'], 'Bộ phận'),
    (r'(Số\s+điện\s+thoại\s+liên\s+hệ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['phone'], 'Số điện thoại liên hệ'),
    (r'(Số\s+điện\s+thoại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['phone'], 'Số điện thoại'),
    (r'(Di\s+động\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['phone'], 'Di động'),
    (r'(Điện\s+thoại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['phone'], 'Điện thoại'),
    (r'(Email\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['email'], 'Email'),
    (r'(Địa\s+chỉ\s+thư\s+điện\s+tử\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['email'], 'Email'),
    (r'(User\s+ID\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['ipcas'], 'User ID'),
    (r'(Mã\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['ipcas'], 'Mã người dùng'),
    (r'(mã\s+người\s+dùng\s+là\s*)[\.…._{—–-]{3,}', r'\1' + DATA['ipcas'], 'Mã người dùng'),
    (r'(mã\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['ipcas'], 'Mã người dùng'),
    (r'(Tên\s+Phần\s+mềm\s+nghiệp\s+vụ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['software_name'], 'Tên PMNV'),
    (r'(Yêu\s+cầu\s+khác\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['other_req'], 'Yêu cầu khác'),
    (r'(Lý\s+do\s+đổi\s+lại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['reason_pwd'], 'Lý do đổi mật khẩu'),
    (r'(Lý\s+do(?:\s*\(\d+\))?\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['reason'], 'Lý do'),
    (r'(Yêu\s+cầu\s+cụ\s+thể\s+như\s+sau\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['content_it'], 'Nội dung yêu cầu IT'),
    (r'(Thông\s+tin\s+liên\s+hệ\s+của\s+cán\s+bộ\s+yêu\s+cầu[^\(]*\(SĐT,\s*email\)\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 SĐT: ' + DATA['phone'] + ', Email: ' + DATA['email'], 'Thông tin liên hệ SĐT Email'),
    (r'(Kính\s+gửi\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['recipient'], 'Kính gửi'),
    (r'(Số\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ' + DATA['doc_no'], 'Số công văn'),
    (r'<CHI NHÁNH\s*>', DATA['branch_upper'], 'Bracket CHI NHÁNH'),
    (r'<ĐƠN VỊ\s*>', DATA['branch_upper'], 'Bracket ĐƠN VỊ'),
    (r'<Chi\s+nhánh\s*>', DATA['branch_upper'], 'Bracket Chi nhánh'),
    (r'<Đơn\s+vị\s*>', DATA['branch_upper'], 'Bracket Đơn vị'),
    (r'[\.…._{—–-]{3,},\s*ngày\s*[\.…._{—–-]+\s*tháng\s*[\.…._{—–-]+\s*năm\s*20[\.…._{—–-]+', DATA['date_place_full'], 'Ngày tháng năm'),
    (r'[\.…._{—–-]{3,},\s*ngày\s*[\.…._{—–-]+\s*tháng\s*[\.…._{—–-]+\s*năm[\.…._{—–-]+', DATA['date_place_full'], 'Ngày tháng năm'),
    (r'Đồng\s+Tháp,\s*ngày[\.…._{—–-]+\s*tháng[\.…._{—–-]+\s*năm[\.…._{—–-]+', DATA['date_place_full'], 'Ngày tháng năm'),
    (r'ngày\s*[\.…._{—–-]{2,}\s*tháng\s*[\.…._{—–-]{2,}\s*năm\s*20[\.…._{—–-]+', DATA['date_full'], 'Ngày tháng năm'),
    (r'ngày\s*[\.…._{—–-]{2,}\s*tháng\s*[\.…._{—–-]{2,}\s*năm\s*[\.…._{—–-]+', DATA['date_full'], 'Ngày tháng năm'),
    (r'ngày\s*…+\s*/\s*…+\s*/\s*20…+', DATA['date_str'], 'Ngày tháng năm'),
    (r'ngày\s*…+\s*/\s*…+\s*/\s*\.+', DATA['date_str'], 'Ngày tháng năm'),
    (r'hồi\s*……\s*giờ\s*……\s*phút\s*ngày[\.…._{—–-]+', 'hồi 08 giờ 30 phút ngày ' + DATA['date_str'], 'Hồi giờ phút ngày'),
    (r'hồi\s*[\.….]{2,}\s*giờ\s*[\.….]{2,}\s*phút\s*[\.….]{2,}\s*ngày', 'hồi 08 giờ 30 phút ngày ' + DATA['date_str'], 'Hồi giờ phút ngày'),
    (r'Thời\s+gian\s+tiếp\s+nhận\s*[:：]\s*……\s*giờ\s*……\s*phút,\s*ngày\s*……\s*/\s*……\s*/\s*\.*', 'Thời gian tiếp nhận: ' + DATA['time_str'], 'Thời gian tiếp nhận'),
    (r'Thời\s+gian\s+bàn\s+giao\s*[:：]\s*……\s*giờ\s*……\s*phút,\s*ngày\s*……\s*/\s*……\s*/\s*\.*', 'Thời gian bàn giao: ' + DATA['time_str'], 'Thời gian bàn giao'),
]

blank_label_rules = [
    (r'^(Mã\s+đơn\s+vị\s+trên\s+IPCAS\s*[:：])\s*$', r'\1 ' + DATA['branch_code'], 'Mã đơn vị IPCAS'),
    (r'^(Họ\s+(?:và\s+)?tên\s*(?:người\s+dùng)?\s*[:：])\s*$', r'\1 ' + DATA['name'], 'Họ và tên'),
    (r'^(Chức\s+vụ\s*[:：])\s*$', r'\1 ' + DATA['pos'], 'Chức vụ'),
    (r'^(Đơn\s+vị\s+công\s+tác\s*[:：])\s*$', r'\1 ' + DATA['branch'], 'Đơn vị công tác'),
    (r'^(Số\s+điện\s+thoại\s*(?:liên\s+hệ)?\s*[:：])\s*$', r'\1 ' + DATA['phone'], 'Số điện thoại'),
    (r'^(Địa\s+chỉ\s+thư\s+điện\s+tử\s*[:：])\s*$', r'\1 ' + DATA['email'], 'Email'),
    (r'^(Kính\s+gửi\s*[:：])\s*$', r'\1 ' + DATA['recipient'], 'Kính gửi'),
    (r'^(Địa\s+điểm\s+bàn\s+giao\s*[:：])\s*$', r'\1 Phòng KHDN - Agribank Chi nhánh Tỉnh Đồng Tháp', 'Địa điểm bàn giao'),
    (r'^(Đề\s+nghị\s+bộ\s+phận\s+quản\s+trị\s+hệ\s+thống\s+đổi\s+lại\s+mật\s+khẩu\s+của\s+người\s+dùng\s*[:：])\s*$', r'\1 ' + DATA['ipcas'], 'Đề nghị đổi mật khẩu người dùng'),
    (r'^(Lý\s+do\s+đổi\s+lại\s*[:：])\s*$', r'\1 ' + DATA['reason_pwd'], 'Lý do đổi lại'),
]

hardcoded_sample_replacements = [
    (r'Vũ Quang Thịnh', DATA['name'], 'Cập nhật Họ tên cán bộ'),
    (r'Phó phòng Kế hoạch và QLRR Agribank Đồng Tháp', DATA['pos'], 'Cập nhật Chức vụ cán bộ'),
    (r'0902332020', DATA['phone'], 'Cập nhật SĐT cán bộ'),
    (r'thinhvuquang1@agribank.com.vn', DATA['email'], 'Cập nhật Email cán bộ'),
    (r'DTHTHINH', DATA['ipcas'], 'Cập nhật User IPCAS'),
    (r'ngày 10 tháng 10 năm 2025', DATA['date_full'], 'Cập nhật ngày lập biểu mẫu'),
    (r'ngày 22 tháng 09 năm 2026', DATA['date_full'], 'Cập nhật ngày lập biểu mẫu'),
    (r'Nguyễn Văn A\.', DATA['name'], 'Cập nhật Họ tên cán bộ'),
    (r'Nhân viên phòng\s+KT&NQ\.', DATA['pos'] + ' ' + DATA['dept'], 'Cập nhật Chức vụ & Phòng ban'),
    (r'02773\s*822\s*174', DATA['phone'], 'Cập nhật SĐT cán bộ'),
    (r'Võ Thị Cẩm Loan', DATA['name'], 'Cập nhật Họ tên cán bộ'),
    (r'Phó Phòng KTNQ', DATA['pos'], 'Cập nhật Chức vụ cán bộ'),
    (r'CT3VCLOAN', DATA['ipcas'], 'Cập nhật User IPCAS'),
    (r'loanvothicam', DATA['ad_user'], 'Cập nhật User AD'),
    (r'Họ và tên\s*:\s*\tPhòng\s*:', f"Họ và tên : {DATA['name']}\tPhòng: {DATA['dept']}", 'Cập nhật Họ tên & Phòng ban'),
    (r'Đơn vị công tác\s*:\s*\tSố điện thoại\s*:', f"Đơn vị công tác: {DATA['branch']}\tSố điện thoại: {DATA['phone']}", 'Cập nhật Đơn vị & SĐT'),
    (r'^Email\s*:\s*$', f"Email: {DATA['email']}", 'Cập nhật Email'),
    (r'Tên đăng nhập trên hệ thống TMS \(nếu có\)\s*:\s*$', f"Tên đăng nhập trên hệ thống TMS (nếu có): {DATA['ad_user']}", 'Cập nhật TMS user'),
    (r'Tên giao dịch đầy đủ bằng tiếng Việt \(VIẾT HOA\)\s*:\s*$', f"Tên giao dịch đầy đủ bằng tiếng Việt (VIẾT HOA): {DATA['branch_upper']}", 'Cập nhật Tên Tổ chức'),
    (r'Tên viết tắt\s*:\s*$', f"Tên viết tắt: AGRIBANK ĐỒNG THÁP", 'Cập nhật Tên viết tắt'),
    (r'Trực thuộc\s*:\s*$', f"Trực thuộc: Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam", 'Cập nhật Trực thuộc'),
    (r'Họ và tên\s*:\s*\t+Chức vụ\s*:\s*$', f"Họ và tên : {DATA['name']}\t\tChức vụ: {DATA['pos']}", 'Cập nhật Họ tên & Chức vụ đại diện'),
    (r'Họ và tên\s*:\s*\t+Chức vụ/bộ phận\s*:\s*$', f"Họ và tên: {DATA['name']}\tChức vụ/bộ phận: {DATA['pos']} - {DATA['dept']}", 'Cập nhật Cán bộ liên hệ'),
    (r'Email \(Tổ chức\)\s*;\s*\t+Di động\s*:\s*$', f"Email (Tổ chức): {DATA['email']}\t\tDi động: {DATA['phone']}", 'Cập nhật Email & SĐT liên hệ'),
    (r'Kính gửi:\s*Ngân hàng Agribank chi nhánh \.\.\.', f"Kính gửi: Ngân hàng {DATA['branch']}", 'Cập nhật Kính gửi chi nhánh'),
    (r'^Tên tổ chức\s*:\s*$', f"Tên tổ chức: {DATA['branch']}", 'Cập nhật Tên tổ chức'),
    (r'^Đại diện\s*:\s*$', f"Đại diện: {DATA['name']}", 'Cập nhật Đại diện tổ chức'),
    (r'Điện thoại\(cơ quan\)\s*;\s*\t+Fax\s*:\s*$', f"Điện thoại(cơ quan): 02773.852316\tFax: 02773.852317", 'Cập nhật SĐT Fax cơ quan')
]

def process_paragraph(p, filled_records):
    orig_text = p.text
    if not orig_text.strip():
        return
        
    for var_k, var_v in var_map.items():
        if var_k in p.text:
            replaced = False
            for r in p.runs:
                if var_k in r.text:
                    r.text = r.text.replace(var_k, var_v)
                    replaced = True
            if not replaced and var_k in p.text:
                p.text = p.text.replace(var_k, var_v)
            filled_records.add(f"Placeholder: {var_k} -> {var_v}")
            
    current_t = p.text
    for pat, rep, field_name in regex_rules:
        if re.search(pat, current_t, re.IGNORECASE):
            current_t = re.sub(pat, rep, current_t, flags=re.IGNORECASE)
            filled_records.add(f"Trường văn bản: {field_name}")
            
    for pat, rep, field_name in blank_label_rules:
        if re.search(pat, current_t.strip(), re.IGNORECASE):
            current_t = re.sub(pat, rep, current_t.strip(), flags=re.IGNORECASE)
            filled_records.add(f"Trường nhãn trống: {field_name}")
            
    for pat, rep, field_name in hardcoded_sample_replacements:
        if re.search(pat, current_t, re.IGNORECASE):
            current_t = re.sub(pat, rep, current_t, flags=re.IGNORECASE)
            filled_records.add(f"Dữ liệu biểu mẫu: {field_name}")
            
    if current_t != p.text:
        if p.runs:
            p.runs[0].text = current_t
            for r in p.runs[1:]:
                r.text = ''
        else:
            p.text = current_t

def process_table_rows(table, filled_records):
    if len(table.rows) < 2:
        return
        
    header_row = [c.text.strip().replace('\n', ' ') for c in table.rows[0].cells]
    header_str = " | ".join(header_row).lower()
    
    if not any(k in header_str for k in ['họ và tên', 'họ tên', 'mã nhân viên', 'chức vụ', 'user ad', 'email', 'user ipcas', 'mã số nhân viên']):
        return
        
    start_row = 1
    if len(table.rows) > 2 and re.match(r'^\s*\(?\s*1(?:\.1)?\s*\)?\s*$', table.rows[1].cells[0].text.strip()):
        start_row = 2
        
    for r_idx in range(start_row, len(table.rows)):
        row = table.rows[r_idx]
        row_txt = "".join(c.text.strip() for c in row.cells)
        if len(row_txt) <= 2 or re.match(r'^\d+$', row_txt):
            for c_idx, cell in enumerate(row.cells):
                if c_idx >= len(header_row):
                    break
                h = header_row[c_idx].lower()
                val = None
                
                if h in ['stt', 'tt', 'stt (1)', 'stt (1.1)']:
                    val = '1'
                elif any(k in h for k in ['họ và tên', 'họ tên']):
                    val = DATA['name']
                    filled_records.add("Bảng: Họ và tên")
                elif any(k in h for k in ['phòng ban', 'phòng/ đơn vị', 'phòng/đơn vị', 'đơn vị']):
                    val = DATA['dept']
                    filled_records.add("Bảng: Phòng ban/Đơn vị")
                elif 'chức vụ' in h:
                    val = DATA['pos']
                    filled_records.add("Bảng: Chức vụ")
                elif any(k in h for k in ['mã nhân viên', 'mã số nhân viên', 'mã nv']):
                    val = DATA['emp_code']
                    filled_records.add("Bảng: Mã nhân viên")
                elif any(k in h for k in ['user ad', 'user name', 'mã đăng nhập', 'mã người dùng smartform', 'mã người dùng be-admin']):
                    val = DATA['ad_user']
                    filled_records.add("Bảng: User AD / Mã đăng nhập")
                elif any(k in h for k in ['email ad', 'địa chỉ e-mail', 'email']):
                    val = DATA['email']
                    filled_records.add("Bảng: Email")
                elif any(k in h for k in ['user ipcas', 'mã người dùng ipcas', 'mã người dùng']):
                    val = DATA['ipcas']
                    filled_records.add("Bảng: User IPCAS")
                elif any(k in h for k in ['số điện thoại', 'số đtdđ', 'số điện thoại di động', 'sđt']):
                    val = DATA['phone']
                    filled_records.add("Bảng: Số điện thoại")
                elif any(k in h for k in ['số cmnd', 'số thẻ căn cước', 'cccd']):
                    val = DATA['cccd']
                    filled_records.add("Bảng: CCCD")
                elif 'phương thức xác thực' in h:
                    val = DATA['auth_method']
                    filled_records.add("Bảng: Phương thức xác thực")
                elif any(k in h for k in ['chức năng giao dịch', 'nhóm chức năng', 'chức năng, quyền']):
                    val = DATA['role_req']
                    filled_records.add("Bảng: Nhóm chức năng/Quyền")
                elif any(k in h for k in ['nội dung cập nhật', 'yêu cầu thay đổi']):
                    val = DATA['content_it']
                    filled_records.add("Bảng: Nội dung cập nhật/Yêu cầu")
                elif 'lý do' in h:
                    val = DATA['reason']
                    filled_records.add("Bảng: Lý do")
                elif h in ['yêu cầu', 'loại yêu cầu']:
                    val = 'Cấp mới'
                    filled_records.add("Bảng: Loại yêu cầu")
                elif 'mã chi nhánh' in h:
                    val = DATA['branch_code']
                    filled_records.add("Bảng: Mã chi nhánh")
                    
                if val is not None:
                    if cell.paragraphs:
                        p = cell.paragraphs[0]
                        if p.runs:
                            p.runs[0].text = val
                            for r in p.runs[1:]:
                                r.text = ''
                        else:
                            p.text = val
                    else:
                        cell.text = val
            break

docx_files = []
for root, dirs, files in os.walk(src_dir):
    for f in files:
        if f.endswith('.docx') and not f.startswith('~$'):
            docx_files.append(os.path.join(root, f))

summary_report = []

for filepath in sorted(docx_files):
    rel_path = os.path.relpath(filepath, src_dir)
    target_path = os.path.join(dst_dir, rel_path)
    os.makedirs(os.path.dirname(target_path), exist_ok=True)
    
    file_info = {
        'file': rel_path,
        'target': target_path,
        'status': 'Thành công',
        'filled_fields': [],
        'error': None
    }
    
    try:
        doc = Document(filepath)
        filled = set()
        
        for s in doc.sections:
            for p in s.header.paragraphs:
                process_paragraph(p, filled)
            for p in s.footer.paragraphs:
                process_paragraph(p, filled)
                
        for p in doc.paragraphs:
            process_paragraph(p, filled)
            
        for tbl in doc.tables:
            for row in tbl.rows:
                for cell in row.cells:
                    for p in cell.paragraphs:
                        process_paragraph(p, filled)
            process_table_rows(tbl, filled)
            
        doc.save(target_path)
        file_info['filled_fields'] = sorted(list(filled))
        
        doc_verify = Document(target_path)
        if len(doc_verify.paragraphs) != len(doc.paragraphs) or len(doc_verify.tables) != len(doc.tables):
            file_info['status'] = 'Cảnh báo: Sai lệch số lượng đoạn văn/bảng'
            
    except Exception as e:
        file_info['status'] = 'Lỗi'
        file_info['error'] = str(e)
        
    summary_report.append(file_info)

import json
with open(r'd:\hotrobieumau\scratch\fill_report.json', 'w', encoding='utf-8') as f:
    json.dump(summary_report, f, ensure_ascii=False, indent=2)

print(f"Executed fill on {len(summary_report)} files successfully.")
