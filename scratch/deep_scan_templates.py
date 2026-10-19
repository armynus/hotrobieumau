import os
import sys
import docx

sys.stdout.reconfigure(encoding='utf-8')
src_dir = r'D:\hotrobieumau\Mau Bieu IT'

for root, dirs, files in os.walk(src_dir):
    if 'File da gan tag' in root:
        continue
    for f in sorted(files):
        if f.endswith('.docx') and not f.startswith('~$'):
            full_path = os.path.join(root, f)
            rel = os.path.relpath(full_path, src_dir)
            try:
                doc = docx.Document(full_path)
                p_count = len(doc.paragraphs)
                t_count = len(doc.tables)
                print(f"[{rel}] - Paras: {p_count}, Tables: {t_count}")
                # check table header in table 1 or 2
                for ti, t in enumerate(doc.tables):
                    if len(t.rows) > 0:
                        first_row = [c.text.strip().replace('\n', ' ') for c in t.rows[0].cells]
                        h_str = " | ".join(first_row)
                        if any(k in h_str.lower() for k in ['họ và tên', 'họ tên', 'stt', 'user', 'mã số', 'chức vụ']):
                            print(f"   Table {ti} (rows={len(t.rows)}, cols={len(first_row)}): {h_str[:120]}...")
            except Exception as e:
                print(f"Error {rel}: {e}")
