import os
import sys
import re
from docx import Document

sys.stdout.reconfigure(encoding='utf-8')

src_dir = r'D:\hotrobieumau\Mau Bieu IT'
dst_dir = r'D:\hotrobieumau\Mau Bieu IT\File da gan tag'

os.makedirs(dst_dir, exist_ok=True)

# 25 Official IT Support Form Fields in system
OFFICIAL_TAGS = {
    'TenCanBo': {'name': 'Họ và tên cán bộ yêu cầu', 'source': 'users.name / Session user_name', 'group': 'requester'},
    'SDT': {'name': 'Số điện thoại cá nhân', 'source': 'users.phone_no (chỉ số cá nhân)', 'group': 'requester'},
    'ChucVu': {'name': 'Chức vụ', 'source': 'positions.position_name / cache ChucVu', 'group': 'requester'},
    'MaChiNhanh': {'name': 'Mã chi nhánh', 'source': 'branches.branch_code / Session UserBranchCode', 'group': 'requester'},
    'TenChiNhanh': {'name': 'Tên chi nhánh / Đơn vị', 'source': 'branches.branch_name / Session UserBranchName', 'group': 'requester'},
    'PhongBan': {'name': 'Phòng ban / Bộ phận', 'source': 'departments.department_code / department_name', 'group': 'requester'},
    'TenChuongTrinh': {'name': 'Tên Chương trình / Phần mềm', 'source': 'Người dùng chọn / nhập trên form', 'group': 'system_device'},
    'DiaChiIP': {'name': 'Địa chỉ IP máy tính', 'source': 'request()->ip() / cache DiaChiIP', 'group': 'system_device'},
    'MaTaiSan': {'name': 'Mã tài sản / Số máy', 'source': 'Người dùng nhập trên form', 'group': 'system_device'},
    'TenHeThong': {'name': 'Tên hệ thống thông tin', 'source': 'Người dùng chọn trên form', 'group': 'system_device'},
    'TieuDeYeuCau': {'name': 'Tiêu đề yêu cầu / Sự cố', 'source': 'Người dùng nhập trên form', 'group': 'issue_request'},
    'NoiDungYeuCau': {'name': 'Nội dung chi tiết yêu cầu', 'source': 'Người dùng nhập trên form', 'group': 'issue_request'},
    'LyDo': {'name': 'Lý do đề nghị', 'source': 'Người dùng nhập trên form', 'group': 'issue_request'},
    'NgayYeuCau': {'name': 'Ngày yêu cầu (dạng số)', 'source': 'Carbon::now()->format("Y-m-d") / date input', 'group': 'issue_request'},
    'CanBoIT': {'name': 'Cán bộ IT tiếp nhận', 'source': 'Cán bộ IT xử lý nhập', 'group': 'approval'},
    'KetQuaXuLy': {'name': 'Kết quả xử lý', 'source': 'Cán bộ IT xử lý nhập', 'group': 'approval'},
    'LanhDaoPheDuyet': {'name': 'Lãnh đạo đơn vị phê duyệt', 'source': 'Lãnh đạo ký duyệt', 'group': 'approval'},
    'DiaDanh': {'name': 'Địa danh chi nhánh', 'source': 'branches.branch_place / Session UserBranchPlace', 'group': 'requester'},
    'CCCD': {'name': 'CCCD/CMND Cán bộ', 'source': 'users.citizen_id / cache CCCD', 'group': 'requester'},
    'MaNV': {'name': 'Mã số nhân viên', 'source': 'users.employee_code', 'group': 'requester'},
    'EmailNV': {'name': 'Email nhân viên', 'source': 'users.email / Session user_email', 'group': 'requester'},
    'IPCAS': {'name': 'Mã người dùng IPCAS', 'source': 'users.user_ipcas / cache IPCAS', 'group': 'requester'},
    'TenThietBi': {'name': 'Tên thiết bị / Phần mềm', 'source': 'Người dùng nhập trên form', 'group': 'system_device'},
    'NgayThangNam': {'name': 'Ngày tháng năm bằng chữ', 'source': 'SupportFormService::formatDateInWords(NgayYeuCau)', 'group': 'issue_request'},
    'SDTCoQuan': {'name': 'Số điện thoại cơ quan', 'source': 'branches.branch_phone', 'group': 'requester'},
}

DERIVED_TAGS = {
    'TenCanBoHOA': {'name': 'Họ và tên cán bộ in hoa', 'source': 'mb_strtoupper(users.name)', 'parent': 'TenCanBo'},
    'TenChiNhanhHOA': {'name': 'Tên chi nhánh in hoa', 'source': 'mb_strtoupper(branches.branch_name)', 'parent': 'TenChiNhanh'},
    'ChiNhanhHOA': {'name': 'Tên chi nhánh in hoa', 'source': 'mb_strtoupper(branches.branch_name)', 'parent': 'TenChiNhanh'},
    'MaPhong': {'name': 'Mã phòng ban', 'source': 'departments.department_code', 'parent': 'PhongBan'},
}

# Regex rules for attaching tags
paragraph_tag_rules = [
    # Units
    (r'(Đơn\s+vị\s+yêu\s+cầu\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChiNhanh}', 'Đơn vị yêu cầu', 'TenChiNhanh'),
    (r'(Đơn\s+vị\s+công\s+tác\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh'),
    (r'(Mã\s+chi\s+nhánh\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'Mã chi nhánh', 'MaChiNhanh'),
    (r'(Mã\s+đơn\s+vị\s+trên\s+IPCAS\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'Mã đơn vị trên IPCAS', 'MaChiNhanh'),
    (r'(BranchID\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'BranchID', 'MaChiNhanh'),
    
    # User / Officer
    (r'(Họ\s+(?:và\s+)?tên\s+Người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên Người dùng', 'TenCanBo'),
    (r'(Họ\s+(?:và\s+)?tên\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên người dùng', 'TenCanBo'),
    (r'(Họ\s+(?:và\s+)?tên\s+người\s+sử\s+dụng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên người sử dụng', 'TenCanBo'),
    (r'(Họ\s+và\s+tên\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên', 'TenCanBo'),
    (r'(Người\s+liên\s+hệ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Người liên hệ', 'TenCanBo'),
    (r'(Chức\s+vụ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${ChucVu}', 'Chức vụ', 'ChucVu'),
    (r'(Phòng\s*(?:ban)?\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${PhongBan}', 'Phòng ban', 'PhongBan'),
    (r'(Bộ\s+phận\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${PhongBan}', 'Bộ phận', 'PhongBan'),
    (r'(Số\s+điện\s+thoại\s+liên\s+hệ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Số điện thoại liên hệ', 'SDT'),
    (r'(Số\s+điện\s+thoại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Số điện thoại', 'SDT'),
    (r'(Di\s+động\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Di động', 'SDT'),
    (r'(Điện\s+thoại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Điện thoại', 'SDT'),
    (r'(Email\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${EmailNV}', 'Email', 'EmailNV'),
    (r'(Địa\s+chỉ\s+thư\s+điện\s+tử\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${EmailNV}', 'Địa chỉ thư điện tử', 'EmailNV'),
    (r'(User\s+ID\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${IPCAS}', 'User ID', 'IPCAS'),
    (r'(Mã\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${IPCAS}', 'Mã người dùng', 'IPCAS'),
    (r'(mã\s+người\s+dùng\s+là\s*)[\.…._{—–-]{3,}', r'\1${IPCAS}', 'mã người dùng là', 'IPCAS'),
    
    # Request & reason
    (r'(Tên\s+Phần\s+mềm\s+nghiệp\s+vụ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChuongTrinh}', 'Tên Phần mềm nghiệp vụ', 'TenChuongTrinh'),
    (r'(Lý\s+do\s+đổi\s+lại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${LyDo}', 'Lý do đổi lại', 'LyDo'),
    (r'(Lý\s+do(?:\s*\(\d+\))?\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${LyDo}', 'Lý do', 'LyDo'),
    (r'(Yêu\s+cầu\s+cụ\s+thể\s+như\s+sau\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${NoiDungYeuCau}', 'Yêu cầu cụ thể như sau', 'NoiDungYeuCau'),
    (r'(Thông\s+tin\s+liên\s+hệ\s+của\s+cán\s+bộ\s+yêu\s+cầu[^\(]*\(SĐT,\s*email\)\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 SĐT: ${SDT}, Email: ${EmailNV}', 'Thông tin liên hệ SĐT, email', 'SDT & EmailNV'),
    
    # Bracket tokens
    (r'<CHI NHÁNH\s*>', '${TenChiNhanhHOA}', '<CHI NHÁNH>', 'TenChiNhanhHOA'),
    (r'<ĐƠN VỊ\s*>', '${TenChiNhanhHOA}', '<ĐƠN VỊ>', 'TenChiNhanhHOA'),
    (r'<Chi\s+nhánh\s*>', '${TenChiNhanhHOA}', '<Chi nhánh>', 'TenChiNhanhHOA'),
    (r'<Đơn\s+vị\s*>', '${TenChiNhanhHOA}', '<Đơn vị>', 'TenChiNhanhHOA'),
    
    # Date expressions
    (r'[\.…._{—–-]{3,},\s*ngày\s*[\.…._{—–-]+\s*tháng\s*[\.…._{—–-]+\s*năm\s*20[\.…._{—–-]+', '${DiaDanh}, ${NgayThangNam}', 'Địa danh ngày tháng năm', 'DiaDanh & NgayThangNam'),
    (r'[\.…._{—–-]{3,},\s*ngày\s*[\.…._{—–-]+\s*tháng\s*[\.…._{—–-]+\s*năm[\.…._{—–-]+', '${DiaDanh}, ${NgayThangNam}', 'Địa danh ngày tháng năm', 'DiaDanh & NgayThangNam'),
    (r'Đồng\s+Tháp,\s*ngày[\.…._{—–-]+\s*tháng[\.…._{—–-]+\s*năm[\.…._{—–-]+', 'Đồng Tháp, ${NgayThangNam}', 'Ngày tháng năm bằng chữ', 'NgayThangNam'),
    (r'ngày\s*[\.…._{—–-]{2,}\s*tháng\s*[\.…._{—–-]{2,}\s*năm\s*20[\.…._{—–-]+', '${NgayThangNam}', 'Ngày tháng năm bằng chữ', 'NgayThangNam'),
    (r'ngày\s*[\.…._{—–-]{2,}\s*tháng\s*[\.…._{—–-]{2,}\s*năm\s*[\.…._{—–-]+', '${NgayThangNam}', 'Ngày tháng năm bằng chữ', 'NgayThangNam'),
    (r'ngày\s*…+\s*/\s*…+\s*/\s*20…+', '${NgayYeuCau}', 'Ngày yêu cầu', 'NgayYeuCau'),
    (r'ngày\s*…+\s*/\s*…+\s*/\s*\.+', '${NgayYeuCau}', 'Ngày yêu cầu', 'NgayYeuCau'),
]

# Blank label rules (colon at end of line without dots)
blank_label_tag_rules = [
    (r'^(Mã\s+đơn\s+vị\s+trên\s+IPCAS\s*[:：])\s*$', r'\1 ${MaChiNhanh}', 'Mã đơn vị trên IPCAS', 'MaChiNhanh'),
    (r'^(Họ\s+(?:và\s+)?tên\s*(?:người\s+dùng)?\s*[:：])\s*$', r'\1 ${TenCanBo}', 'Họ và tên người dùng', 'TenCanBo'),
    (r'^(Chức\s+vụ\s*[:：])\s*$', r'\1 ${ChucVu}', 'Chức vụ', 'ChucVu'),
    (r'^(Đơn\s+vị\s+công\s+tác\s*[:：])\s*$', r'\1 ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh'),
    (r'^(Số\s+điện\s+thoại\s*(?:liên\s+hệ)?\s*[:：])\s*$', r'\1 ${SDT}', 'Số điện thoại', 'SDT'),
    (r'^(Địa\s+chỉ\s+thư\s+điện\s+tử\s*[:：])\s*$', r'\1 ${EmailNV}', 'Địa chỉ thư điện tử', 'EmailNV'),
    (r'^(Đề\s+nghị\s+bộ\s+phận\s+quản\s+trị\s+hệ\s+thống\s+đổi\s+lại\s+mật\s+khẩu\s+của\s+người\s+dùng\s*[:：])\s*$', r'\1 ${IPCAS}', 'Đổi mật khẩu người dùng', 'IPCAS'),
    (r'^(Lý\s+do\s+đổi\s+lại\s*[:：])\s*$', r'\1 ${LyDo}', 'Lý do đổi lại', 'LyDo'),
]

# Replacements for files with hardcoded sample data to convert into TAGs
hardcoded_to_tag_rules = [
    # Mau-CSUS-09_Doi-Mat-Khau-IPCAS.docx
    (r'Họ và tên người dùng:\s*Vũ Quang Thịnh', 'Họ và tên người dùng: ${TenCanBo}', 'Họ và tên người dùng', 'TenCanBo'),
    (r'Chức vụ:\s*Phó phòng Kế hoạch và QLRR Agribank Đồng Tháp', 'Chức vụ: ${ChucVu}', 'Chức vụ', 'ChucVu'),
    (r'Đơn vị công tác:\s*Agribank chi nhánh Đồng Tháp', 'Đơn vị công tác: ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh'),
    (r'Số điện thoại liên hệ:\s*0902332020', 'Số điện thoại liên hệ: ${SDT}', 'Số điện thoại liên hệ', 'SDT'),
    (r'Địa chỉ thư điện tử:\s*thinhvuquang1@agribank.com.vn', 'Địa chỉ thư điện tử: ${EmailNV}', 'Địa chỉ thư điện tử', 'EmailNV'),
    (r'Mã đơn vị trên IPCAS:\s*6500', 'Mã đơn vị trên IPCAS: ${MaChiNhanh}', 'Mã đơn vị trên IPCAS', 'MaChiNhanh'),
    (r'đổi lại mật khẩu của người dùng:\s*DTHTHINH', 'đổi lại mật khẩu của người dùng: ${IPCAS}', 'Mã người dùng đổi mật khẩu', 'IPCAS'),
    (r'ngày 10 tháng 10 năm 2025', '${NgayThangNam}', 'Ngày tháng năm', 'NgayThangNam'),
    (r'ngày 22 tháng 09 năm 2026', '${NgayThangNam}', 'Ngày tháng năm', 'NgayThangNam'),
    
    # Mau-13A-PKI_Doi-Mat-Khau-PKI.docx
    (r'Họ và tên người sử dụng:\s*Nguyễn Văn A\.', 'Họ và tên người sử dụng: ${TenCanBo}', 'Họ và tên người sử dụng', 'TenCanBo'),
    (r'Chức vụ:\s*Nhân viên phòng\s+KT&NQ\.', 'Chức vụ: ${ChucVu}', 'Chức vụ', 'ChucVu'),
    (r'Đơn vị công tác:\s*Agribank chi nhánh Đồng Tháp\.', 'Đơn vị công tác: ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh'),
    (r'Số điện thoại liên hệ:\s*02773\s*822\s*174', 'Số điện thoại liên hệ: ${SDT}', 'Số điện thoại liên hệ', 'SDT'),
    (r'Mã chi nhánh trên IPCAS:\s*6500', 'Mã chi nhánh trên IPCAS: ${MaChiNhanh}', 'Mã chi nhánh trên IPCAS', 'MaChiNhanh'),
    
    # Mau-15-TT
    (r'Võ Thị Cẩm Loan', '${TenCanBo}', 'Họ và tên', 'TenCanBo'),
    (r'Phó Phòng KTNQ', '${ChucVu}', 'Chức vụ', 'ChucVu'),
    (r'CT3VCLOAN', '${IPCAS}', 'User IPCAS', 'IPCAS'),
    
    # Mau-3A-PKI
    (r'Họ và tên\s*:\s*\tPhòng\s*:', 'Họ và tên : ${TenCanBo}\tPhòng: ${PhongBan}', 'Họ và tên & Phòng', 'TenCanBo & PhongBan'),
    (r'Đơn vị công tác\s*:\s*\tSố điện thoại\s*:', 'Đơn vị công tác: ${TenChiNhanh}\tSố điện thoại: ${SDT}', 'Đơn vị & Số điện thoại', 'TenChiNhanh & SDT'),
    (r'^Email\s*:\s*$', 'Email: ${EmailNV}', 'Email', 'EmailNV'),
    
    # Mau-1B-PKI & Mau-2B-PKI
    (r'Tên giao dịch đầy đủ bằng tiếng Việt \(VIẾT HOA\)\s*:\s*$', 'Tên giao dịch đầy đủ bằng tiếng Việt (VIẾT HOA): ${TenChiNhanhHOA}', 'Tên giao dịch đầy đủ', 'TenChiNhanhHOA'),
    (r'Họ và tên\s*:\s*\t+Chức vụ\s*:\s*$', 'Họ và tên : ${TenCanBo}\t\tChức vụ: ${ChucVu}', 'Họ và tên & Chức vụ', 'TenCanBo & ChucVu'),
    (r'Họ và tên\s*:\s*\t+Chức vụ/bộ phận\s*:\s*$', 'Họ và tên: ${TenCanBo}\tChức vụ/bộ phận: ${ChucVu}', 'Họ và tên & Chức vụ', 'TenCanBo & ChucVu'),
    (r'Email \(Tổ chức\)\s*;\s*\t+Di động\s*:\s*$', 'Email (Tổ chức): ${EmailNV}\t\tDi động: ${SDT}', 'Email & Di động', 'EmailNV & SDT'),
    (r'Kính gửi:\s*Ngân hàng Agribank chi nhánh \.\.\.', 'Kính gửi: Ngân hàng Agribank chi nhánh ${TenChiNhanh}', 'Kính gửi chi nhánh', 'TenChiNhanh'),
    (r'^Tên tổ chức\s*:\s*$', 'Tên tổ chức: ${TenChiNhanh}', 'Tên tổ chức', 'TenChiNhanh'),
    (r'^Đại diện\s*:\s*$', 'Đại diện: ${TenCanBo}', 'Đại diện', 'TenCanBo'),
]

# Untagged fields needing confirmation (to include in synthesis table)
UNCONFIRMED_FIELDS = [
    'User AD', 'Mã liên ngân hàng', 'Mã người dùng SmartForm', 
    'Mã người dùng BE-ADMIN', 'Phương thức xác thực (PKI/SMS/IP…)',
    'Nhóm chức năng / Quyền nghiệp vụ', 'Tên đăng nhập TMS',
    'Số Serial thiết bị bảo mật', 'Kính gửi', 'Số công văn'
]

print("Rules configured.")
