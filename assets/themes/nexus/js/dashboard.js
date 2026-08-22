/* ====================================================
   TanzArt – dashboard.js  (page-specific logic)
   All nav/dark-mode handled by nav.js
   ==================================================== */

const charts = {};

/* ===== COLORS ===== */
function chartColors() {
  const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  return {
    grid:    dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)',
    text:    dark ? '#94a3b8' : '#64748b',
    tooltip: dark ? '#1e293b' : '#fff',
  };
}

const styleColors = {
  'Salsa':'#ef4444','Samba':'#f59e0b','Tango':'#8b5cf6','Walzer':'#3b82f6',
  'Hip-Hop':'#10b981','Yoga':'#06b6d4','Ballett':'#ec4899','Quickstep':'#f97316',
  'Cha-Cha-Cha':'#84cc16','Kindertanz':'#f43f5e','Wiener Walzer':'#0ea5e9',
};

/* ===== CHART FACTORY ===== */
function makeChart(id, config) {
  if (charts[id]) { charts[id].destroy(); delete charts[id]; }
  const canvas = document.getElementById(id);
  if (!canvas) return null;
  charts[id] = new Chart(canvas, config);
  return charts[id];
}

/* ===== CHART INIT PER PAGE ===== */
function initChartsForPage(page) {
  if (page === 'dashboard')  initDashboardCharts();
  if (page === 'checkin')    initCheckinCharts();
  if (page === 'berichte')   initBerichteCharts();
  if (page === 'zahlungen')  initZahlungenCharts();
}

function initDashboardCharts() {
  const c = chartColors();
  makeChart('chart-revenue', {
    type: 'line',
    data: {
      labels: ['Okt','Nov','Dez','Jan','Feb','Mrz'],
      datasets: [
        { label:'Umsatz (€)', data:[6800,7200,7900,7600,8030,8450], borderColor:'#e11d48', backgroundColor:'rgba(225,29,72,.1)', tension:.4, fill:true, yAxisID:'y', pointBackgroundColor:'#e11d48', pointRadius:4 },
        { label:'Anmeldungen', data:[18,22,15,28,19,24], borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,.08)', tension:.4, fill:true, yAxisID:'y1', pointBackgroundColor:'#6366f1', pointRadius:4, borderDash:[4,2] },
      ],
    },
    options: {
      responsive:true, interaction:{mode:'index',intersect:false},
      plugins:{ legend:{labels:{color:c.text,font:{size:12}}} },
      scales:{
        x:{grid:{color:c.grid},ticks:{color:c.text}},
        y:{position:'left',grid:{color:c.grid},ticks:{color:c.text,callback:v=>v+'€'}},
        y1:{position:'right',grid:{drawOnChartArea:false},ticks:{color:c.text}},
      },
    },
  });

  const styleLabels = ['Salsa','Samba','Tango','Walzer','Hip-Hop','Yoga','Ballett','Quickstep','Cha-Cha-Cha'];
  const styleData   = [52,38,22,30,28,20,24,18,15];
  const styleCol    = styleLabels.map(s => styleColors[s]);
  makeChart('chart-styles', {
    type:'doughnut',
    data:{ labels:styleLabels, datasets:[{data:styleData, backgroundColor:styleCol, borderWidth:2, borderColor:c.tooltip}] },
    options:{ cutout:'65%', responsive:true, plugins:{legend:{display:false}} },
  });

  const leg = document.getElementById('styles-legend');
  if (leg) {
    leg.innerHTML = styleLabels.map((s,i) =>
      `<div class="d-flex align-items-center justify-content-between py-1" style="border-bottom:1px solid var(--border-color)">
        <span style="display:flex;align-items:center;gap:6px;font-size:12px">
          <span style="width:10px;height:10px;border-radius:50%;background:${styleCol[i]};display:inline-block"></span>${s}
        </span>
        <span style="font-size:12px;font-weight:600">${styleData[i]}</span>
      </div>`
    ).join('');
  }

  makeChart('chart-auslastung', {
    type:'bar',
    data:{
      labels:['Salsa','Samba','Tango','Walzer','Hip-Hop','Yoga','Ballett','CCC'],
      datasets:[{ label:'Auslastung %', data:[95,88,82,90,80,100,87,75],
        backgroundColor:Object.values(styleColors).slice(0,8).map(c=>c+'cc'),
        borderRadius:6, borderSkipped:false }],
    },
    options:{
      indexAxis:'y', responsive:true, plugins:{legend:{display:false}},
      scales:{
        x:{max:100,grid:{color:c.grid},ticks:{color:c.text,callback:v=>v+'%'}},
        y:{grid:{display:false},ticks:{color:c.text}},
      },
    },
  });
}

function initCheckinCharts() {
  const c = chartColors();
  makeChart('chart-checkin-today', {
    type:'bar',
    data:{
      labels:['10:00','16:00','16:30','17:30','19:00','20:00','20:30'],
      datasets:[{ label:'Check-Ins', data:[20,12,10,11,13,16,7], backgroundColor:'rgba(225,29,72,.7)', borderRadius:4, borderSkipped:false }],
    },
    options:{
      responsive:true, plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false},ticks:{color:c.text,font:{size:10}}},
        y:{grid:{color:c.grid},ticks:{color:c.text}},
      },
    },
  });
}

function initBerichteCharts() {
  const c = chartColors();
  makeChart('chart-anmeldungen', {
    type:'bar',
    data:{
      labels:['Sep','Okt','Nov','Dez','Jan','Feb','Mrz'],
      datasets:[
        { label:'Anmeldungen', data:[28,22,19,15,31,24,18], backgroundColor:'rgba(225,29,72,.75)', borderRadius:5 },
        { label:'Abmeldungen', data:[4,6,3,8,5,4,2], backgroundColor:'rgba(100,116,139,.45)', borderRadius:5 },
      ],
    },
    options:{
      responsive:true, plugins:{legend:{labels:{color:c.text,font:{size:12}}}},
      scales:{x:{grid:{display:false},ticks:{color:c.text}},y:{grid:{color:c.grid},ticks:{color:c.text}}},
    },
  });
  makeChart('chart-altersgruppen', {
    type:'doughnut',
    data:{
      labels:['Kinder (4-12)','Jugend (13-17)','Erw. 18-35','Erw. 36-55','Erw. 55+'],
      datasets:[{ data:[28,34,88,72,25], backgroundColor:['#f43f5e','#a78bfa','#6366f1','#f59e0b','#10b981'], borderWidth:2, borderColor:c.tooltip }],
    },
    options:{ cutout:'60%', responsive:true, plugins:{legend:{position:'right',labels:{color:c.text,font:{size:11}}}} },
  });
  makeChart('chart-umsatz-stil', {
    type:'bar',
    data:{
      labels:Object.keys(styleColors),
      datasets:[{ label:'Umsatz €', data:[2100,1680,970,1340,1250,900,1080,810,680,420,650],
        backgroundColor:Object.values(styleColors).map(c=>c+'bb'), borderRadius:6, borderSkipped:false }],
    },
    options:{
      responsive:true, plugins:{legend:{display:false}},
      scales:{x:{grid:{display:false},ticks:{color:c.text,font:{size:10}}},y:{grid:{color:c.grid},ticks:{color:c.text,callback:v=>v+'€'}}},
    },
  });
  makeChart('chart-checkin-verlauf', {
    type:'line',
    data:{
      labels:['Do','Fr','Sa','So','Mo','Di','Mi'],
      datasets:[
        { label:'Einzel', data:[42,58,35,0,61,49,52], borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,.1)', tension:.4, fill:true },
        { label:'Paar-TN', data:[28,36,24,0,42,33,37], borderColor:'#e11d48', backgroundColor:'rgba(225,29,72,.08)', tension:.4, fill:true },
      ],
    },
    options:{
      responsive:true, plugins:{legend:{labels:{color:c.text,font:{size:12}}}},
      scales:{x:{grid:{color:c.grid},ticks:{color:c.text}},y:{grid:{color:c.grid},ticks:{color:c.text}}},
    },
  });
}

function initZahlungenCharts() {
  const c = chartColors();
  makeChart('chart-pay-type', {
    type:'doughnut',
    data:{
      labels:['Kinderkurse','Jugendkurse','Erw. Single','Erw. Paar'],
      datasets:[{ data:[1470,2006,2767,2207], backgroundColor:['#f43f5e','#a78bfa','#6366f1','#f59e0b'], borderWidth:2, borderColor:c.tooltip }],
    },
    options:{ cutout:'60%', responsive:true, plugins:{legend:{position:'bottom',labels:{color:c.text,font:{size:11}}}} },
  });
}

/* ===== KURSE ===== */
const kurseData = [
  { cat:'kinder',  style:'kinder',     title:'Kindertanz I',               sub:'4–7 Jahre',   trainer:'Sarah Hoffmann', ti:'SH', tc:'#f43f5e,#fb7185', times:['Mo 16:00','Mi 16:00'], cur:12, max:15, studio:'Studio 1' },
  { cat:'kinder',  style:'kinder',     title:'Kindertanz II',              sub:'8–12 Jahre',  trainer:'Sarah Hoffmann', ti:'SH', tc:'#f43f5e,#fb7185', times:['Di 16:30','Do 16:30'], cur:10, max:12, studio:'Studio 1' },
  { cat:'kinder',  style:'ballett',    title:'Ballett Minis',              sub:'5–8 Jahre',   trainer:'Elena Brandt',   ti:'EB', tc:'#ec4899,#db2777', times:['Sa 10:00'],            cur:8,  max:10, studio:'Studio 2' },
  { cat:'jugend',  style:'hiphop',     title:'Hip-Hop Teens',              sub:'13–17 Jahre', trainer:'Carlos Ruiz',    ti:'CR', tc:'#10b981,#059669', times:['Fr 17:00'],            cur:14, max:16, studio:'Studio 3' },
  { cat:'jugend',  style:'salsa',      title:'Salsa Teens',                sub:'13–17 Jahre', trainer:'Maria Lopez',    ti:'ML', tc:'#ef4444,#dc2626', times:['Mi 17:30'],            cur:11, max:14, studio:'Studio 3' },
  { cat:'jugend',  style:'ballett',    title:'Ballett Jugend',             sub:'12–18 Jahre', trainer:'Elena Brandt',   ti:'EB', tc:'#ec4899,#db2777', times:['Sa 11:30'],            cur:9,  max:12, studio:'Studio 2' },
  { cat:'single',  style:'salsa',      title:'Salsa Solo Beginner',        sub:'Erwachsene',  trainer:'Carlos Ruiz',    ti:'CR', tc:'#10b981,#059669', times:['Mo 19:00','Do 19:00'], cur:18, max:20, studio:'Studio 2' },
  { cat:'single',  style:'salsa',      title:'Salsa Solo Fortgeschritten', sub:'Erwachsene',  trainer:'Carlos Ruiz',    ti:'CR', tc:'#10b981,#059669', times:['Di 20:30'],            cur:12, max:16, studio:'Studio 2' },
  { cat:'single',  style:'samba',      title:'Samba Solo',                 sub:'Erwachsene',  trainer:'Maria Lopez',    ti:'ML', tc:'#ef4444,#dc2626', times:['Mi 19:00'],            cur:15, max:18, studio:'Studio 3' },
  { cat:'single',  style:'hiphop',     title:'Hip-Hop Adults',             sub:'Erwachsene',  trainer:'Carlos Ruiz',    ti:'CR', tc:'#10b981,#059669', times:['Fr 19:30'],            cur:16, max:20, studio:'Studio 3' },
  { cat:'single',  style:'yoga',       title:'Yoga & Tanz',                sub:'Alle Levels', trainer:'Julia Kern',     ti:'JK', tc:'#06b6d4,#0891b2', times:['Mo 18:00','Mi 10:00'], cur:20, max:20, studio:'Studio 2' },
  { cat:'single',  style:'ballett',    title:'Ballett Adults',             sub:'Erwachsene',  trainer:'Elena Brandt',   ti:'EB', tc:'#ec4899,#db2777', times:['Di 19:00','Sa 13:00'], cur:14, max:16, studio:'Studio 1' },
  { cat:'single',  style:'tango',      title:'Tango Solo',                 sub:'Erwachsene',  trainer:'Roberto Vega',   ti:'RV', tc:'#8b5cf6,#7c3aed', times:['Do 20:00'],            cur:10, max:14, studio:'Studio 1' },
  { cat:'paar',    style:'salsa',      title:'Salsa Paare Beginner',       sub:'Paarkurs',    trainer:'Carlos Ruiz',    ti:'CR', tc:'#10b981,#059669', times:['Mo 20:30','Mi 20:00'], cur:8,  max:10, studio:'Studio 2', isPaar:true },
  { cat:'paar',    style:'salsa',      title:'Salsa Paare Fortgeschritten',sub:'Paarkurs',    trainer:'Carlos Ruiz',    ti:'CR', tc:'#10b981,#059669', times:['Fr 20:30'],            cur:6,  max:8,  studio:'Studio 2', isPaar:true },
  { cat:'paar',    style:'samba',      title:'Samba Paare',                sub:'Paarkurs',    trainer:'Maria Lopez',    ti:'ML', tc:'#ef4444,#dc2626', times:['Di 20:00','Sa 15:00'], cur:7,  max:10, studio:'Studio 3', isPaar:true },
  { cat:'paar',    style:'tango',      title:'Tango Argentino Paare',      sub:'Paarkurs',    trainer:'Roberto Vega',   ti:'RV', tc:'#8b5cf6,#7c3aed', times:['Do 21:00'],            cur:5,  max:8,  studio:'Studio 1', isPaar:true },
  { cat:'paar',    style:'walzer',     title:'Walzer Paare',               sub:'Paarkurs',    trainer:'Anna Weber',     ti:'AW', tc:'#3b82f6,#2563eb', times:['Mo 19:30'],            cur:9,  max:12, studio:'Studio 1', isPaar:true },
  { cat:'paar',    style:'quickstep',  title:'Quickstep Paare',            sub:'Paarkurs',    trainer:'Anna Weber',     ti:'AW', tc:'#3b82f6,#2563eb', times:['Do 19:00'],            cur:7,  max:10, studio:'Studio 1', isPaar:true },
  { cat:'paar',    style:'chacha',     title:'Cha-Cha-Cha Paare',          sub:'Paarkurs',    trainer:'Anna Weber',     ti:'AW', tc:'#3b82f6,#2563eb', times:['Mi 20:30'],            cur:6,  max:8,  studio:'Studio 1', isPaar:true },
  { cat:'paar',    style:'wiener',     title:'Wiener Walzer',              sub:'Paarkurs',    trainer:'Anna Weber',     ti:'AW', tc:'#3b82f6,#2563eb', times:['Sa 16:30'],            cur:5,  max:8,  studio:'Studio 1', isPaar:true },
];

const styleBarColors = { kinder:'#f43f5e', salsa:'#ef4444', samba:'#f59e0b', tango:'#8b5cf6', walzer:'#3b82f6', hiphop:'#10b981', yoga:'#06b6d4', ballett:'#ec4899', quickstep:'#f97316', chacha:'#84cc16', wiener:'#0ea5e9' };
function catLabel(c) { return {kinder:'Kinder',jugend:'Jugend',single:'Single',paar:'Paar'}[c]||c; }

function renderKurse(filter) {
  const grid = document.getElementById('kurse-grid'); if (!grid) return;
  const data = filter === 'alle' ? kurseData : kurseData.filter(k => k.cat === filter);
  grid.innerHTML = data.map(k => {
    const pct = Math.round(k.cur/k.max*100);
    const pctC = pct>=95?'#ef4444':pct>=80?'#f59e0b':'#10b981';
    return `<div class="col-sm-6 col-lg-4 col-xl-3">
      <div class="card kurs-card">
        <div class="kurs-card-top" style="background:${styleBarColors[k.style]||'#6366f1'}"></div>
        <div class="kurs-card-body">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="cat-badge cat-${k.cat}">${catLabel(k.cat)}</span>
            ${k.isPaar?'<i class="bi bi-heart-fill text-danger"></i>':''}
          </div>
          <div class="kurs-title mt-2">${k.title}</div>
          <div class="kurs-meta">${k.sub} &mdash; ${k.studio}</div>
          <div class="kurs-trainer">
            <div class="av" style="background:linear-gradient(135deg,${k.tc});width:30px;height:30px;font-size:11px">${k.ti}</div>
            <div class="trainer-name">${k.trainer}</div>
          </div>
          <div class="kurs-times">${k.times.map(t=>`<span class="kurs-time-badge"><i class="bi bi-clock"></i>${t}</span>`).join('')}</div>
          <div class="kurs-capacity">
            <div class="capacity-text"><span>${k.cur} Teiln.</span><span>${pct}% belegt</span></div>
            <div class="capacity-bar"><div class="capacity-inner" style="width:${pct}%;background:${pctC}"></div></div>
            <div class="capacity-text"><span></span><span>max. ${k.max}</span></div>
          </div>
          <div class="mt-2 pt-2 border-top d-flex gap-1">
            <a href="kurs-edit.html" class="btn btn-sm btn-outline-secondary flex-fill"><i class="bi bi-pencil me-1"></i>Bearbeiten</a>
            <a href="checkin.html" class="btn btn-sm btn-outline-danger"><i class="bi bi-check2-circle"></i></a>
          </div>
        </div>
      </div>
    </div>`;
  }).join('');
}

function filterKurse(cat, btn) {
  document.querySelectorAll('#kurse-filter .nav-link').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderKurse(cat);
}

/* ===== SCHÜLER ===== */
const singlesData = [
  { i:'AS', n:'Anna Schmidt',      s:'seit 2023', c:'#e11d48,#f59e0b', k:['Salsa Solo','Samba Solo'] },
  { i:'PM', n:'Peter Meier',       s:'seit 2024', c:'#10b981,#059669', k:['Hip-Hop Adults'] },
  { i:'LW', n:'Lisa Wagner',       s:'seit 2022', c:'#06b6d4,#0891b2', k:['Yoga & Tanz','Ballett Adults'] },
  { i:'MB', n:'Max Bauer',         s:'seit 2024', c:'#8b5cf6,#7c3aed', k:['Tango Solo'] },
  { i:'SF', n:'Sophie Fischer',    s:'seit 2023', c:'#f43f5e,#e11d48', k:['Salsa Solo Beginner'] },
  { i:'TH', n:'Tobias Hartmann',   s:'seit 2024', c:'#f59e0b,#d97706', k:['Hip-Hop Adults','Samba Solo'] },
  { i:'KN', n:'Kathrin Neumann',   s:'seit 2023', c:'#ec4899,#db2777', k:['Ballett Adults'] },
  { i:'FR', n:'Felix Richter',     s:'seit 2022', c:'#3b82f6,#2563eb', k:['Salsa Beginner','Tango Solo'] },
  { i:'MS', n:'Melanie Schulze',   s:'seit 2024', c:'#84cc16,#65a30d', k:['Yoga & Tanz'] },
  { i:'JK', n:'Jonas Koch',        s:'seit 2023', c:'#f97316,#ea580c', k:['Hip-Hop Adults'] },
  { i:'AH', n:'Andrea Hoffmann',   s:'seit 2021', c:'#0ea5e9,#0284c7', k:['Walzer','Ballett Adults'] },
  { i:'CW', n:'Chris Wolf',        s:'seit 2024', c:'#a78bfa,#7c3aed', k:['Salsa Fortgeschr.'] },
];

const paareData = [
  { p1i:'MK', p1n:'Maria Klein',      p2i:'TK', p2n:'Thomas Klein',   c1:'#e11d48,#be123c', c2:'#3b82f6,#1d4ed8', s:'seit 2022', k:['Salsa Paare','Quickstep'] },
  { p1i:'JS', p1n:'Jennifer Schulz',  p2i:'MS', p2n:'Michael Schulz', c1:'#ec4899,#db2777', c2:'#10b981,#059669', s:'seit 2023', k:['Walzer Paare','Tango'] },
  { p1i:'LB', p1n:'Laura Braun',      p2i:'DB', p2n:'David Braun',    c1:'#f59e0b,#d97706', c2:'#6366f1,#4f46e5', s:'seit 2023', k:['Samba Paare'] },
  { p1i:'SW', p1n:'Sandra Weber',     p2i:'KW', p2n:'Klaus Weber',    c1:'#8b5cf6,#7c3aed', c2:'#f97316,#ea580c', s:'seit 2021', k:['Cha-Cha-Cha','Walzer'] },
  { p1i:'CM', p1n:'Christina Müller', p2i:'RM', p2n:'Robert Müller',  c1:'#06b6d4,#0891b2', c2:'#84cc16,#65a30d', s:'seit 2022', k:['Tango Argentino'] },
  { p1i:'PH', p1n:'Petra Heinz',      p2i:'AH', p2n:'Andreas Heinz',  c1:'#0ea5e9,#0284c7', c2:'#a78bfa,#7c3aed', s:'seit 2024', k:['Wiener Walzer','Quickstep'] },
  { p1i:'NK', p1n:'Nicole König',     p2i:'SK', p2n:'Stefan König',   c1:'#f43f5e,#e11d48', c2:'#10b981,#059669', s:'seit 2023', k:['Salsa Paare Fortgeschr.'] },
  { p1i:'BL', p1n:'Barbara Lang',     p2i:'ML', p2n:'Martin Lang',    c1:'#ec4899,#db2777', c2:'#f59e0b,#d97706', s:'seit 2024', k:['Samba Paare','Cha-Cha-Cha'] },
];

function renderSingles() {
  const grid = document.getElementById('singles-grid'); if (!grid) return;
  grid.innerHTML = singlesData.map(s => `
    <div class="col-sm-6 col-lg-4 col-xl-3">
      <div class="card pair-student-card">
        <div class="student-card-body">
          <div class="av av-lg" style="background:linear-gradient(135deg,${s.c})">${s.i}</div>
          <div class="student-info" style="flex:1">
            <div class="student-name">${s.n}</div>
            <div class="student-since">${s.s}</div>
            <div class="student-courses">${s.k.map(k=>`<span class="bs bs-muted">${k}</span>`).join('')}</div>
          </div>
          <a href="schueler-edit.html" class="btn btn-sm btn-outline-secondary ms-auto align-self-start" title="Bearbeiten"><i class="bi bi-pencil"></i></a>
        </div>
      </div>
    </div>`).join('');
}

function renderPaare() {
  const grid = document.getElementById('paare-grid'); if (!grid) return;
  grid.innerHTML = paareData.map(p => `
    <div class="col-sm-6 col-xl-4">
      <div class="card pair-student-card">
        <div class="pair-members">
          <div class="pair-member">
            <div class="av av-lg" style="background:linear-gradient(135deg,${p.c1})">${p.p1i}</div>
            <div class="member-name">${p.p1n}</div>
            <div class="member-gender" style="color:#ec4899">&#9792; Dame</div>
          </div>
          <div class="pair-divider"><i class="bi bi-heart-fill"></i></div>
          <div class="pair-member">
            <div class="av av-lg" style="background:linear-gradient(135deg,${p.c2})">${p.p2i}</div>
            <div class="member-name">${p.p2n}</div>
            <div class="member-gender" style="color:#3b82f6">&#9794; Herr</div>
          </div>
        </div>
        <div class="pair-card-footer">
          <span class="text-secondary" style="font-size:11px">${p.s}</span>
          <div class="d-flex flex-wrap gap-1">${p.k.map(k=>`<span class="bs bs-muted">${k}</span>`).join('')}</div>
          <a href="schueler-edit.html?typ=paar" class="btn btn-sm btn-outline-secondary ms-auto" title="Bearbeiten"><i class="bi bi-pencil"></i></a>
        </div>
      </div>
    </div>`).join('');
}

function showSchueler(type) {
  const singles = document.getElementById('schueler-singles');
  const paare   = document.getElementById('schueler-paare');
  const btnS    = document.getElementById('btn-singles');
  const btnP    = document.getElementById('btn-paare');
  if (type === 'singles') {
    singles.style.display=''; paare.style.display='none';
    btnS.classList.add('active'); btnP.classList.remove('active');
  } else {
    singles.style.display='none'; paare.style.display='';
    btnP.classList.add('active'); btnS.classList.remove('active');
  }
}

/* ===== LEHRER ===== */
const lehrerData = [
  { i:'CR', n:'Carlos Ruiz',    sp:'Salsa & Hip-Hop',    k:8,  s:84, r:'4.9', c:'#10b981,#059669', styles:['Salsa','Samba','Hip-Hop'] },
  { i:'ML', n:'Maria Lopez',    sp:'Samba & Latin',      k:5,  s:61, r:'4.8', c:'#ef4444,#dc2626', styles:['Samba','Salsa','Cha-Cha-Cha'] },
  { i:'SH', n:'Sarah Hoffmann', sp:'Kindertanz',         k:3,  s:30, r:'4.7', c:'#f43f5e,#e11d48', styles:['Kindertanz','Ballett'] },
  { i:'EB', n:'Elena Brandt',   sp:'Ballett',            k:3,  s:31, r:'5.0', c:'#ec4899,#db2777', styles:['Ballett'] },
  { i:'JK', n:'Julia Kern',     sp:'Yoga & Tanz',        k:2,  s:20, r:'4.6', c:'#06b6d4,#0891b2', styles:['Yoga','Modern'] },
  { i:'RV', n:'Roberto Vega',   sp:'Tango Argentino',    k:2,  s:15, r:'4.9', c:'#8b5cf6,#7c3aed', styles:['Tango'] },
  { i:'AW', n:'Anna Weber',     sp:'Standard & Latein',  k:4,  s:54, r:'4.8', c:'#3b82f6,#2563eb', styles:['Walzer','Quickstep','CCC','Wiener Walzer'] },
];

function styleToClass(s) {
  return s.toLowerCase().replace(/[^a-z]/g,'').replace('ccc','chacha').replace('wienerwalzer','wiener');
}

function renderLehrer() {
  const grid = document.getElementById('lehrer-grid'); if (!grid) return;
  grid.innerHTML = lehrerData.map(l => `
    <div class="col-sm-6 col-lg-4 col-xl-3">
      <div class="card lehrer-card">
        <div class="lehrer-card-header" style="background:linear-gradient(135deg,${l.c})">
          <div class="lehrer-avatar-wrap">
            <div class="av av-xl" style="background:linear-gradient(135deg,${l.c});border:3px solid var(--bg-card);box-shadow:0 4px 12px rgba(0,0,0,.2)">${l.i}</div>
          </div>
        </div>
        <div class="lehrer-card-body">
          <div class="lehrer-name">${l.n}</div>
          <div class="lehrer-spec">${l.sp}</div>
          <div class="lehrer-stats">
            <div class="lehrer-stat"><div class="ls-val">${l.k}</div><div class="ls-lbl">Kurse</div></div>
            <div class="lehrer-stat"><div class="ls-val">${l.s}</div><div class="ls-lbl">Schüler</div></div>
            <div class="lehrer-stat"><div class="ls-val" style="color:#f59e0b">${l.r}★</div><div class="ls-lbl">Rating</div></div>
          </div>
          <div class="lehrer-styles">${l.styles.map(s=>`<span class="style-badge style-${styleToClass(s)}">${s}</span>`).join('')}</div>
          <div class="mt-2 pt-2 border-top">
            <a href="lehrer-edit.html" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-pencil me-1"></i>Bearbeiten</a>
          </div>
        </div>
      </div>
    </div>`).join('');
}

/* ===== CHECK-IN ===== */
const checkinDemoData = [
  { type:'paar',   p1n:'Maria Klein',      p1i:'MK', p1c:'#e11d48,#be123c', p2n:'Thomas Klein',   p2i:'TK', p2c:'#3b82f6,#1d4ed8', kurs:'Salsa Paare' },
  { type:'single', name:'Anna Schmidt',    init:'AS', col:'#e11d48,#f59e0b', kurs:'Samba Solo' },
  { type:'paar',   p1n:'Jennifer Schulz',  p1i:'JS', p1c:'#ec4899,#db2777', p2n:'Michael Schulz', p2i:'MS', p2c:'#10b981,#059669', kurs:'Walzer Paare' },
  { type:'single', name:'Lisa Wagner',     init:'LW', col:'#06b6d4,#0891b2', kurs:'Yoga & Tanz' },
  { type:'single', name:'Peter Meier',     init:'PM', col:'#10b981,#059669', kurs:'Hip-Hop Adults' },
  { type:'paar',   p1n:'Laura Braun',      p1i:'LB', p1c:'#f59e0b,#d97706', p2n:'David Braun',    p2i:'DB', p2c:'#6366f1,#4f46e5', kurs:'Samba Paare' },
  { type:'single', name:'Sophie Fischer',  init:'SF', col:'#f43f5e,#e11d48', kurs:'Salsa Beginner' },
  { type:'paar',   p1n:'Sandra Weber',     p1i:'SW', p1c:'#8b5cf6,#7c3aed', p2n:'Klaus Weber',    p2i:'KW', p2c:'#f97316,#ea580c', kurs:'Cha-Cha-Cha' },
  { type:'single', name:'Max Bauer',       init:'MB', col:'#8b5cf6,#7c3aed', kurs:'Tango Solo' },
  { type:'paar',   p1n:'Petra Heinz',      p1i:'PH', p1c:'#0ea5e9,#0284c7', p2n:'Andreas Heinz',  p2i:'AH', p2c:'#a78bfa,#7c3aed', kurs:'Quickstep' },
];
let ciDemoIdx = 0, ciCount = 89;

function renderCiItem(ci, time) {
  if (ci.type === 'paar') {
    return `<div class="checkin-item">
      <div class="checkin-pair-wrapper" style="flex:1">
        <div class="checkin-person"><div class="av av-sm" style="background:linear-gradient(135deg,${ci.p1c})">${ci.p1i}</div><div><div class="cp-name">${ci.p1n}</div></div></div>
        <i class="bi bi-heart-fill checkin-heart mx-2"></i>
        <div class="checkin-person"><div class="av av-sm" style="background:linear-gradient(135deg,${ci.p2c})">${ci.p2i}</div><div><div class="cp-name">${ci.p2n}</div></div></div>
      </div>
      <div class="d-flex flex-column align-items-end gap-1">
        <span class="checkin-time">${time}</span>
        <span class="checkin-kurs">${ci.kurs}</span>
      </div>
    </div>`;
  } else {
    return `<div class="checkin-item">
      <div class="checkin-pair-wrapper" style="flex:1">
        <div class="checkin-person"><div class="av av-sm" style="background:linear-gradient(135deg,${ci.col})">${ci.init}</div><div><div class="cp-name">${ci.name}</div></div></div>
      </div>
      <div class="d-flex flex-column align-items-end gap-1">
        <span class="checkin-time">${time}</span>
        <span class="checkin-kurs">${ci.kurs}</span>
      </div>
    </div>`;
  }
}

function renderCheckinFeed() {
  const feed = document.getElementById('checkin-feed'); if (!feed) return;
  const times = ['09:55','10:12','10:18','16:02','16:08','16:33','17:31','19:02','19:45','19:52','19:58','20:01'];
  feed.innerHTML = [...checkinDemoData].reverse().map((ci, idx) =>
    renderCiItem(ci, times[times.length - 1 - idx] || '20:30')
  ).join('');
}

function demoCheckin() {
  const ci = checkinDemoData[ciDemoIdx++ % checkinDemoData.length];
  const now = new Date();
  const time = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
  const feed = document.getElementById('checkin-feed'); if (!feed) return;
  feed.insertAdjacentHTML('afterbegin', `<div class="checkin-new">${renderCiItem(ci, time)}</div>`);
  ciCount++;
  const cnt = document.getElementById('ci-count'); if (cnt) cnt.textContent = ciCount;
}

function searchCheckin(val) {
  const sug = document.getElementById('checkin-suggestions'); if (!sug) return;
  if (!val.trim()) { sug.innerHTML = ''; return; }
  const all = [
    ...singlesData.map(s => ({ label:s.n, init:s.i, col:s.c, kurs:s.k[0]||'', type:'single' })),
    ...paareData.map(p => ({ label:p.p1n+' & '+p.p2n, init:p.p1i, col:p.c1, kurs:p.k[0]||'', type:'paar' })),
  ];
  const matches = all.filter(s => s.label.toLowerCase().includes(val.toLowerCase())).slice(0,6);
  sug.innerHTML = matches.map(m =>
    `<button class="btn btn-sm btn-outline-secondary" onclick="quickCheckin('${m.label}','${m.init}','${m.col}','${m.kurs}','${m.type}')">
      <div class="av av-sm d-inline-flex me-1" style="background:linear-gradient(135deg,${m.col});vertical-align:middle">${m.init}</div>${m.label}
    </button>`
  ).join('');
}

function quickCheckin(label, init, col, kurs, type) {
  const now = new Date();
  const time = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
  const feed = document.getElementById('checkin-feed'); if (!feed) return;
  feed.insertAdjacentHTML('afterbegin', `<div class="checkin-new">${renderCiItem({type,name:label,init,col,kurs}, time)}</div>`);
  ciCount++;
  const cnt = document.getElementById('ci-count'); if (cnt) cnt.textContent = ciCount;
  const inp = document.getElementById('checkinInput'); if (inp) inp.value = '';
  const sug = document.getElementById('checkin-suggestions'); if (sug) sug.innerHTML = '';
}

/* ===== STUNDENPLAN ===== */
const scheduleData = {
  Mo:[ {time:'18:00',title:'Yoga & Tanz',trainer:'J. Kern',style:'yoga'},{time:'19:00',title:'Salsa Solo',trainer:'C. Ruiz',style:'salsa'},{time:'19:30',title:'Walzer Paare',trainer:'A. Weber',style:'walzer'},{time:'20:30',title:'Salsa Paare',trainer:'C. Ruiz',style:'salsa'} ],
  Di:[ {time:'16:30',title:'Kindertanz II',trainer:'S. Hoffmann',style:'kinder'},{time:'19:00',title:'Ballett Adults',trainer:'E. Brandt',style:'ballett'},{time:'20:00',title:'Samba Paare',trainer:'M. Lopez',style:'samba'},{time:'20:30',title:'Salsa Solo Fortg.',trainer:'C. Ruiz',style:'salsa'} ],
  Mi:[ {time:'10:00',title:'Yoga & Tanz',trainer:'J. Kern',style:'yoga'},{time:'16:00',title:'Kindertanz I',trainer:'S. Hoffmann',style:'kinder'},{time:'16:30',title:'Kindertanz II',trainer:'S. Hoffmann',style:'kinder'},{time:'17:30',title:'Salsa Teens',trainer:'M. Lopez',style:'salsa'},{time:'19:00',title:'Samba Solo',trainer:'M. Lopez',style:'samba'},{time:'20:00',title:'Salsa Paare',trainer:'C. Ruiz',style:'salsa'},{time:'20:30',title:'Cha-Cha-Cha',trainer:'A. Weber',style:'chacha'} ],
  Do:[ {time:'19:00',title:'Salsa Solo',trainer:'C. Ruiz',style:'salsa'},{time:'19:00',title:'Quickstep Paare',trainer:'A. Weber',style:'quickstep'},{time:'20:00',title:'Tango Solo',trainer:'R. Vega',style:'tango'},{time:'21:00',title:'Tango Argentino',trainer:'R. Vega',style:'tango'} ],
  Fr:[ {time:'17:00',title:'Hip-Hop Teens',trainer:'C. Ruiz',style:'hiphop'},{time:'19:30',title:'Hip-Hop Adults',trainer:'C. Ruiz',style:'hiphop'},{time:'20:30',title:'Salsa Paare Fortg.',trainer:'C. Ruiz',style:'salsa'} ],
  Sa:[ {time:'10:00',title:'Ballett Minis',trainer:'E. Brandt',style:'ballett'},{time:'11:30',title:'Ballett Jugend',trainer:'E. Brandt',style:'ballett'},{time:'13:00',title:'Ballett Adults',trainer:'E. Brandt',style:'ballett'},{time:'15:00',title:'Samba Paare',trainer:'M. Lopez',style:'samba'},{time:'16:30',title:'Wiener Walzer',trainer:'A. Weber',style:'walzer'} ],
};

function renderSchedule() {
  const grid = document.getElementById('schedule-grid'); if (!grid) return;
  const days = ['Mo','Di','Mi','Do','Fr','Sa'];
  const times = ['10:00','11:30','13:00','15:00','16:00','16:30','17:00','17:30','18:00','19:00','19:30','20:00','20:30','21:00'];
  let html = '<div class="sg-head"></div>';
  days.forEach(d => { html += `<div class="sg-head">${d}</div>`; });
  times.forEach(t => {
    html += `<div class="sg-time">${t}</div>`;
    days.forEach(d => {
      const courses = (scheduleData[d]||[]).filter(c => c.time === t);
      html += `<div class="sg-cell">${courses.map(c=>`<div class="course-block cb-${c.style}" title="${c.title}"><div class="cb-name">${c.title}</div><div class="cb-trainer">${c.trainer}</div></div>`).join('')}</div>`;
    });
  });
  grid.innerHTML = html;
}

/* ===== SETTINGS ===== */
function showSettings(tab, btn) {
  document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
  const el = document.getElementById('settings-' + tab); if (el) el.style.display = '';
  document.querySelectorAll('#settings-nav .nav-link').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

/* ===== AUTO-INIT ===== */
document.addEventListener('DOMContentLoaded', function () {
  const page = document.body.dataset.page || '';
  if (page === 'kurse')       renderKurse('alle');
  if (page === 'schueler')    { renderSingles(); renderPaare(); }
  if (page === 'lehrer')      renderLehrer();
  if (page === 'stundenplan') renderSchedule();
  if (page === 'checkin')     renderCheckinFeed();
  setTimeout(() => initChartsForPage(page), 80);
});
