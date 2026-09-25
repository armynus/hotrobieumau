import { createHash } from 'node:crypto';
import { dongThapReference, expandRef } from './dong-thap-reference.mjs';

export const clean = value => String(value ?? '').normalize('NFC').trim().replace(/\s+/gu, ' ');
// Accent-sensitive identities. Accent folding is ONLY for search/candidate review.
export const spellingKey = value => clean(value).toLowerCase().replace(/[’‘`]/gu, "'").replace(/[\s.'‐‑–—-]/gu, '');
export const searchKey = value => spellingKey(value).normalize('NFD').replace(/\p{M}/gu, '').replace(/đ/gu, 'd');
export const provinceKey = value => spellingKey(clean(value).replace(/^(tỉnh|thành phố|tp\.?)\s*/iu, '').replace(/^hcm$/iu, 'Hồ Chí Minh'));
const typeOf = value => clean(value).match(/^(Xã|Phường|Thị trấn|Đặc khu)\s/iu)?.[1] ?? '';
const bare = value => clean(value).replace(/^(xã|phường|thị trấn|đặc khu)\s+/iu, '');
const id = value => createHash('sha256').update(value).digest('hex').slice(0,24);
export const oldKey = r => [provinceKey(r.oldProvince),spellingKey(r.district),r.oldWard ? 'commune' : 'district',spellingKey(r.oldWard)].join('|');
export function group(rows, getKey) {
 const result = new Map();
 for (const row of rows) { const key = getKey(row); if (!result.has(key)) result.set(key, []); result.get(key).push(row); }
 return result;
}
const fullKey = r => provinceKey(r.province) + '|' + spellingKey(r.ward ?? r.name);
const foldedKey = r => searchKey(fullKey(r));
const bareKey = r => provinceKey(r.province) + '|' + spellingKey(bare(r.ward ?? r.name));
const foldedBareKey = r => searchKey(bareKey(r));
export function matchAuxiliary(row, units) {
 return createAuxiliaryMatcher(units)(row);
}
export function createAuxiliaryMatcher(units) {
 const indexes=[[fullKey,'exact'],[foldedKey,'name_variant'],[bareKey,'type_conflict'],[foldedBareKey,'type_conflict']].map(([key,kind])=>({key,kind,index:group(units,key)}));
 return row => {
 for (const {key,kind,index} of indexes) {
  const candidates = index.get(key(row)) ?? [];
  if (candidates.length === 1) return {kind, unit:candidates[0]};
  if (candidates.length > 1) return {kind:'ambiguous', candidates:candidates.map(u=>u.code)};
 }
 return {kind:'unmatched', candidates:[]};
 };
}

export function parseExtracts(extracts) {
 const get = (source, sheet, headerRow, headers) => {
  const s=extracts[source].sheets.find(x=>x.name===sheet);
  if(!s) throw new Error(`Missing sheet ${source}/${sheet}`);
  for(const [col,expected] of headers) if(clean(s.values[headerRow-1]?.[col])!==expected) throw new Error(`Unexpected header ${source} row ${headerRow} column ${col+1}`);
  return s.values;
 };
 const map=get('mapping','Sheet1',1,[[0,'Phường/Xã cũ'],[6,'Mã phường/xã mới']]);
 const c1=get('catalog1','Table 1',5,[[1,'Tỉnh mới'],[3,'Phường/xã mới']]);
 const c34=get('catalog34','1.DM Phường xã mới ',3,[[3,'Tên tỉnh/TP mới']]);
 const rows=(values,start,fields) => values.slice(start).map((raw,i)=>({row:start+i+1,raw:[...raw],...Object.fromEntries(fields.map(([name,col])=>[name,clean(raw[col])]))})).filter(r=>r.raw.some(v=>clean(v)));
 return {
  mapping:rows(map,1,[['oldWard',0],['district',1],['oldProvince',2],['province',3],['ward',4],['type',5],['code',6],['relation',7],['area',8]]),
  catalog1:rows(c1,5,[['province',1],['oldProvinces',2],['ward',3],['oldText',4]]),
  catalog34:rows(c34,3,[['provinceCode',2],['province',3],['tmsProvinceCode',4],['districtCode',5],['district',6],['code',8],['ward',9],['status',10],['note',11]])
 };
}

export function normalizeData(input, official, resolution, reviewedAliases = {oldUnits:[]}) {
 const issues=[];
 const issue=(kind, source, row, context, original, proposed, message, state='pending') => issues.push({
  id:id([kind,source,row,context,original,proposed].join('|')),kind,source,row,context,original,proposed,message,state,
  reviewDecision:'',reviewNote:''
 });
 const byCode=group(input.mapping,r=>r.code);
 const units=[...byCode].map(([code,rows])=>{
  if(!/^\d{5}$/u.test(code)) throw new Error(`Invalid administrative code: ${code}`);
  if(new Set(rows.map(fullKey)).size!==1 || new Set(rows.map(r=>r.type)).size!==1) throw new Error(`Conflicting names/types for code ${code}`);
  const r=rows[0];
  const areas=[...new Set(rows.map(x=>x.area))];
  const area=areas.length===1 && areas[0]!=='' && Number(areas[0])>0 ? Number(areas[0]) : null;
  if(area===null)issue('area_missing_or_invalid','mapping',r.row,`${r.ward}, ${r.province}`,areas.join('; '),'','Thiếu, bằng 0 hoặc không thống nhất diện tích. Không chuyển ô trống thành 0.');
  return {id:`vn-2025-${code}`,code,province:r.province,name:r.ward,type:r.type,areaKm2:area,
   areaVerification:'source_only',nameCodeVerification:'pending',effectiveFrom:'2025-07-01',
   sourceRows:rows.map(x=>x.row),auxiliary:[],sourceName:r.ward,provinceCode:null};
 });
 const provinceCount=new Set(units.map(r=>provinceKey(r.province))).size;
 const oldUnits=[...group(input.mapping,oldKey)].map(([key,rows])=>{
  const r=rows[0];
  return {id:`old-${id(key)}`,key,province:r.oldProvince,district:r.district,name:r.oldWard||null,level:r.oldWard?'commune':'district',code:null,sourceRows:rows.map(x=>x.row)};
 });
 const oldIndex=new Map(oldUnits.map(x=>[x.key,x]));
 const links=input.mapping.map(r=>({id:`link-${id(oldKey(r)+'|'+r.code)}`,source:'mapping',sourceRow:r.row,
  oldId:oldIndex.get(oldKey(r)).id,oldProvince:r.oldProvince,oldDistrict:r.district,oldName:r.oldWard||null,
  oldLevel:r.oldWard?'commune':'district',newCode:r.code,newProvince:r.province,newName:r.ward,
  sourceRelation:r.relation,scope:null,verification:'pending',clause:null,evidence:null,notes:''}));
 if(new Set(links.map(r=>r.id)).size!==links.length)throw new Error('Duplicate old-unit/new-code link. Review source before continuing.');
 for(const u of oldUnits.filter(x=>x.level==='district'))issue('historical_district_unit','mapping',u.sourceRows[0],`${u.district}, ${u.province}`,'Không có xã cũ','Giữ cấp huyện cũ','Không tạo xã giả và không bỏ dòng đặc khu chỉ vì trống xã cũ.');
 const splits=[...group(links,r=>r.oldId)].filter(([,rows])=>rows.length>1);
 for(const [oldId,rows] of splits)issue('multiple_destinations','mapping',rows[0].sourceRow,`${rows[0].oldName}, ${rows[0].oldDistrict}, ${rows[0].oldProvince}`,rows.map(r=>r.newName).join('; '),'','Nhiều đích mới: cần nghị quyết xác nhận, không tự chọn đích đầu tiên.');

 const resolutions=new Map(resolution.clauses.map(line=>[Number(line.match(/^\d+/u)?.[0]),line]));
 if(resolutions.size!==102 || dongThapReference.length!==102 || official.units.length!==102)throw new Error('Incomplete Dong Thap official references');
 const officialByName=new Map(official.units.map(x=>[spellingKey(x.name),x]));
 for(const officialUnit of official.units){
  const unit=units.find(x=>x.code===officialUnit.code);
  if(!unit || provinceKey(unit.province)!==provinceKey(official.province)) throw new Error(`Missing/wrong province for official code ${officialUnit.code}`);
  if(spellingKey(unit.name)!==spellingKey(officialUnit.name))issue('official_name_correction','mapping',unit.sourceRows[0],unit.code,unit.name,officialUnit.name,'Tên được đối chiếu danh mục QĐ19/2025.','resolved');
  unit.name=officialUnit.name;unit.type=typeOf(unit.name);unit.nameCodeVerification='verified_qd19';unit.provinceCode=official.provinceCode;
 }
 const oldDongThap=oldUnits.filter(u=>[provinceKey('Đồng Tháp'),provinceKey('Tiền Giang')].includes(provinceKey(u.province)));
 const expected=[],unresolved=[];
 for(const ref of dongThapReference){
  const newName=expandRef(ref.name).name;
  const officialUnit=officialByName.get(spellingKey(newName));
  if(!officialUnit || !resolutions.get(ref.clause)?.normalize('NFC').toLowerCase().includes(newName.toLowerCase()))throw new Error(`Reference transcription mismatch, clause ${ref.clause}: ${newName}`);
  for(const spec of ref.members){
   const member=expandRef(spec);
   const candidates=oldDongThap.filter(u=>{
    const direct=spellingKey(u.name??'')===spellingKey(member.name);
    const alias=reviewedAliases.oldUnits.find(a=>a.clause===ref.clause && spellingKey(a.legalName)===spellingKey(member.name) && spellingKey(a.sourceName)===spellingKey(u.name??'') && spellingKey(a.district)===spellingKey(u.district) && provinceKey(a.province)===provinceKey(u.province));
    return (direct || alias) && (!member.district || spellingKey(u.district)===spellingKey(member.district));
   });
   if(candidates.length!==1){
    unresolved.push({clause:ref.clause,newCode:officialUnit.code,newName,member,candidates});
    issue('official_member_identity','nq1663',ref.clause,newName,member.name+(member.district?' / '+member.district:''),candidates.map(c=>`${c.name}, ${c.district}, ${c.province}`).join('; '),'Chưa xác định duy nhất địa bàn cũ. Không suy đoán từ tên.');
    continue;
   }
   const old=candidates[0];
   expected.push({oldId:old.id,newCode:officialUnit.code,newName,scope:member.scope,clause:ref.clause,districtExplicit:!!member.district});
  }
 }
 const expectedIndex=new Map(expected.map(x=>[x.oldId+'|'+x.newCode,x]));
 if(expectedIndex.size!==expected.length)throw new Error('Duplicate expected legal membership');
 for(const link of links.filter(r=>provinceKey(r.newProvince)===provinceKey(official.province))){
  const match=expectedIndex.get(link.oldId+'|'+link.newCode);
  if(match){
   link.verification='verified_nq1663';link.scope=match.scope;link.clause=match.clause;link.evidence=resolution.url;link.newName=match.newName;
   if(match.scope==='whole' && link.sourceRelation!=='Hợp nhất toàn bộ' || match.scope==='part' && link.sourceRelation!=='Tách — một phần' || match.scope==='remainder')
    issue('relation_scope_correction','mapping',link.sourceRow,`${link.oldName}, ${link.oldDistrict} → ${link.newName}`,link.sourceRelation,match.scope,'Đối chiếu khoản '+match.clause+' Điều 1 NQ1663; phần còn lại không đồng nghĩa nhập chủ yếu.','resolved');
  } else if(unresolved.some(x=>x.newCode===link.newCode && spellingKey(x.member.name)===spellingKey(link.oldName??''))){
   link.verification='pending_identity';link.notes='Có tên trong nghị quyết nhưng chưa xác định duy nhất huyện/tỉnh cũ.';
  } else if(expected.some(x=>x.oldId===link.oldId && x.scope==='whole')) {
   link.verification='rejected_nq1663';link.notes='Không thuộc danh sách thành phần theo NQ1663. Giữ nguyên dòng gốc để truy vết, loại khỏi bộ liên kết đề xuất.';link.evidence=resolution.url;
   const clause=dongThapReference.find(x=>spellingKey(expandRef(x.name).name)===spellingKey(link.newName))?.clause;
   link.clause=clause??null;
   issue('incorrect_link','mapping',link.sourceRow,`${link.oldName}, ${link.oldDistrict}, ${link.oldProvince}`,link.newName,'Loại liên kết','Không thuộc khoản '+clause+' Điều 1 NQ1663.','resolved');
  } else {
   link.verification='pending_identity';link.notes='Không đủ căn cứ xác định tên/địa bàn cũ. Không tự loại hoặc tự chấp nhận liên kết.';
  }
 }
 const actualKeys=new Set(links.map(x=>x.oldId+'|'+x.newCode));
 for(const ref of expected.filter(x=>!actualKeys.has(x.oldId+'|'+x.newCode)))issue('missing_link','nq1663',ref.clause,ref.newName,oldUnits.find(x=>x.id===ref.oldId)?.name??'','','Nghị quyết có thành phần này nhưng bảng Excel chưa có liên kết.');

 const auxiliaryMatches=[];
 for(const alias of reviewedAliases.oldUnits){
  const unit=oldDongThap.find(u=>spellingKey(u.name??'')===spellingKey(alias.sourceName) && spellingKey(u.district)===spellingKey(alias.district) && provinceKey(u.province)===provinceKey(alias.province));
  if(unit){unit.aliases=[alias.legalName];issue('reviewed_old_name_alias','mapping',unit.sourceRows[0],`${unit.name}, ${unit.district}`,alias.sourceName,alias.legalName,alias.note+' Nguồn: '+alias.supportingSource,'resolved');}
 }
 const auxiliaryMatcher=createAuxiliaryMatcher(units);
 for(const source of ['catalog1','catalog34'])for(const row of input[source]){
  const match=auxiliaryMatcher(row);
  auxiliaryMatches.push({source,row:row.row,kind:match.kind,newCode:match.unit?.code??null,raw:row});
  if(match.unit){
   match.unit.auxiliary.push({source,row:row.row,joinKind:match.kind,sourceName:row.ward,
    businessCode:source==='catalog34'?row.code:null,oldComposition:source==='catalog1'?row.oldText:null});
  }
  if(match.kind!=='exact')issue('auxiliary_'+match.kind,source,row.row,`${row.ward}, ${row.province}`,row.ward,match.unit?.name??(match.candidates??[]).join('; '),'Ghép bổ sung để rà soát; không dùng tên gần giống làm định danh chính.');
  if(source==='catalog34' && (row.note||row.status))issue('source_review_note',source,row.row,`${row.ward}, ${row.province}`,row.note||row.status,'','Giữ chú thích của nguồn; cần xác định là lỗi còn tồn tại hay ghi chú đã sửa.');
  if(source==='catalog1' && provinceKey(row.province)===provinceKey(official.province)){
   const oldProvinces=row.oldProvinces.split(',').map(provinceKey).sort();
   if(JSON.stringify(oldProvinces)!==JSON.stringify(['Đồng Tháp','Tiền Giang'].map(provinceKey).sort()))issue('province_composition_conflict',source,row.row,row.province,row.oldProvinces,'Đồng Tháp, Tiền Giang','Không dùng cột tỉnh hợp thành này để tạo ánh xạ tỉnh.');
  }
 }
 // A completed legal review resolves only the matching multiple-destination issue.
 for(const issueRow of issues.filter(x=>x.kind==='multiple_destinations')){
  const row=links.find(x=>x.sourceRow===issueRow.row), related=links.filter(x=>x.oldId===row.oldId);
  if(related.every(x=>['verified_nq1663','rejected_nq1663'].includes(x.verification))){issueRow.state='resolved';issueRow.proposed=related.filter(x=>x.verification==='verified_nq1663').map(x=>x.newName).join('; ');issueRow.message='Đã đối chiếu các đích theo NQ1663; xem phạm vi toàn bộ/một phần/phần còn lại ở bảng liên kết.';}
 }
 const stats={inputRows:input.mapping.length,newUnits:units.length,newProvinces:provinceCount,oldUnits:oldUnits.length,
  splitCandidates:splits.length,verifiedLinks:links.filter(x=>x.verification==='verified_nq1663').length,
  rejectedLinks:links.filter(x=>x.verification==='rejected_nq1663').length,pendingLinks:links.filter(x=>x.verification.startsWith('pending')).length,
  officialNewUnits:units.filter(x=>x.nameCodeVerification==='verified_qd19').length,expectedDongThapLinks:expected.length,
  unresolvedDongThapMembers:unresolved.length,issues:issues.length,pendingIssues:issues.filter(x=>x.state==='pending').length};
 return {schemaVersion:1,snapshotDate:'2025-07-01',productionReady:false,
  verificationScope:'Đối chiếu tên, mã mới và quan hệ Đồng Tháp theo QĐ19/NQ1663. Huyện/tỉnh cũ lấy từ Excel, phân biệt bằng địa bàn ghi trong nghị quyết hoặc tên duy nhất trong dữ liệu hai tỉnh cũ. Chưa xác minh mã cũ, diện tích, ranh giới chi tiết và các thay đổi sau mốc 01/07/2025.',
  stats,units,oldUnits,links,issues,auxiliaryMatches,unresolvedDongThapMembers:unresolved,officialExpected:expected};
}
