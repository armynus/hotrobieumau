import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { oldKey, spellingKey, searchKey, matchAuxiliary, parseExtracts, normalizeData } from '../../scripts/administrative-data/normalize.mjs';
import { dongThapReference, expandRef } from '../../scripts/administrative-data/dong-thap-reference.mjs';
const root=fileURLToPath(new URL('../../',import.meta.url));
const read=async file=>JSON.parse(await fs.readFile(file,'utf8'));
const source=async name=>read(path.join(root,'scripts/administrative-data/sources',name+'.json'));

test('accent-sensitive identities distinguish Thành and Thạnh, search finds both',()=>{
 assert.notEqual(spellingKey('Xã Tân Thành'),spellingKey('Xã Tân Thạnh'));
 assert.equal(searchKey('Xã Tân Thành'),searchKey('Xã Tân Thạnh'));
 const units=[{province:'Tỉnh Đồng Tháp',name:'Xã Tân Thành',code:'29938'},{province:'Tỉnh Đồng Tháp',name:'Xã Tân Thạnh',code:'30157'}];
 assert.equal(matchAuxiliary({province:'Đồng Tháp',ward:'Xã Tân Thành'},units).unit.code,'29938');
 assert.equal(matchAuxiliary({province:'Đồng Tháp',ward:'Xa Tan Thanh'},units).kind,'ambiguous');
});
test('same ward name in different districts/provinces is not deduplicated',()=>{
 const ward={oldProvince:'Tỉnh Đồng Tháp',district:'Thành phố Sa Đéc',oldWard:'Phường 3'};
 assert.notEqual(oldKey(ward),oldKey({...ward,district:'Thành phố Cao Lãnh'}));
 assert.notEqual(oldKey(ward),oldKey({...ward,oldProvince:'Tỉnh Tiền Giang'}));
});
test('historical district without commune is retained as a distinct level',()=>{
 const row={oldProvince:'Hải Phòng',district:'Huyện Bạch Long Vĩ',oldWard:''};
 assert.match(oldKey(row),/\|district\|$/u);
 assert.notEqual(oldKey(row),oldKey({...row,oldWard:'Xã Bạch Long Vĩ'}));
});
test('type conflict creates a candidate, never overwrites the primary name',()=>{
 const units=[{province:'Đồng Tháp',name:'Phường Cao Lãnh',code:'29869'}];
 const match=matchAuxiliary({province:'Đồng Tháp',ward:'Xã Cao Lãnh'},units);
 assert.equal(match.kind,'type_conflict');assert.equal(units[0].name,'Phường Cao Lãnh');
});
test('punctuation and known province abbreviation normalize without changing accents',()=>{
 assert.equal(spellingKey("Xã Cư M’gar"),spellingKey("Xã Cư M'gar"));
 assert.equal(matchAuxiliary({province:'TP HCM',ward:'Phường An Đông'},[{province:'Thành phố Hồ Chí Minh',name:'Phường An Đông',code:'00001'}]).unit.code,'00001');
});
test('independent legal reference covers 102 units and 308 memberships',async()=>{
 const q=await source('qd19-dong-thap'),n=await source('nq1663-dong-thap');
 assert.equal(dongThapReference.length,102);assert.equal(n.clauses.length,102);assert.equal(q.units.length,102);
 assert.equal(dongThapReference.flatMap(r=>r.members).length,308);
 assert.equal(dongThapReference.filter(x=>expandRef(x.name).name.startsWith('Phường ')).length,20);
 assert.equal(new Set(q.units.map(x=>x.code)).size,102);
 for(const r of dongThapReference){const name=expandRef(r.name).name;assert.ok(q.units.some(x=>x.name===name));assert.ok(n.clauses[r.clause-1].normalize('NFC').includes(name.toLowerCase().replace(/^xã |^phường /u,'')) || n.clauses[r.clause-1].normalize('NFC').includes(name.substring(name.indexOf(' ')+1)));}
});
test('partial and remaining territory are distinct from majority/whole',()=>{
 assert.equal(expandRef('Phú Thuận B|part').scope,'part');
 assert.equal(expandRef('Gáo Giồng|remainder').scope,'remainder');
 assert.equal(expandRef('p:3@Thành phố Sa Đéc').district,'Thành phố Sa Đéc');
});
test('unknown input layouts fail visibly rather than skipping every row',()=>{
 assert.throws(()=>parseExtracts({mapping:{sheets:[]}}),/Missing sheet/u);
});
const outputDir=process.env.WARD_REVIEW_DIR;
test('real-source reconciliation is reproducible, complete and non-publishing',{skip:!outputDir},async()=>{
 const extracts=Object.fromEntries(await Promise.all(['mapping','catalog1','catalog34'].map(async key=>[key,await read(path.join(outputDir,key+'-extract.json'))])));
 const input=parseExtracts(extracts);
 const result=normalizeData(input,await source('qd19-dong-thap'),await source('nq1663-dong-thap'),await source('reviewed-aliases'));
 const saved=await read(path.join(outputDir,'normalized-review.json'));
 assert.deepEqual(result.stats,saved.stats);
 assert.equal(result.stats.inputRows,10602);assert.equal(result.stats.newUnits,3321);assert.equal(result.stats.newProvinces,34);
 assert.equal(result.stats.verifiedLinks,308);assert.equal(result.stats.rejectedLinks,6);assert.equal(result.stats.pendingLinks,10288);
 assert.equal(result.stats.unresolvedDongThapMembers,0);assert.equal(result.productionReady,false);
 assert.deepEqual(result.links.filter(x=>x.verification==='rejected_nq1663').map(x=>x.sourceRow),[10427,10430,10442,10445,10595,10597]);
 assert.equal(new Set(result.links.map(x=>x.id)).size,10602);
 assert.equal(result.oldUnits.filter(x=>x.level==='district').length,5);
 for(const l of result.links.filter(x=>x.verification==='verified_nq1663'))assert.ok(l.clause>=1&&l.clause<=102&&l.scope);
 const codes=name=>result.links.filter(x=>x.oldName===name&&x.verification==='verified_nq1663').map(x=>x.newCode).sort();
 assert.deepEqual(codes('Xã Gáo Giồng'),['30025','30088']);
 assert.deepEqual(codes('Xã Phú Thuận B'),['29992','30154']);
 const thanhBinh=result.links.filter(x=>x.oldName==='Xã Tân Thạnh'&&x.oldDistrict==='Huyện Thanh Bình'&&x.verification==='verified_nq1663');
 assert.deepEqual(thanhBinh.map(x=>x.newCode).sort(),['30130','30157']);
 assert.deepEqual(result.links.map(x=>x.id),saved.links.map(x=>x.id));
});
