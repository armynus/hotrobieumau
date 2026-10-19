import docx
import sys

sys.stdout.reconfigure(encoding='utf-8')
doc = docx.Document(r'D:\hotrobieumau\Mau Bieu IT\IPCAS\Mau-CSUS-01_Yeu-Cau-Cap-Moi-Thay-Doi-Thong-Tin.docx')
print('=== PARAGRAPHS ===')
for i, p in enumerate(doc.paragraphs):
    if any(k in p.text for k in ['${', 'Đơn vị', 'Kính gửi', 'ngày']):
        print(f'P{i}: {p.text}')

print('=== TABLES ===')
for t_idx, t in enumerate(doc.tables):
    print(f'Table {t_idx}:')
    for r_idx, r in enumerate(t.rows):
        cells = [c.text.strip().replace('\n', ' ') for c in r.cells]
        print(f'  Row {r_idx}: ' + ' | '.join(cells))
