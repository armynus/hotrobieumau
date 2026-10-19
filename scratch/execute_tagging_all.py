import os
import sys
import re
import json
import docx

sys.stdout.reconfigure(encoding='utf-8')

src_dir = r'D:\hotrobieumau\Mau Bieu IT'
dst_dir = r'D:\hotrobieumau\Mau Bieu IT\File da gan tag'

os.makedirs(dst_dir, exist_ok=True)

# 25 Official System Tags in `it_support_form_fields`
OFFICIAL_TAGS_INFO = {
    'TenCanBo': {
        'name': 'Họ và tên cán bộ yêu cầu',
        'source': 'users.name / Session user_name',
        'type': 'text'
    },
    'TenCanBoHOA': {
        'name': 'Họ và tên cán bộ (in hoa)',
        'source': 'mb_strtoupper(users.name)',
        'type': 'derived'
    },
    'SDT': {
        'name': 'Số điện thoại cá nhân',
        'source': 'users.phone_no',
        'type': 'tel'
    },
    'SDTCoQuan': {
        'name': 'Số điện thoại cơ quan',
        'source': 'branches.branch_phone',
        'type': 'tel'
    },
    'ChucVu': {
        'name': 'Chức vụ',
        'source': 'positions.position_name',
        'type': 'text'
    },
    'MaChiNhanh': {
        'name': 'Mã chi nhánh',
        'source': 'branches.branch_code / Session UserBranchCode',
        'type': 'text'
    },
    'TenChiNhanh': {
        'name': 'Tên chi nhánh / Đơn vị',
        'source': 'branches.branch_name / Session UserBranchName',
        'type': 'text'
    },
    'TenChiNhanhHOA': {
        'name': 'Tên chi nhánh (in hoa)',
        'source': 'mb_strtoupper(branches.branch_name)',
        'type': 'derived'
    },
    'ChiNhanhHOA': {
        'name': 'Tên chi nhánh (in hoa)',
        'source': 'mb_strtoupper(branches.branch_name)',
        'type': 'derived'
    },
    'PhongBan': {
        'name': 'Phòng ban / Bộ phận',
        'source': 'departments.department_code / department_name',
        'type': 'text'
    },
    'MaPhong': {
        'name': 'Mã phòng ban',
        'source': 'departments.department_code',
        'type': 'derived'
    },
    'TenChuongTrinh': {
        'name': 'Tên Chương trình / Phần mềm',
        'source': 'Người dùng chọn / nhập trên form',
        'type': 'text'
    },
    'DiaChiIP': {
        'name': 'Địa chỉ IP máy tính',
        'source': 'request()->ip() / cache DiaChiIP',
        'type': 'text'
    },
    'MaTaiSan': {
        'name': 'Mã tài sản / Số máy',
        'source': 'Người dùng nhập trên form',
        'type': 'text'
    },
    'TenHeThong': {
        'name': 'Tên hệ thống thông tin',
        'source': 'Người dùng chọn trên form',
        'type': 'text'
    },
    'TieuDeYeuCau': {
        'name': 'Tiêu đề yêu cầu / Sự cố',
        'source': 'Người dùng nhập trên form',
        'type': 'text'
    },
    'NoiDungYeuCau': {
        'name': 'Nội dung chi tiết yêu cầu',
        'source': 'Người dùng nhập trên form',
        'type': 'textarea'
    },
    'LyDo': {
        'name': 'Lý do đề nghị',
        'source': 'Người dùng nhập trên form',
        'type': 'textarea'
    },
    'NgayYeuCau': {
        'name': 'Ngày yêu cầu (dạng số dd/mm/yyyy)',
        'source': 'Carbon::now()->format("d/m/Y")',
        'type': 'date'
    },
    'CanBoIT': {
        'name': 'Cán bộ IT tiếp nhận',
        'source': 'Cán bộ IT xử lý nhập',
        'type': 'text'
    },
    'KetQuaXuLy': {
        'name': 'Kết quả xử lý',
        'source': 'Cán bộ IT xử lý nhập',
        'type': 'textarea'
    },
    'LanhDaoPheDuyet': {
        'name': 'Lãnh đạo đơn vị phê duyệt',
        'source': 'Lãnh đạo ký duyệt',
        'type': 'text'
    },
    'DiaDanh': {
        'name': 'Địa danh chi nhánh',
        'source': 'branches.branch_place / Session UserBranchPlace',
        'type': 'text'
    },
    'CCCD': {
        'name': 'CCCD/CMND Cán bộ',
        'source': 'users.citizen_id / cache CCCD',
        'type': 'text'
    },
    'MaNV': {
        'name': 'Mã số nhân viên',
        'source': 'users.employee_code',
        'type': 'text'
    },
    'EmailNV': {
        'name': 'Email nhân viên',
        'source': 'users.email / Session user_email',
        'type': 'text'
    },
    'IPCAS': {
        'name': 'Mã người dùng IPCAS',
        'source': 'users.user_ipcas / cache IPCAS',
        'type': 'text'
    },
    'TenThietBi': {
        'name': 'Tên thiết bị / Phần mềm',
        'source': 'Người dùng nhập trên form',
        'type': 'text'
    },
    'NgayThangNam': {
        'name': 'Ngày tháng năm bằng chữ',
        'source': 'SupportFormService::formatDateInWords(NgayYeuCau)',
        'type': 'date_text'
    }
}

# Regex rules for paragraph text replacement
PARAGRAPH_RULES = [
    # Units
    (r'(Đơn\s+vị\s+yêu\s+cầu\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChiNhanh}', 'Đơn vị yêu cầu', 'TenChiNhanh', 'Đoạn văn nhãn đơn vị'),
    (r'(Đơn\s+vị\s+công\s+tác\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh', 'Đoạn văn nhãn đơn vị'),
    (r'(Tên\s+đơn\s+vị\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChiNhanh}', 'Tên đơn vị', 'TenChiNhanh', 'Đoạn văn nhãn đơn vị'),
    (r'(Mã\s+chi\s+nhánh\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'Mã chi nhánh', 'MaChiNhanh', 'Đoạn văn mã chi nhánh'),
    (r'(Mã\s+đơn\s+vị\s+trên\s+IPCAS\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'Mã đơn vị trên IPCAS', 'MaChiNhanh', 'Đoạn văn mã IPCAS chi nhánh'),
    (r'(Mã\s+chi\s+nhánh\s+trên\s+IPCAS\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'Mã chi nhánh trên IPCAS', 'MaChiNhanh', 'Đoạn văn mã IPCAS chi nhánh'),
    (r'(BranchID\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaChiNhanh}', 'BranchID', 'MaChiNhanh', 'Đoạn văn mã chi nhánh'),

    # User & Requester
    (r'(Họ\s+(?:và\s+)?tên\s+Người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên Người dùng', 'TenCanBo', 'Đoạn văn người dùng'),
    (r'(Họ\s+(?:và\s+)?tên\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên người dùng', 'TenCanBo', 'Đoạn văn người dùng'),
    (r'(Họ\s+(?:và\s+)?tên\s+người\s+sử\s+dụng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên người sử dụng', 'TenCanBo', 'Đoạn văn người sử dụng'),
    (r'(Họ\s+và\s+tên\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Họ và tên', 'TenCanBo', 'Đoạn văn họ tên'),
    (r'(Người\s+liên\s+hệ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenCanBo}', 'Người liên hệ', 'TenCanBo', 'Đoạn văn người liên hệ'),
    (r'(Chức\s+vụ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${ChucVu}', 'Chức vụ', 'ChucVu', 'Đoạn văn chức vụ'),
    (r'(Phòng\s*(?:ban)?\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${PhongBan}', 'Phòng ban', 'PhongBan', 'Đoạn văn phòng ban'),
    (r'(Bộ\s+phận\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${PhongBan}', 'Bộ phận', 'PhongBan', 'Đoạn văn phòng ban'),
    (r'(Số\s+điện\s+thoại\s+liên\s+hệ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Số điện thoại liên hệ', 'SDT', 'Đoạn văn số điện thoại'),
    (r'(Số\s+điện\s+thoại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Số điện thoại', 'SDT', 'Đoạn văn số điện thoại'),
    (r'(Di\s+động\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Di động', 'SDT', 'Đoạn văn số điện thoại'),
    (r'(Điện\s+thoại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${SDT}', 'Điện thoại', 'SDT', 'Đoạn văn số điện thoại'),
    (r'(Email\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${EmailNV}', 'Email', 'EmailNV', 'Đoạn văn email'),
    (r'(Địa\s+chỉ\s+thư\s+điện\s+tử\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${EmailNV}', 'Địa chỉ thư điện tử', 'EmailNV', 'Đoạn văn email'),
    (r'(User\s+ID\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${IPCAS}', 'User ID', 'IPCAS', 'Đoạn văn mã người dùng'),
    (r'(Mã\s+người\s+dùng\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${IPCAS}', 'Mã người dùng', 'IPCAS', 'Đoạn văn mã người dùng'),
    (r'(mã\s+người\s+dùng\s+là\s*)[\.…._{—–-]{3,}', r'\1${IPCAS}', 'mã người dùng là', 'IPCAS', 'Đoạn văn mã người dùng'),
    (r'(Mã\s+(?:số\s+)?nhân\s+viên\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${MaNV}', 'Mã số nhân viên', 'MaNV', 'Đoạn văn mã nhân viên'),
    (r'(Số\s+CMND\/CCCD\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${CCCD}', 'Số CMND/CCCD', 'CCCD', 'Đoạn văn CCCD'),
    (r'(Số\s+CCCD\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${CCCD}', 'Số CCCD', 'CCCD', 'Đoạn văn CCCD'),

    # Request & System
    (r'(Tên\s+Phần\s+mềm\s+nghiệp\s+vụ\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChuongTrinh}', 'Tên Phần mềm nghiệp vụ', 'TenChuongTrinh', 'Đoạn văn tên phần mềm'),
    (r'(Tên\s+chương\s+trình\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${TenChuongTrinh}', 'Tên chương trình', 'TenChuongTrinh', 'Đoạn văn tên chương trình'),
    (r'(Lý\s+do\s+đổi\s+lại\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${LyDo}', 'Lý do đổi lại', 'LyDo', 'Đoạn văn lý do'),
    (r'(Lý\s+do(?:\s*\(\d+\))?\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${LyDo}', 'Lý do', 'LyDo', 'Đoạn văn lý do'),
    (r'(Yêu\s+cầu\s+cụ\s+thể\s+như\s+sau\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 ${NoiDungYeuCau}', 'Yêu cầu cụ thể như sau', 'NoiDungYeuCau', 'Đoạn văn nội dung yêu cầu'),
    (r'(Thông\s+tin\s+liên\s+hệ\s+của\s+cán\s+bộ\s+yêu\s+cầu[^\(]*\(SĐT,\s*email\)\s*[:：])\s*[\.…._{—–-]{3,}', r'\1 SĐT: ${SDT}, Email: ${EmailNV}', 'Thông tin liên hệ SĐT, email', 'SDT / EmailNV', 'Đoạn văn liên hệ'),

    # Organization Bracket Placeholders in headers
    (r'<CHI NHÁNH\s*>', '${TenChiNhanhHOA}', '<CHI NHÁNH>', 'TenChiNhanhHOA', 'Tiêu đề đơn vị cấp trên'),
    (r'<ĐƠN VỊ\s*>', '${TenChiNhanhHOA}', '<ĐƠN VỊ>', 'TenChiNhanhHOA', 'Tiêu đề đơn vị cấp trên'),
    (r'<Chi\s+nhánh\s*>', '${TenChiNhanhHOA}', '<Chi nhánh>', 'TenChiNhanhHOA', 'Tiêu đề đơn vị cấp trên'),
    (r'<Đơn\s+vị\s*>', '${TenChiNhanhHOA}', '<Đơn vị>', 'TenChiNhanhHOA', 'Tiêu đề đơn vị cấp trên'),
    (r'<\s*TÊN ĐƠN VỊ\s*>', '${TenChiNhanhHOA}', '< TÊN ĐƠN VỊ >', 'TenChiNhanhHOA', 'Tiêu đề đơn vị cấp trên'),

    # Date and place
    (r'[\.…._{—–-]{3,},\s*ngày\s*[\.…._{—–-]+\s*tháng\s*[\.…._{—–-]+\s*năm\s*20[\.…._{—–-]+', '${DiaDanh}, ${NgayThangNam}', 'Địa danh ngày tháng năm', 'DiaDanh / NgayThangNam', 'Dòng ngày tháng năm'),
    (r'[\.…._{—–-]{3,},\s*ngày\s*[\.…._{—–-]+\s*tháng\s*[\.…._{—–-]+\s*năm[\.…._{—–-]+', '${DiaDanh}, ${NgayThangNam}', 'Địa danh ngày tháng năm', 'DiaDanh / NgayThangNam', 'Dòng ngày tháng năm'),
    (r'Đồng\s+Tháp,\s*ngày[\.…._{—–-]+\s*tháng[\.…._{—–-]+\s*năm[\.…._{—–-]+', 'Đồng Tháp, ${NgayThangNam}', 'Ngày tháng năm bằng chữ', 'NgayThangNam', 'Dòng ngày tháng năm'),
    (r'ngày\s*[\.…._{—–-]{2,}\s*tháng\s*[\.…._{—–-]{2,}\s*năm\s*20[\.…._{—–-]+', '${NgayThangNam}', 'Ngày tháng năm bằng chữ', 'NgayThangNam', 'Dòng ngày tháng năm'),
    (r'ngày\s*[\.…._{—–-]{2,}\s*tháng\s*[\.…._{—–-]{2,}\s*năm\s*[\.…._{—–-]+', '${NgayThangNam}', 'Ngày tháng năm bằng chữ', 'NgayThangNam', 'Dòng ngày tháng năm'),
    (r'ngày\s*…+\s*/\s*…+\s*/\s*20…+', '${NgayYeuCau}', 'Ngày yêu cầu', 'NgayYeuCau', 'Dòng ngày yêu cầu số'),
    (r'ngày\s*…+\s*/\s*…+\s*/\s*\.+', '${NgayYeuCau}', 'Ngày yêu cầu', 'NgayYeuCau', 'Dòng ngày yêu cầu số'),
]

# Blank labels without trailing dots
BLANK_LABEL_RULES = [
    (r'^(Mã\s+đơn\s+vị\s+trên\s+IPCAS\s*[:：])\s*$', r'\1 ${MaChiNhanh}', 'Mã đơn vị trên IPCAS', 'MaChiNhanh', 'Dòng nhãn mã IPCAS chi nhánh'),
    (r'^(Đơn\s+vị\s+yêu\s+cầu\s*[:：])\s*$', r'\1 ${TenChiNhanh}', 'Đơn vị yêu cầu', 'TenChiNhanh', 'Dòng nhãn đơn vị yêu cầu'),
    (r'^(Họ\s+(?:và\s+)?tên\s*(?:người\s+dùng)?\s*[:：])\s*$', r'\1 ${TenCanBo}', 'Họ và tên người dùng', 'TenCanBo', 'Dòng nhãn họ tên'),
    (r'^(Chức\s+vụ\s*[:：])\s*$', r'\1 ${ChucVu}', 'Chức vụ', 'ChucVu', 'Dòng nhãn chức vụ'),
    (r'^(Đơn\s+vị\s+công\s+tác\s*[:：])\s*$', r'\1 ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh', 'Dòng nhãn đơn vị'),
    (r'^(Số\s+điện\s+thoại\s*(?:liên\s+hệ)?\s*[:：])\s*$', r'\1 ${SDT}', 'Số điện thoại', 'SDT', 'Dòng nhãn số điện thoại'),
    (r'^(Địa\s+chỉ\s+thư\s+điện\s+tử\s*[:：])\s*$', r'\1 ${EmailNV}', 'Địa chỉ thư điện tử', 'EmailNV', 'Dòng nhãn email'),
    (r'^(Đề\s+nghị\s+bộ\s+phận\s+quản\s+trị\s+hệ\s+thống\s+đổi\s+lại\s+mật\s+khẩu\s+của\s+người\s+dùng\s*[:：])\s*$', r'\1 ${IPCAS}', 'Đổi mật khẩu người dùng', 'IPCAS', 'Dòng nhãn đổi mật khẩu'),
    (r'^(Lý\s+do\s+đổi\s+lại\s*[:：])\s*$', r'\1 ${LyDo}', 'Lý do đổi lại', 'LyDo', 'Dòng nhãn lý do'),
]

# Hardcoded sample data found in some original files
HARDCODED_DATA_RULES = [
    # Mau-CSUS-09_Doi-Mat-Khau-IPCAS.docx
    (r'Họ và tên người dùng:\s*Vũ Quang Thịnh', 'Họ và tên người dùng: ${TenCanBo}', 'Họ và tên người dùng', 'TenCanBo', 'Dòng họ tên người dùng'),
    (r'Chức vụ:\s*Phó phòng Kế hoạch và QLRR Agribank Đồng Tháp', 'Chức vụ: ${ChucVu}', 'Chức vụ', 'ChucVu', 'Dòng chức vụ'),
    (r'Đơn vị công tác:\s*Agribank chi nhánh Đồng Tháp', 'Đơn vị công tác: ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh', 'Dòng đơn vị công tác'),
    (r'Số điện thoại liên hệ:\s*0902332020', 'Số điện thoại liên hệ: ${SDT}', 'Số điện thoại liên hệ', 'SDT', 'Dòng số điện thoại'),
    (r'Địa chỉ thư điện tử:\s*thinhvuquang1@agribank.com.vn', 'Địa chỉ thư điện tử: ${EmailNV}', 'Địa chỉ thư điện tử', 'EmailNV', 'Dòng email'),
    (r'Mã đơn vị trên IPCAS:\s*6500', 'Mã đơn vị trên IPCAS: ${MaChiNhanh}', 'Mã đơn vị trên IPCAS', 'MaChiNhanh', 'Dòng mã đơn vị IPCAS'),
    (r'đổi lại mật khẩu của người dùng:\s*DTHTHINH', 'đổi lại mật khẩu của người dùng: ${IPCAS}', 'Mã người dùng đổi mật khẩu', 'IPCAS', 'Dòng mã người dùng'),
    (r'Đồng Tháp,\s*ngày 10 tháng 10 năm 2025', '${DiaDanh}, ${NgayThangNam}', 'Địa danh ngày tháng năm', 'DiaDanh / NgayThangNam', 'Dòng địa danh ngày tháng'),
    (r'ngày 10 tháng 10 năm 2025', '${NgayThangNam}', 'Ngày tháng năm', 'NgayThangNam', 'Dòng ngày tháng'),
    (r'ngày 22 tháng 09 năm 2026', '${NgayThangNam}', 'Ngày tháng năm', 'NgayThangNam', 'Dòng ngày tháng'),

    # Mau-13A-PKI_Doi-Mat-Khau-PKI.docx
    (r'Họ và tên người sử dụng:\s*Nguyễn Văn A\.', 'Họ và tên người sử dụng: ${TenCanBo}', 'Họ và tên người sử dụng', 'TenCanBo', 'Dòng họ tên người sử dụng'),
    (r'Nhân viên phòng\s+KT&NQ\.', '${ChucVu}', 'Chức vụ', 'ChucVu', 'Dòng chức vụ'),
    (r'Đơn vị công tác:\s*Agribank chi nhánh Đồng Tháp\.', 'Đơn vị công tác: ${TenChiNhanh}', 'Đơn vị công tác', 'TenChiNhanh', 'Dòng đơn vị công tác'),
    (r'Số điện thoại liên hệ:\s*02773\s*822\s*174', 'Số điện thoại liên hệ: ${SDT}', 'Số điện thoại liên hệ', 'SDT', 'Dòng số điện thoại'),
    (r'Mã chi nhánh trên IPCAS:\s*6500', 'Mã chi nhánh trên IPCAS: ${MaChiNhanh}', 'Mã chi nhánh trên IPCAS', 'MaChiNhanh', 'Dòng mã chi nhánh IPCAS'),

    # Mau-15-TT.docx
    (r'Võ Thị Cẩm Loan', '${TenCanBo}', 'Họ và tên cán bộ', 'TenCanBo', 'Dòng họ tên cán bộ'),
    (r'Phó Phòng KTNQ', '${ChucVu}', 'Chức vụ cán bộ', 'ChucVu', 'Dòng chức vụ'),
    (r'CT3VCLOAN', '${IPCAS}', 'User IPCAS', 'IPCAS', 'Dòng User IPCAS'),

    # Mau-3A-PKI
    (r'Họ và tên\s*:\s*\tPhòng\s*:', 'Họ và tên : ${TenCanBo}\tPhòng: ${PhongBan}', 'Họ và tên & Phòng', 'TenCanBo / PhongBan', 'Dòng Họ và tên & Phòng'),
    (r'Đơn vị công tác\s*:\s*\tSố điện thoại\s*:', 'Đơn vị công tác: ${TenChiNhanh}\tSố điện thoại: ${SDT}', 'Đơn vị & Số điện thoại', 'TenChiNhanh / SDT', 'Dòng Đơn vị & SĐT'),
    (r'^Email\s*:\s*$', 'Email: ${EmailNV}', 'Email', 'EmailNV', 'Dòng email'),

    # Mau-1B-PKI & Mau-2B-PKI
    (r'Tên giao dịch đầy đủ bằng tiếng Việt \(VIẾT HOA\)\s*:\s*$', 'Tên giao dịch đầy đủ bằng tiếng Việt (VIẾT HOA): ${TenChiNhanhHOA}', 'Tên giao dịch đầy đủ', 'TenChiNhanhHOA', 'Dòng tên giao dịch tổ chức'),
    (r'Họ và tên\s*:\s*\t+Chức vụ\s*:\s*$', 'Họ và tên : ${TenCanBo}\t\tChức vụ: ${ChucVu}', 'Họ và tên & Chức vụ', 'TenCanBo / ChucVu', 'Dòng họ tên & chức vụ'),
    (r'Họ và tên\s*:\s*\t+Chức vụ/bộ phận\s*:\s*$', 'Họ và tên: ${TenCanBo}\tChức vụ/bộ phận: ${ChucVu}', 'Họ và tên & Chức vụ', 'TenCanBo / ChucVu', 'Dòng họ tên & chức vụ'),
    (r'Email \(Tổ chức\)\s*;\s*\t+Di động\s*:\s*$', 'Email (Tổ chức): ${EmailNV}\t\tDi động: ${SDT}', 'Email & Di động', 'EmailNV / SDT', 'Dòng email & di động'),
    (r'Kính gửi:\s*Ngân hàng Agribank chi nhánh \.\.\.', 'Kính gửi: Ngân hàng Agribank chi nhánh ${TenChiNhanh}', 'Kính gửi chi nhánh', 'TenChiNhanh', 'Dòng kính gửi'),
    (r'^Tên tổ chức\s*:\s*$', 'Tên tổ chức: ${TenChiNhanh}', 'Tên tổ chức', 'TenChiNhanh', 'Dòng tên tổ chức'),
    (r'^Đại diện\s*:\s*$', 'Đại diện: ${TenCanBo}', 'Đại diện', 'TenCanBo', 'Dòng đại diện')
]

# Unconfirmed fields that have NO system tag in `it_support_form_fields`
UNCONFIRMED_FIELD_PATTERNS = [
    ('User AD / Tài khoản AD', ['user ad', 'tài khoản ad', 'mã đăng nhập', 'user name']),
    ('Mã liên ngân hàng', ['mã liên ngân hàng', 'mã lnh']),
    ('Mã người dùng SmartForm', ['mã người dùng smartform', 'user smartform']),
    ('Mã người dùng BE-ADMIN', ['mã người dùng be-admin', 'user beadmin', 'người quản trị be-admin']),
    ('Phương thức xác thực', ['phương thức xác thực', 'pki/sms/ip']),
    ('Nhóm chức năng / Quyền nghiệp vụ', ['nhóm chức năng', 'chức năng giao dịch', 'quyền nghiệp vụ', 'nhóm quyền', 'phân quyền']),
    ('Tên đăng nhập TMS', ['tms']),
    ('Số Serial Token / Chứng thư số', ['serial', 'số seri', 'số serial']),
    ('Ngày cấp CCCD/CMND', ['ngày cấp']),
    ('Nơi cấp CCCD/CMND', ['nơi cấp']),
    ('Số công văn / Tờ trình', ['số:............', 'số ……………']),
    ('Kính gửi (Đơn vị nhận)', ['kính gửi:'])
]

def apply_paragraph_replacement(p, file_rel, report_records, unconfirmed_records):
    orig_text = p.text
    if not orig_text.strip():
        return
    
    current_t = orig_text
    
    # 1. Check hardcoded data rules
    for pat, rep, field_name, tag, loc_desc in HARDCODED_DATA_RULES:
        if re.search(pat, current_t, re.IGNORECASE):
            current_t = re.sub(pat, rep, current_t, flags=re.IGNORECASE)
            report_records.append({
                'file': file_rel,
                'field_label': field_name,
                'tag': f"${{{tag}}}",
                'location': loc_desc,
                'source': OFFICIAL_TAGS_INFO.get(tag, {}).get('source', 'Hệ thống IT'),
                'status': 'Đã xác định'
            })
            
    # 2. Check paragraph rules
    for pat, rep, field_name, tag, loc_desc in PARAGRAPH_RULES:
        if re.search(pat, current_t, re.IGNORECASE):
            # Check multiple tags if combined
            current_t = re.sub(pat, rep, current_t, flags=re.IGNORECASE)
            tags_list = tag.split(' / ')
            for t_item in tags_list:
                report_records.append({
                    'file': file_rel,
                    'field_label': field_name,
                    'tag': f"${{{t_item}}}",
                    'location': loc_desc,
                    'source': OFFICIAL_TAGS_INFO.get(t_item, {}).get('source', 'Hệ thống IT'),
                    'status': 'Đã xác định'
                })
                
    # 3. Check blank label rules
    for pat, rep, field_name, tag, loc_desc in BLANK_LABEL_RULES:
        if re.search(pat, current_t.strip(), re.IGNORECASE):
            current_t = re.sub(pat, rep, current_t.strip(), flags=re.IGNORECASE)
            report_records.append({
                'file': file_rel,
                'field_label': field_name,
                'tag': f"${{{tag}}}",
                'location': loc_desc,
                'source': OFFICIAL_TAGS_INFO.get(tag, {}).get('source', 'Hệ thống IT'),
                'status': 'Đã xác định'
            })

    # 4. Check for unconfirmed fields in paragraph
    for unconf_label, kws in UNCONFIRMED_FIELD_PATTERNS:
        if any(kw in current_t.lower() for kw in kws) and not any(tag in current_t for tag in ['${TenCanBo}', '${TenChiNhanh}', '${MaChiNhanh}', '${IPCAS}', '${SDT}', '${EmailNV}']):
            if any(term in current_t.lower() for term in ['..........', '…………', ':']):
                unconfirmed_records.append({
                    'file': file_rel,
                    'field_label': unconf_label,
                    'tag': '(Chưa hỗ trợ TAG)',
                    'location': f'Đoạn văn: {current_t[:60]}...',
                    'source': 'Chưa cấu hình trong bảng it_support_form_fields',
                    'status': 'Cần xác nhận'
                })
            
    # If text changed, update runs preserving formatting
    if current_t != orig_text:
        if p.runs:
            p.runs[0].text = current_t
            for r in p.runs[1:]:
                r.text = ''
        else:
            p.text = current_t


def process_tables(doc, file_rel, report_records, unconfirmed_records):
    for t_idx, table in enumerate(doc.tables):
        if len(table.rows) < 2:
            continue
            
        header_row = [c.text.strip().replace('\n', ' ') for c in table.rows[0].cells]
        header_str = " | ".join(header_row).lower()
        
        # Check if table is a header meta table (like Unit & Date in Table 0)
        if any(k in header_str for k in ['ngân hàng nông nghiệp', 'cộng hòa xã hội']):
            for r in table.rows:
                for c_idx, cell in enumerate(r.cells):
                    for p in cell.paragraphs:
                        apply_paragraph_replacement(p, file_rel, report_records, unconfirmed_records)
            continue
            
        # Check if table is a signature table (e.g. Lập biểu, Người dùng)
        if any(k in header_str for k in ['lập biểu', 'người dùng', 'người yêu cầu', 'người đề nghị', 'người lập']):
            # Look for row 1 or 2 where user name signature line is
            for r_idx in range(1, len(table.rows)):
                row = table.rows[r_idx]
                for c_idx, cell in enumerate(row.cells):
                    h_cell = header_row[c_idx].lower() if c_idx < len(header_row) else ''
                    if any(k in h_cell for k in ['lập biểu', 'người dùng', 'người yêu cầu', 'người đề nghị', 'người lập']) and not any(k in h_cell for k in ['thủ trưởng', 'giám đốc', 'kiểm soát', 'trưởng phòng']):
                        txt = cell.text.strip()
                        if not txt or txt == '...' or txt.startswith('('):
                            # Add signature tag
                            if not '${TenCanBo}' in cell.text:
                                p = cell.add_paragraph() if not cell.paragraphs else cell.paragraphs[-1]
                                if not '${TenCanBo}' in p.text:
                                    p.text = (p.text + '\n${TenCanBo}').strip()
                                report_records.append({
                                    'file': file_rel,
                                    'field_label': 'Chữ ký / Họ tên người lập',
                                    'tag': '${TenCanBo}',
                                    'location': f'Bảng {t_idx+1}, Cột "{header_row[c_idx]}", Dòng {r_idx+1}',
                                    'source': OFFICIAL_TAGS_INFO['TenCanBo']['source'],
                                    'status': 'Đã xác định'
                                })
            continue
            
        # Check if table is a data registration table
        if not any(k in header_str for k in ['họ và tên', 'họ tên', 'mã số nhân viên', 'mã nhân viên', 'user ipcas', 'mã người dùng', 'chức vụ', 'user ad']):
            continue
            
        # Determine start row for data filling
        start_row = 1
        if len(table.rows) > 2 and re.match(r'^\s*\(?\s*1(?:\.1)?\s*\)?\s*$', table.rows[1].cells[0].text.strip()):
            start_row = 2
            
        for r_idx in range(start_row, min(start_row + 1, len(table.rows))):
            row = table.rows[r_idx]
            for c_idx, cell in enumerate(row.cells):
                if c_idx >= len(header_row):
                    break
                h = header_row[c_idx].lower()
                cell_txt = cell.text.strip()
                
                # Check if cell already has variable
                if '${' in cell_txt:
                    continue
                    
                target_tag = None
                target_label = None
                unconf_tag = None
                
                if h in ['stt', 'tt', 'stt (1)', 'stt (1.1)']:
                    if not cell_txt or cell_txt in ['.', '..', '...']:
                        cell.text = '1'
                elif any(k in h for k in ['họ và tên', 'họ tên']):
                    target_tag = 'TenCanBo'
                    target_label = 'Họ và tên cán bộ'
                elif any(k in h for k in ['phòng ban', 'phòng/ đơn vị', 'phòng/đơn vị', 'bộ phận']):
                    target_tag = 'PhongBan'
                    target_label = 'Phòng ban / Bộ phận'
                elif 'chức vụ' in h or 'chức danh' in h:
                    target_tag = 'ChucVu'
                    target_label = 'Chức vụ'
                elif any(k in h for k in ['mã số nhân viên', 'mã nhân viên', 'mã nv']):
                    target_tag = 'MaNV'
                    target_label = 'Mã số nhân viên'
                elif any(k in h for k in ['địa chỉ e-mail', 'email ad', 'email']):
                    target_tag = 'EmailNV'
                    target_label = 'Email nhân viên'
                elif any(k in h for k in ['số điện thoại', 'số đtdđ', 'số điện thoại di động', 'sđt', 'điện thoại']):
                    target_tag = 'SDT'
                    target_label = 'Số điện thoại cá nhân'
                elif any(k in h for k in ['số cmnd', 'số thẻ căn cước', 'cccd']):
                    target_tag = 'CCCD'
                    target_label = 'CCCD/CMND Cán bộ'
                elif any(k in h for k in ['mã người dùng được cấp', 'mã người dùng ipcas', 'user ipcas', 'mã người dùng']):
                    target_tag = 'IPCAS'
                    target_label = 'Mã người dùng IPCAS'
                elif any(k in h for k in ['nội dung cập nhật', 'yêu cầu cụ thể']):
                    target_tag = 'NoiDungYeuCau'
                    target_label = 'Nội dung chi tiết yêu cầu'
                elif 'lý do' in h:
                    target_tag = 'LyDo'
                    target_label = 'Lý do đề nghị'
                # Unconfirmed fields in table
                elif any(k in h for k in ['user ad', 'user name', 'mã đăng nhập']):
                    unconf_tag = ('User AD / Mã đăng nhập', 'Chưa có TAG User AD trong bảng it_support_form_fields')
                elif 'smartform' in h:
                    unconf_tag = ('Mã người dùng SmartForm', 'Chưa có TAG riêng cho SmartForm trong it_support_form_fields')
                elif 'be-admin' in h:
                    unconf_tag = ('Mã người dùng BE-ADMIN', 'Chưa có TAG riêng cho BE-ADMIN trong it_support_form_fields')
                elif 'phương thức xác thực' in h:
                    unconf_tag = ('Phương thức xác thực (PKI/SMS/IP…)', 'Chưa có TAG Phương thức xác thực trong it_support_form_fields')
                elif any(k in h for k in ['nhóm chức năng', 'chức năng giao dịch', 'quyền nghiệp vụ', 'chức năng, quyền']):
                    unconf_tag = ('Nhóm chức năng / Quyền nghiệp vụ', 'Chưa có TAG Nhóm quyền trong it_support_form_fields')
                elif any(k in h for k in ['kiểu người dùng', 'loại thông tin cần cập nhật', 'yêu cầu thay đổi', 'yêu cầu']):
                    unconf_tag = (header_row[c_idx], 'Trường tùy chọn nghiệp vụ / chưa có mã TAG hệ thống')
                    
                if target_tag:
                    val = f"${{{target_tag}}}"
                    if cell.paragraphs and cell.paragraphs[0].runs:
                        cell.paragraphs[0].runs[0].text = val
                        for r in cell.paragraphs[0].runs[1:]:
                            r.text = ''
                    else:
                        cell.text = val
                    report_records.append({
                        'file': file_rel,
                        'field_label': target_label,
                        'tag': val,
                        'location': f'Bảng {t_idx+1}, Cột "{header_row[c_idx]}", Dòng {r_idx+1}',
                        'source': OFFICIAL_TAGS_INFO[target_tag]['source'],
                        'status': 'Đã xác định'
                    })
                elif unconf_tag:
                    unconfirmed_records.append({
                        'file': file_rel,
                        'field_label': unconf_tag[0],
                        'tag': '(Chưa hỗ trợ TAG)',
                        'location': f'Bảng {t_idx+1}, Cột "{header_row[c_idx]}", Dòng {r_idx+1}',
                        'source': unconf_tag[1],
                        'status': 'Cần xác nhận'
                    })


def process_document(file_rel):
    src_path = os.path.join(src_dir, file_rel)
    dst_path = os.path.join(dst_dir, file_rel)
    os.makedirs(os.path.dirname(dst_path), exist_ok=True)
    
    doc = docx.Document(src_path)
    report_records = []
    unconfirmed_records = []
    
    # Process all paragraphs
    for p in doc.paragraphs:
        apply_paragraph_replacement(p, file_rel, report_records, unconfirmed_records)
        
    # Process all tables
    process_tables(doc, file_rel, report_records, unconfirmed_records)
    
    # Save the updated docx to destination
    doc.save(dst_path)
    
    # Deduplicate records by (file, field_label, tag, location)
    dedup_report = []
    seen = set()
    for r in report_records:
        key = (r['file'], r['field_label'], r['tag'], r['location'])
        if key not in seen:
            seen.add(key)
            dedup_report.append(r)
            
    dedup_unconf = []
    seen_unconf = set()
    for r in unconfirmed_records:
        key = (r['file'], r['field_label'], r['location'])
        if key not in seen_unconf:
            seen_unconf.add(key)
            dedup_unconf.append(r)
            
    return dedup_report, dedup_unconf


# Main Execution
all_files = []
for root, dirs, files in os.walk(src_dir):
    if 'File da gan tag' in root:
        continue
    for f in sorted(files):
        if f.endswith('.docx') and not f.startswith('~$'):
            full_path = os.path.join(root, f)
            rel = os.path.relpath(full_path, src_dir)
            all_files.append(rel)

print(f"Total documents to process: {len(all_files)}")

all_report_records = []
all_unconfirmed_records = []
file_summary = {}

for f_idx, rel in enumerate(all_files, 1):
    try:
        reps, unconfs = process_document(rel)
        all_report_records.extend(reps)
        all_unconfirmed_records.extend(unconfs)
        file_summary[rel] = {
            'tagged_count': len(reps),
            'unconf_count': len(unconfs),
            'tags': sorted(list(set(r['tag'] for r in reps)))
        }
        print(f"[{f_idx}/{len(all_files)}] Processed {rel}: {len(reps)} tags attached, {len(unconfs)} unconfirmed.")
    except Exception as e:
        print(f"Error processing {rel}: {e}")

# Save detailed results to JSON
output_data = {
    'total_files': len(all_files),
    'file_summary': file_summary,
    'all_tagged_records': all_report_records,
    'all_unconfirmed_records': all_unconfirmed_records
}

with open(r'd:\hotrobieumau\scratch\tagging_report.json', 'w', encoding='utf-8') as f:
    json.dump(output_data, f, ensure_ascii=False, indent=2)

print("\nSuccessfully processed all files and saved tagging_report.json!")
