import docx
import sys
import os

sys.stdout.reconfigure(encoding='utf-8')

sample_files = [
    r'D:\hotrobieumau\Mau Bieu IT\BeAdmin\Mau-01_Yeu-Cau-Cap-Moi-Thay-Doi-Thong-Tin-Nguoi-Quan-Tri-BE-ADMIN.docx',
    r'D:\hotrobieumau\Mau Bieu IT\BeAdmin\Mau-04_Yeu-Cau-Cap-Lai-Mat-Khau-Nguoi-Su-Dung-BE-ADMIN.docx',
    r'D:\hotrobieumau\Mau Bieu IT\IPCAS\Mau-CSUS-02_Yeu-Cau-Cap-Moi-Nguoi-Dung.docx',
    r'D:\hotrobieumau\Mau Bieu IT\IPCAS\Mau-CSUS-09_Doi-Mat-Khau-IPCAS.docx',
    r'D:\hotrobieumau\Mau Bieu IT\Mẫu PKI\Mau-1A-PKI_Dang-Ky-Chung-Thu-So.docx',
    r'D:\hotrobieumau\Mau Bieu IT\PMNV\Mau-02-PMNV_Cap-Quyen-Nguoi-Su-Dung.docx',
    r'D:\hotrobieumau\Mau Bieu IT\SMF\Mau-02-SMF_Cap-Moi-Ma-Nguoi-Dung.docx',
    r'D:\hotrobieumau\Mau Bieu IT\Mau-15-TT.docx'
]

for sf in sample_files:
    print(f"\n=================== {os.path.basename(sf)} ===================")
    doc = docx.Document(sf)
    for i, p in enumerate(doc.paragraphs[:15]):
        if p.text.strip():
            print(f"P{i}: {p.text.strip()}")
    for t_idx, t in enumerate(doc.tables[:2]):
        print(f"  Table {t_idx}:")
        for r_idx, r in enumerate(t.rows[:3]):
            cells = [c.text.strip().replace('\n', ' ') for c in r.cells]
            print(f"    R{r_idx}: " + " | ".join(cells))
