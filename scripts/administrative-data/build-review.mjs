import fs from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { createRequire } from 'node:module';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { clean, parseExtracts, normalizeData, group } from './normalize.mjs';

const args=process.argv.slice(2);
const option=(name,fallback)=>{const i=args.indexOf(name);return i>=0?args[i+1]:fallback;};
const inputDir=path.resolve(option('--input-dir','C:/Users/Admin/Downloads'));
const outputDir=path.resolve(option('--output-dir','outputs/ward-review-2026-09-24'));
if(outputDir===inputDir)throw new Error('Output directory must differ from the original workbook directory.');
const runtimeModules=option('--runtime-modules',path.resolve(path.dirname(process.execPath),'../node_modules'));
const modulePath=createRequire(import.meta.url).resolve('@oai/artifact-tool',{paths:[runtimeModules]});
const {Workbook,SpreadsheetFile,FileBlob}=await import(pathToFileURL(modulePath).href);
const sourceDir=path.join(path.dirname(fileURLToPath(import.meta.url)),'sources');
const readJson=async file=>JSON.parse(await fs.readFile(file,'utf8'));
const official=await readJson(path.join(sourceDir,'qd19-dong-thap.json'));
const resolution=await readJson(path.join(sourceDir,'nq1663-dong-thap.json'));
const aliases=await readJson(path.join(sourceDir,'reviewed-aliases.json'));
const definitions={
 mapping:{filename:'vietnam-sap-nhap-phuong-xa.xlsx',sheet:'Sheet1',range:'A1:I10603'},
 catalog1:{filename:'Danh-muc-Phuong-xa_moi-1.xlsx',sheet:'Table 1',range:'A1:E3326'},
 catalog34:{filename:'Danh-muc-Phuong-xa_moi_34-tinh-thanh-sau-sat-nhap.xlsx',sheet:'1.DM Phường xã mới ',range:'A1:L3324'}
};
await fs.mkdir(outputDir,{recursive:true});
const hash=async file=>createHash('sha256').update(await fs.readFile(file)).digest('hex');
const extracts={},sources={};
for(const [key,def] of Object.entries(definitions)){
 const file=path.join(inputDir,def.filename),sha256=await hash(file);
 sources[key]={...def,sha256};
 const cacheFile=path.join(outputDir,`${key}-extract.json`);
 let cached;try{cached=await readJson(cacheFile);}catch(error){if(error.code!=='ENOENT')throw error;}
 if(cached?.sha256===sha256){extracts[key]=cached;console.log(`Reusing unchanged source ${key}`);continue;}
 console.log(`Reading ${def.filename}`);
 const sourceWb=await SpreadsheetFile.importXlsx(await FileBlob.load(file));
 const info=await sourceWb.inspect({kind:'sheet',maxChars:6000});
 const metadata=info.ndjson.split('\n').filter(Boolean).map(line=>JSON.parse(line)).find(x=>x.kind==='sheet'&&x.name===def.sheet);
 if(!metadata || metadata.range!==def.range)throw new Error(`Source layout changed: ${key}; inspect new sheet before importing.`);
 extracts[key]={sha256,sheets:[{name:def.sheet,range:def.range,values:sourceWb.worksheets.getItem(def.sheet).getRange(def.range).values}]};
 await fs.writeFile(cacheFile,JSON.stringify(extracts[key]));
}
const input=parseExtracts(extracts);
const result=normalizeData(input,official,resolution,aliases);
if(result.stats.verifiedLinks!==308 || result.stats.rejectedLinks!==6 || result.stats.officialNewUnits!==102 || result.stats.unresolvedDongThapMembers!==0)throw new Error('Dong Thap regression checks failed; do not export reviewed dataset.');
result.sources=sources;result.officialSources={official,resolution,aliases};
result.summaryGeneratedOn='2026-09-24';
await fs.writeFile(path.join(outputDir,'normalized-review.json'),JSON.stringify(result,null,2));
await fs.writeFile(path.join(outputDir,'dong-thap-reviewed.json'),JSON.stringify({schemaVersion:1,snapshotDate:result.snapshotDate,verificationScope:result.verificationScope,
 productionReady:false,units:result.units.filter(x=>x.nameCodeVerification==='verified_qd19'),
 oldUnits:result.oldUnits.filter(x=>result.links.some(l=>l.oldId===x.id&&l.verification==='verified_nq1663')),
 links:result.links.filter(x=>x.verification==='verified_nq1663'),rejected:result.links.filter(x=>x.verification==='rejected_nq1663'),sources:result.officialSources},null,2));
console.log(JSON.stringify(result.stats));
// App imports only need JSON + embedded reconciliation notes, not a review workbook.
if (args.includes('--json-only')) process.exit(0);

const wb=Workbook.create();
const textSafe=value=>typeof value==='string'&&/^[=+@]/u.test(value)?"'"+value:value;
const col=n=>{let s='';for(let x=n;x>0;x=Math.floor((x-1)/26))s=String.fromCharCode(65+(x-1)%26)+s;return s;};
const relationLabel={whole:'Toàn bộ',part:'Một phần',remainder:'Phần còn lại'};
const statusLabel={verified_nq1663:'Đã đối chiếu NQ1663',rejected_nq1663:'Loại theo NQ1663',pending:'Chưa đối chiếu nghị quyết',pending_identity:'Cần xác minh địa bàn cũ'};
const kindLabels={incorrect_link:'Liên kết sai',multiple_destinations:'Nhiều đích mới',relation_scope_correction:'Sửa phạm vi sáp nhập',reviewed_old_name_alias:'Biến thể tên đã duyệt',area_missing_or_invalid:'Diện tích thiếu/lỗi',historical_district_unit:'Đơn vị cũ cấp huyện',auxiliary_name_variant:'Khác cách viết tên',auxiliary_type_conflict:'Khác xã/phường/đặc khu',auxiliary_unmatched:'Chưa ghép được tên',auxiliary_ambiguous:'Tên chưa xác định duy nhất',source_review_note:'Ghi chú từ nguồn',province_composition_conflict:'Khác tỉnh hợp thành',official_member_identity:'Cần xác minh đơn vị cũ',missing_link:'Thiếu liên kết',official_name_correction:'Sửa tên theo danh mục'};
const summary=wb.worksheets.add('Tong quan');
summary.showGridLines=false;summary.tabColor='#A6193E';
function tableSheet(name,title,context,headers,rows,widths,tableName){
 const sheet=wb.worksheets.add(name),lastRow=rows.length+4,lastCol=col(headers.length);
 sheet.showGridLines=false;
 sheet.getRange(`A1:${lastCol}${lastRow}`).format.font={name:'Arial',size:10,color:'#263445'};
 sheet.getRange(`A1:${lastCol}${lastRow}`).format.verticalAlignment='center';
 sheet.getRange('A2').values=[[title]];sheet.getRange('A2').format.font={bold:true,size:14,color:'#263445'};
 sheet.getRange('A3').values=[[context]];sheet.getRange('A3').format.font={italic:true,color:'#596579',size:10};
 sheet.getRange(`A4:${lastCol}4`).values=[headers];
 if(rows.length)sheet.getRange(`A5:${lastCol}${lastRow}`).values=rows.map(row=>row.map(textSafe));
 const table=sheet.tables.add(`A4:${lastCol}${lastRow}`,true,tableName);table.style='TableStyleMedium2';table.showFilterButton=true;
 sheet.getRange(`A4:${lastCol}4`).format={fill:'#30465D',font:{name:'Arial',size:10,color:'#FFFFFF',bold:true},wrapText:true,rowHeight:34,horizontalAlignment:'center',verticalAlignment:'center'};
 if(rows.length)sheet.getRange(`A5:${lastCol}${lastRow}`).format.rowHeight=32;
 widths.forEach((w,i)=>sheet.getRange(`${col(i+1)}4:${col(i+1)}${lastRow}`).format.columnWidth=w);
 sheet.freezePanes.freezeRows(4);
 return sheet;
}
const dtLinks=result.links.filter(x=>x.verification==='verified_nq1663').sort((a,b)=>a.clause-b.clause||a.sourceRow-b.sourceRow);
const dt=tableSheet('Dong Thap','Đồng Tháp: liên kết đã đối chiếu','Mốc 01/07/2025. Quan hệ: NQ1663, mã mới: QĐ19. Chưa xác minh diện tích và ranh giới chi tiết.',
 ['Tỉnh cũ','Huyện/TP cũ','Xã/phường cũ','Đơn vị mới','Mã hành chính mới','Phạm vi','Khoản Điều 1','Dòng Excel gốc'],
 dtLinks.map(x=>[x.oldProvince,x.oldDistrict,x.oldName,x.newName,x.newCode,relationLabel[x.scope],x.clause,x.sourceRow]),[23,25,23,23,17,18,13,15],'DongThapReviewed');
dt.getRange(`A5:D${dtLinks.length+4}`).format.wrapText=true;dt.getRange(`E5:E${dtLinks.length+4}`).setNumberFormat('@');
const catalog=tableSheet('Danh muc moi','Danh mục đơn vị mới','Mã 8 chữ số giữ theo nguồn TMS, chưa xác minh độc lập. Tên/mã chính thức đã kiểm chứng: Đồng Tháp.',
 ['Mã hành chính','Tỉnh/TP mới','Đơn vị mới','Loại','Đối chiếu tên/mã','Mã 8 chữ số nguồn','Ghép tên mã 8 số','Diện tích nguồn (km²)','Mốc dữ liệu','Dòng mapping đầu','Dòng danh mục TMS','Dòng mô tả','Thành phần theo file mô tả'],
 result.units.map(x=>{const t=x.auxiliary.find(a=>a.source==='catalog34'),n=x.auxiliary.find(a=>a.source==='catalog1');return[x.code,x.province,x.name,x.type,x.nameCodeVerification==='verified_qd19'?'Đã đối chiếu QĐ19':'Chưa đối chiếu QĐ19',t?.businessCode??'',t?.joinKind==='exact'?'Khớp cách viết':t?'Cần duyệt cách viết':'Chưa ghép',x.areaKm2,new Date('2025-07-01T00:00:00Z'),x.sourceRows[0],t?.row??null,n?.row??null,n?.oldComposition??''];}),
 [16,24,27,13,25,19,24,18,15,16,18,14,95],'NewUnits');
catalog.getRange(`A5:A${result.units.length+4}`).setNumberFormat('@');catalog.getRange(`F5:F${result.units.length+4}`).setNumberFormat('@');catalog.getRange(`H5:H${result.units.length+4}`).setNumberFormat('0.0000');catalog.getRange(`I5:I${result.units.length+4}`).setNumberFormat('dd/mm/yyyy');
catalog.getRange(`M5:M${result.units.length+4}`).format.wrapText=true;catalog.getRange(`M5:M${result.units.length+4}`).format.autofitRows();
const linkSheet=tableSheet('Lien ket','Liên kết cũ và mới: toàn bộ dòng nguồn','Không dùng làm dữ liệu sản xuất: chỉ các dòng ghi Đã đối chiếu NQ1663 đã được kiểm chứng quan hệ trong phạm vi nêu ở Tổng quan.',
 ['Dòng Excel gốc','Tỉnh cũ','Huyện/TP cũ','Xã/phường cũ','Cấp cũ','Mã mới','Đơn vị mới','Tỉnh mới','Hình thức trong nguồn','Kết quả đối chiếu','Phạm vi đã xác minh','Khoản NQ1663','Ghi chú'],
 result.links.map(x=>[x.sourceRow,x.oldProvince,x.oldDistrict,x.oldName??'',x.oldLevel==='district'?'Huyện':'Xã/phường',x.newCode,x.newName,x.newProvince,x.sourceRelation,statusLabel[x.verification],relationLabel[x.scope]??'',x.clause,x.notes]),
 [15,24,27,24,16,13,28,26,26,30,22,18,80],'AllSourceLinks');
linkSheet.getRange(`F5:F${result.links.length+4}`).setNumberFormat('@');
linkSheet.getRange(`J5:J${result.links.length+4}`).conditionalFormats.add('containsText',{text:'Loại',format:{fill:'#FCE8E8',font:{color:'#9B1C31',bold:true}}});
const issues=[...result.issues].sort((a,b)=>Number(b.kind==='incorrect_link')-Number(a.kind==='incorrect_link')||a.state.localeCompare(b.state)||a.source.localeCompare(b.source)||a.row-b.row);
const review=tableSheet('Can kiem tra','Các điểm cần kiểm tra và lịch sử hiệu chỉnh','Ý kiến người duyệt là ô nhập riêng; chưa tự áp dụng vào dữ liệu chuẩn. Mỗi vấn đề có mã ổn định để truy vết.',
 ['Loại vấn đề','Trạng thái','Đơn vị / địa bàn','Trong nguồn','Đề xuất / đối chiếu','Giải thích','Nguồn','Dòng nguồn / khoản','Mã vấn đề','Ý kiến người duyệt','Ghi chú người duyệt'],
 issues.map(x=>[kindLabels[x.kind]??x.kind,x.state==='resolved'?'Đã xử lý trong bản chuẩn hóa':'Cần kiểm tra',x.context,x.original,x.proposed,x.message,x.source==='nq1663'?'NQ1663':definitions[x.source]?.filename??x.source,x.row,x.id,'','']),
 [25,28,55,45,45,80,45,19,29,24,50],'ReviewQueue');
review.getRange(`A5:H${issues.length+4}`).format.wrapText=true;review.getRange(`A5:H${issues.length+4}`).format.autofitRows();
review.getRange(`J5:K${issues.length+4}`).format.fill='#FFF5CC';
review.getRange(`J5:J${issues.length+4}`).dataValidation={rule:{type:'list',values:['Chấp thuận','Không chấp thuận','Cần thêm căn cứ']}};
review.getRange(`B5:B${issues.length+4}`).conditionalFormats.add('containsText',{text:'Cần kiểm tra',format:{fill:'#FFF1D6',font:{color:'#855100'}}});

summary.getRange('A1:F42').format.font={name:'Arial',size:10,color:'#263445'};
summary.getRange('A2').values=[['Dữ liệu tra cứu xã/phường cũ và mới']];summary.getRange('A2').format.font={size:15,bold:true};
summary.getRange('A3').values=[['Bản đối chiếu theo mốc 01/07/2025; chưa nhập vào database và chưa phát hành cho người dùng.']];
summary.getRange('A5:C5').values=[['Nội dung','Số lượng','Phạm vi / cách hiểu']];
const metrics=[
 ['Đơn vị mới',`=COUNTA('Danh muc moi'!A5:A${result.units.length+4})`,'Danh mục tổng hợp từ 3 file.'],
 ['Dòng liên kết nguồn',`=COUNTA('Lien ket'!A5:A${result.links.length+4})`,'Giữ cả các dòng bị loại để đối chiếu.'],
 ['Liên kết đã đối chiếu',`=COUNTIFS('Lien ket'!J5:J${result.links.length+4},"Đã đối chiếu NQ1663")`,'Đồng Tháp; tên và phạm vi theo NQ1663.'],
 ['Liên kết sai đã tách riêng',`=COUNTIFS('Lien ket'!J5:J${result.links.length+4},"Loại theo NQ1663")`,'Nhầm phường cùng tên giữa Mỹ Tho, Cai Lậy, Sa Đéc với Cao Lãnh.'],
 ['Liên kết chưa đối chiếu',`=COUNTIFS('Lien ket'!J5:J${result.links.length+4},"Chưa đối chiếu nghị quyết")`,'Không đồng nghĩa sai; chưa xác minh nghị quyết tương ứng.'],
 ['Tên/mã mới đã kiểm chứng',`=COUNTIFS('Danh muc moi'!E5:E${result.units.length+4},"Đã đối chiếu QĐ19")`,'102 đơn vị Đồng Tháp; mã 5 chữ số.'],
 ['Điểm còn cần kiểm tra',`=COUNTIFS('Can kiem tra'!B5:B${issues.length+4},"Cần kiểm tra")`,'Mỗi đơn vị có thể có nhiều vấn đề; ý kiến duyệt chưa áp dụng.']
];
metrics.forEach(([label,formula,note],i)=>{summary.getRange(`A${i+6}:C${i+6}`).values=[[label,null,note]];summary.getRange(`B${i+6}`).formulas=[[formula]];});
summary.getRange('A15').values=[['Phạm vi đã kiểm tra và giới hạn sử dụng']];summary.getRange('A15').format.font={bold:true,size:12};
const notes=[
 '102 tên/mã mới Đồng Tháp được đối chiếu QĐ19; 308 quan hệ theo 102 khoản NQ1663.',
 'Huyện/tỉnh cũ theo Excel; phân biệt bằng địa bàn trong NQ hoặc tên duy nhất của hai tỉnh cũ.',
 'Mỹ Quý / Mỹ Quí (Tháp Mười) được đối chiếu riêng, không thay i/y hàng loạt.',
 'Mã TMS, mã cũ, diện tích, ranh giới ấp/thôn và thay đổi sau 01/07/2025 chưa được xác minh.',
 'Xã cũ có nhiều đích mới phải hiện đủ kết quả; không tự chọn đích đầu hoặc đích nhập chủ yếu.',
 'File gốc giữ nguyên. Dòng nguồn, giá trị gốc và dấu vân tay SHA-256 được lưu để truy vết.'
];
notes.forEach((n,i)=>summary.getRange(`A${i+16}`).values=[[n]]);
summary.getRange('A24').values=[['Nguồn đầu vào và căn cứ đối chiếu']];summary.getRange('A24').format.font={bold:true,size:12};
Object.values(sources).forEach((s,i)=>{summary.getRange(`A${26+i*2}`).values=[[`${s.filename} — ${s.sheet} (${s.range})`]];summary.getRange(`A${27+i*2}`).values=[[`SHA-256: ${s.sha256}`]];});
summary.getRange('A33').values=[['NQ1663/NQ-UBTVQH15: Điều 1, khoản 1–102; chính quyền mới hoạt động từ 01/07/2025.']];
summary.getRange('A34').values=[[resolution.url]];
summary.getRange('A36').values=[['QĐ19/2025/QĐ-TTg: Đồng Tháp, mã tỉnh 82; danh mục ở trang Công báo 58–62.']];
summary.getRange('A37').values=[[official.url]];
summary.getRange('A39').values=[['Biến thể tên Mỹ Quý tại Tháp Mười: tài liệu của Sở NN&PTNT Đồng Tháp năm 2024.']];
summary.getRange('A40').values=[[aliases.oldUnits[0].supportingSource]];
summary.getRange('A5:C5').format={fill:'#30465D',font:{bold:true,color:'#FFFFFF'},rowHeight:30};
summary.getRange('A6:C12').format.rowHeight=36;summary.getRange('C6:C12').format.wrapText=true;
summary.getRange('A1:A42').format.columnWidth=43;summary.getRange('B1:B42').format.columnWidth=15;summary.getRange('C1:C42').format.columnWidth=74;
summary.getRange('B6:B12').setNumberFormat('#,##0');summary.getRange('B6:B12').format.font={bold:true,size:12};
summary.getRange('A15:A40').format.rowHeight=26;

wb.recalculate();
const metricValues=summary.getRange('B6:B12').values.flat();
const expectedMetrics=[result.stats.newUnits,result.stats.inputRows,result.stats.verifiedLinks,result.stats.rejectedLinks,result.stats.pendingLinks,result.stats.officialNewUnits,result.stats.pendingIssues];
if(JSON.stringify(metricValues)!==JSON.stringify(expectedMetrics))throw new Error(`Workbook summary mismatch ${JSON.stringify(metricValues)} != ${JSON.stringify(expectedMetrics)}`);
console.log((await wb.inspect({kind:'table',sheetId:'Tong quan',range:'A5:C12',tableMaxRows:8,tableMaxCols:3,maxChars:2500})).ndjson);
const errors=await wb.inspect({kind:'match',searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!',options:{useRegex:true,maxResults:20},maxChars:1500});
console.log(errors.ndjson);
const workbookPath=path.join(outputDir,'doi-chieu-xa-phuong.xlsx');
await (await SpreadsheetFile.exportXlsx(wb)).save(workbookPath);
const previews=[['Tong quan','A1:C21','tong-quan'],['Tong quan','A24:C41','nguon-doi-chieu'],['Dong Thap','A1:H11','dong-thap'],['Danh muc moi','A1:G9','danh-muc'],['Lien ket','A1:G9','lien-ket'],['Can kiem tra','A1:E10','can-kiem-tra'],['Can kiem tra','F4:K7','y-kien-duyet']];
for(const [sheetName,range,name] of previews){
 console.log(`Rendering ${sheetName}`);
 const png=await wb.render({sheetName,range,scale:1,format:'png'});
 await fs.writeFile(path.join(outputDir,`${name}.png`),new Uint8Array(await png.arrayBuffer()));
}
for(const [key,def] of Object.entries(definitions))if(await hash(path.join(inputDir,def.filename))!==sources[key].sha256)throw new Error(`Original file changed: ${key}`);
await fs.writeFile(path.join(outputDir,'verification.json'),JSON.stringify({originalFilesUnchanged:true,summaryFormulaValues:metricValues,stats:result.stats,outputSha256:await hash(workbookPath)},null,2));
console.log(`Complete: ${workbookPath}`);
