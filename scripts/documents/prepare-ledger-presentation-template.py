"""Turn the retained clerk DOCX into a PHPWord template without rebuilding its package.

Run with the bundled Python and pass the documents skill directory for OOXML helpers.
The original reference is never modified. This is a maintenance tool, not a web dependency.
"""
import argparse
from copy import copy
import hashlib
import json
import sys
from pathlib import Path
from zipfile import ZipFile

from lxml import etree


def replace_span(paragraph, old, new, ns):
    nodes = paragraph.xpath('.//w:t', namespaces=ns)
    text = ''.join(n.text or '' for n in nodes)
    if text.count(old) != 1:
        raise ValueError(f'Expected exactly one slot: {old!r}')
    start = text.index(old)
    end = start + len(old)
    offset = 0
    inserted = False
    for node in nodes:
        value = node.text or ''
        node_end = offset + len(value)
        if node_end > start and offset < end:
            left = value[:max(0, start - offset)]
            right = value[max(0, end - offset):] if node_end > end else ''
            node.text = left + (new if not inserted else '') + right
            inserted = True
        offset = node_end


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('reference', type=Path)
    parser.add_argument('output', type=Path)
    parser.add_argument('--skill-dir', type=Path, required=True)
    parser.add_argument('--evidence', type=Path, required=True)
    args = parser.parse_args()
    if args.reference.resolve() == args.output.resolve():
        raise ValueError('Output must not overwrite the retained reference')
    sys.path.insert(0, str(args.skill_dir / 'scripts'))
    from docx_ooxml_patch import NS

    reference_hash = hashlib.sha256(args.reference.read_bytes()).hexdigest()
    with ZipFile(args.reference) as source:
        xml = etree.fromstring(source.read('word/document.xml'))
        paragraphs = xml.xpath('//w:body/w:p', namespaces=NS)
        replace_span(paragraphs[2], 'ĐỒNG THÁP', '${branch_name}', NS)
        replace_span(paragraphs[3], 'PHÒNG TỔNG HỢP', '${department_name}', NS)
        replace_span(paragraphs[3], 'P. Cao Lãnh,', '${place_line}', NS)
        replace_span(paragraphs[3], '15 tháng 9 năm 2026', '${print_date} tháng ${print_month} năm ${print_year}', NS)
        replace_span(paragraphs[7], 'Ban Giám đốc', '${submitted_to}', NS)
        replace_span(paragraphs[9], 'P. TRƯỞNG PHÒNG TỔNG HỢP', '${signature_title}', NS)
        replace_span(paragraphs[17], 'Nguyễn Thị Thúy Nga', '${prepared_by}', NS)
        cells = xml.xpath('//w:tbl/w:tr/w:tc', namespaces=NS)
        replace_span(cells[2], 'Ban Giám đốc', '${submitted_to}', NS)
        right = cells[3].findall('w:p', NS)
        replace_span(right[1], '15867/NHNo-TTKH', '${document_code}', NS)
        replace_span(right[2], '15/09/2026', '${issued_date}', NS)
        replace_span(right[3], 'Agribank.', '${issuing_agency}', NS)
        replace_span(right[4], 'Triển khai thu hộ học phí qua cổng kết nối của DTSoft trên địa bàn tỉnh Đồng Tháp', '${title}', NS)
        edits = {'word/document.xml': etree.tostring(xml, xml_declaration=True, encoding='UTF-8', standalone=True)}
        for name in ['word/header1.xml', 'word/header2.xml', 'word/header3.xml']:
            header = etree.fromstring(source.read(name))
            for element in header.iter():
                if 'string' in element.attrib:
                    element.set('string', element.get('string').replace('ĐỒNG THÁP', '${branch_name}'))
            edits[name] = etree.tostring(header, xml_declaration=True, encoding='UTF-8', standalone=True)
        evidence = []
        args.output.parent.mkdir(parents=True, exist_ok=True)
        with ZipFile(args.output, 'w') as target:
            for info in source.infolist():
                old = source.read(info.filename)
                new = edits.get(info.filename, old)
                target.writestr(copy(info), new)
                evidence.append({'part': info.filename, 'bytes': len(old), 'sha256': hashlib.sha256(old).hexdigest(), 'changed': new != old})
        with ZipFile(args.output) as target:
            assert set(source.namelist()) == set(target.namelist())
            for name in source.namelist():
                if name not in edits:
                    assert source.read(name) == target.read(name), name
    assert hashlib.sha256(args.reference.read_bytes()).hexdigest() == reference_hash
    args.evidence.write_text(json.dumps({'reference_sha256': reference_hash, 'parts': evidence}, ensure_ascii=False, indent=2), encoding='utf-8')
    print(args.output)


if __name__ == '__main__':
    main()
