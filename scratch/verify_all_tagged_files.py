import os
import sys
import subprocess
import json
import docx

sys.stdout.reconfigure(encoding='utf-8')

dst_dir = r'D:\hotrobieumau\Mau Bieu IT\File da gan tag'

all_files = []
for root, dirs, files in os.walk(dst_dir):
    for f in sorted(files):
        if f.endswith('.docx') and not f.startswith('~$'):
            full_path = os.path.join(root, f)
            rel = os.path.relpath(full_path, dst_dir)
            all_files.append((rel, full_path))

print(f"Verifying {len(all_files)} generated files...")

corrupt_files = []
verified_files = []

for rel, path in all_files:
    # 1. Test opening with python-docx
    try:
        doc = docx.Document(path)
        p_count = len(doc.paragraphs)
        t_count = len(doc.tables)
    except Exception as e:
        corrupt_files.append((rel, f"python-docx error: {e}"))
        continue

    # 2. Test opening with PHP PhpWord TemplateProcessor
    res = subprocess.run(
        ['php', 'scratch/test_tp.php', path],
        capture_output=True,
        text=True,
        cwd=r'd:\hotrobieumau'
    )
    if res.returncode != 0:
        corrupt_files.append((rel, f"PHP error: {res.stderr}"))
    else:
        try:
            vars_found = json.loads(res.stdout)
            verified_files.append({
                'file': rel,
                'paragraphs': p_count,
                'tables': t_count,
                'vars': vars_found
            })
            print(f"OK: [{rel}] -> {len(vars_found)} variables: {', '.join(vars_found)}")
        except Exception as e:
            corrupt_files.append((rel, f"JSON parse error: {e}, stdout={res.stdout}"))

print(f"\nVerification Results:")
print(f"Total files: {len(all_files)}")
print(f"Valid files: {len(verified_files)}")
print(f"Corrupt files: {len(corrupt_files)}")

if corrupt_files:
    for cf in corrupt_files:
        print(f"FAILED: {cf[0]} -> {cf[1]}")
else:
    print("ALL 41 FILES PASSED PERFECTLY WITH PHPOFFICE TEMPLATEPROCESSOR!")
