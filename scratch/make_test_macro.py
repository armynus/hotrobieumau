import docx

doc = docx.Document()
doc.add_paragraph('TenCanBo')
doc.add_paragraph('${TenCanBo}')
doc.save('d:/hotrobieumau/scratch/test_macro.docx')
