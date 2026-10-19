import docx
import sys

sys.stdout.reconfigure(encoding='utf-8')

doc = docx.Document(r'D:\hotrobieumau\Mau Bieu IT\IPCAS\Mau-CSUS-09_Doi-Mat-Khau-IPCAS.docx')
# test replacing one paragraph
for p in doc.paragraphs:
    if 'Vũ Quang Thịnh' in p.text:
        print("Found original:", p.text)
        new_text = p.text.replace('Vũ Quang Thịnh', '${TenCanBo}')
        if p.runs:
            p.runs[0].text = new_text
            for r in p.runs[1:]:
                r.text = ''
        else:
            p.text = new_text
        print("Replaced:", p.text)
        break

doc.save(r'D:\hotrobieumau\scratch\test_output.docx')
print("Saved successfully!")
