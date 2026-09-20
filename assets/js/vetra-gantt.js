(() => {
  'use strict';
  const J = window.VetraJalali;
  const months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const day = 86400000, utc = iso => Date.parse(iso + 'T00:00:00Z'), iso = ms => new Date(ms).toISOString().slice(0,10);
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  document.querySelectorAll('.vg-root').forEach(root => {
    const q = s => root.querySelector(s), grid = q('.vg-grid-body'), chart = q('.vg-chart-body'), content = q('.vg-chart-content'), head = q('.vg-chart-head'), status = q('.vg-status'), dialog = q('.vg-dialog'), form = q('.vg-form'), deleteButton = q('.vg-delete');
    const projectId = Number(root.dataset.project);
    let tasks = [], deps = [], project, editing = null, view = 'gantt', initialForm = '';
    function message(text, error = false) { status.textContent = text; status.classList.toggle('vg-error', error); }
    async function api(path, method = 'GET', body) {
      const response = await fetch(VG_CONFIG.restBase + path, {method, headers: {'X-WP-Nonce':VG_CONFIG.nonce, ...(body ? {'Content-Type':'application/json'} : {})}, credentials:'same-origin', body:body ? JSON.stringify(body) : undefined});
      const data = await response.json();
      if (!response.ok) throw Error(data.message || 'ارتباط با سرور ناموفق بود.');
      return data;
    }
    async function load() {
      try { message('در حال بارگذاری…'); const data = await api(`/projects/${projectId}/tasks`); tasks = data.tasks; deps = data.dependencies; project = data.project; render(); message(''); }
      catch (err) { message(err.message, true); }
    }
    function predecessorText(id) { return deps.filter(d => Number(d.task_id) === Number(id)).map(d => { const t = tasks.find(t => Number(t.id) === Number(d.depends_on)); return `${t?.wbs_code || d.depends_on}${d.dep_type}${Number(d.lag_days) >= 0 ? '+' : ''}${d.lag_days}`; }).join(', '); }
    function depth(t) { let n = 0, seen = new Set([Number(t.id)]); while (t.parent_id && n < 20) { const p = tasks.find(x => Number(x.id) === Number(t.parent_id)); if (!p || seen.has(Number(p.id))) break; seen.add(Number(p.id)); n++; t = p; } return n; }
    function columns() {
      const values = tasks.flatMap(t => [t.start_date, t.end_date].filter(Boolean).map(utc));
      const min = Math.min(utc(project.start_date), ...values) - 7 * day;
      const max = Math.max(utc(project.start_date) + 35 * day, ...values) + 14 * day;
      return {min, count:Math.min(3650, Math.ceil((max - min) / day) + 1)};
    }
    function render() {
      if (view === 'calendar') { renderCalendar(); return; }
      grid.innerHTML = tasks.length ? tasks.map(t => `<div class="vg-row ${t.task_type === 'summary' ? 'vg-summary' : ''}" data-id="${Number(t.id)}"><span dir="ltr">${escape(t.wbs_code)}</span><span class="vg-title" style="--depth:${depth(t)}">${escape(t.title)}</span><span>${Number(t.duration)}</span><span dir="ltr">${J.display(t.start_date)}</span><span dir="ltr">${J.display(t.end_date)}</span><span dir="ltr">${escape(predecessorText(t.id))}</span><span>${Number(t.weight_percent)}</span><span class="vg-row-tools"><button type="button" data-action="edit" title="ویرایش" aria-label="ویرایش ${escape(t.title)}">✎</button><button type="button" data-action="up" title="بالا" aria-label="انتقال به بالا">↑</button><button type="button" data-action="down" title="پایین" aria-label="انتقال به پایین">↓</button><button type="button" data-action="indent" title="تورفتگی" aria-label="تورفتگی">←</button><button type="button" data-action="outdent" title="بیرون‌رفتگی" aria-label="بیرون‌رفتگی">→</button></span></div>`).join('') : '<p class="vg-empty">هنوز فعالیتی ثبت نشده است.</p>';
      const {min,count} = columns(), zoom = q('.vg-zoom').value, width = zoom === 'day' ? 38 : zoom === 'week' ? 22 : 10, w = count * width;
      const x = date => ((utc(date) - min) / day) * width;
      head.innerHTML = `<div style="width:${w}px">${Array.from({length:count}, (_,i) => { const date = iso(min + i*day), j = J.gregorianToJalali(...date.split('-').map(Number)); const show = zoom === 'day' || (zoom === 'week' && new Date(min+i*day).getUTCDay() === 6) || (zoom === 'month' && j[2] === 1); return `<span style="width:${width}px">${show ? (zoom === 'month' ? months[j[1]-1] : j[2]) : ''}</span>`; }).join('')}</div>`;
      content.style.width = w + 'px'; content.style.height = tasks.length * 40 + 'px';
      content.innerHTML = `<div class="vg-lines" style="background-size:${width}px 40px"></div>` + tasks.map((t,i) => {
        if (!t.start_date || !t.end_date) return '';
        const left = x(t.start_date), barWidth = Math.max(width * .65, x(t.end_date) - left + width);
        return `<div class="vg-bar ${t.task_type === 'summary' ? 'vg-bar-summary' : ''} ${t.task_type === 'milestone' ? 'vg-milestone' : ''}" style="left:${left}px;top:${i*40+10}px;width:${t.task_type === 'milestone' ? 16 : barWidth}px" title="${escape(t.title)} — ${J.display(t.start_date)} تا ${J.display(t.end_date)}" aria-label="${escape(t.title)}"></div>`;
      }).join('') + `<div class="vg-today" style="left:${((Date.now()-min)/day)*width}px"></div>`;
      const byId = new Map(tasks.map((t,i) => [Number(t.id),{t,i}]));
      const paths = deps.map(d => { const a = byId.get(Number(d.depends_on)), b = byId.get(Number(d.task_id)); if (!a || !b || !a.t.start_date || !b.t.start_date) return '';
        const from = x(d.dep_type[0] === 'F' ? a.t.end_date : a.t.start_date) + (d.dep_type[0] === 'F' ? width : 0), to = x(d.dep_type[1] === 'F' ? b.t.end_date : b.t.start_date) + (d.dep_type[1] === 'F' ? width : 0), y1 = a.i*40+20, y2 = b.i*40+20, mid = from + (to >= from ? 8 : -8);
        return `<path d="M${from} ${y1} H${mid} V${y2} H${to}"/>`; }).join('');
      content.insertAdjacentHTML('beforeend', `<svg class="vg-arrows" width="${w}" height="${tasks.length*40}" aria-hidden="true"><defs><marker id="vg-arrow-${projectId}" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto"><path d="M0 0 L6 3 L0 6"/></marker></defs><g marker-end="url(#vg-arrow-${projectId})">${paths}</g></svg>`);
    }
    function renderCalendar() {
      const el = q('.vg-calendar'), anchor = tasks.find(t => t.start_date)?.start_date || project.start_date;
      const [jy,jm] = J.gregorianToJalali(...anchor.split('-').map(Number));
      const first = J.jalaliToGregorian(jy,jm,1).map((n,i) => i ? String(n).padStart(2,'0') : n).join('-');
      const next = jm === 12 ? J.jalaliToGregorian(jy+1,1,1) : J.jalaliToGregorian(jy,jm+1,1);
      const end = next.map((n,i) => i ? String(n).padStart(2,'0') : n).join('-');
      const pad = (new Date(utc(first)).getUTCDay()+1)%7, days = Math.round((utc(end)-utc(first))/day);
      el.innerHTML = `<h3>${months[jm-1]} ${jy}</h3><div class="vg-calendar-grid">${['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'].map(s => `<strong>${s}</strong>`).join('')}${'<div></div>'.repeat(pad)}${Array.from({length:days},(_,i) => { const date=iso(utc(first)+i*day); const active=tasks.filter(t=>t.task_type!=='summary' && t.start_date<=date && t.end_date>=date); return `<div><b>${i+1}</b>${active.slice(0,3).map(t=>`<small title="${escape(t.title)}">${escape(t.title)}</small>`).join('')}${active.length>3?`<small>+${active.length-3}</small>`:''}</div>`; }).join('')}</div>`;
    }
    function open(t) {
      editing = t || null; form.reset();
      deleteButton.hidden = !editing;
      const parent = form.elements.parent_id;
      parent.innerHTML = '<option value="">بدون سرگروه</option>' + tasks.filter(x => x.task_type === 'summary' && x.id !== t?.id).map(x=>`<option value="${Number(x.id)}">${escape(x.wbs_code)} ${escape(x.title)}</option>`).join('');
      if (t) for (const field of ['title','parent_id','task_type','schedule_mode','duration','weight_percent']) form.elements[field].value = t[field] ?? '';
      form.elements.start_jalali.value = t?.start_date ? J.display(t.start_date) : '';
      form.elements.end_jalali.value = t?.schedule_mode === 'manual' ? J.display(t.end_date) : '';
      form.elements.predecessors.value = t ? predecessorText(t.id) : '';
      syncFormMode(); initialForm = new URLSearchParams(new FormData(form)).toString();
      dialog.showModal();
    }
    function syncFormMode() {
      const milestone = form.elements.task_type.value === 'milestone', manual = form.elements.schedule_mode.value === 'manual';
      form.elements.duration.disabled = milestone; if (milestone) form.elements.duration.value = 0;
      form.elements.end_jalali.disabled = !manual; form.elements.end_jalali.required = manual;
    }
    function parseDeps(value) {
      if (!value.trim()) return [];
      return value.split(',').map(part => {
        const m = /^\s*([\w.]+)\s*(FS|SS|FF|SF)?\s*([+-]\d+)?\s*$/.exec(part.trim().toUpperCase());
        if (!m) throw Error('فرمت پیش‌نیاز نامعتبر است.');
        const t = tasks.find(x => x.wbs_code === m[1] || String(x.id) === m[1]);
        if (!t || t.id === editing?.id) throw Error('پیش‌نیاز در این پروژه یافت نشد.');
        return {depends_on:Number(t.id),dep_type:m[2] || 'FS',lag_days:Number(m[3] || 0)};
      });
    }
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      try {
        const data = Object.fromEntries(new FormData(form));
        data.start_date = J.parse(data.start_jalali); data.end_date = J.parse(data.end_jalali);
        data.dependencies = parseDeps(data.predecessors);
        data.parent_id = data.parent_id ? Number(data.parent_id) : null;
        data.duration = Number(data.duration); data.weight_percent = Number(data.weight_percent);
        delete data.start_jalali; delete data.end_jalali; delete data.predecessors;
        submit.disabled = true;
        await api(editing ? `/tasks/${editing.id}` : `/projects/${projectId}/tasks`, editing ? 'PUT' : 'POST', data);
        initialForm = new URLSearchParams(new FormData(form)).toString(); dialog.close(); await load(); message('فعالیت ذخیره شد.');
      } catch (err) { message(err.message, true); }
      finally { submit.disabled = false; }
    });
    async function saveOrder() { await api(`/projects/${projectId}/reorder`, 'POST', {items:tasks.map(t=>({id:Number(t.id),parent_id:t.parent_id?Number(t.parent_id):null}))}); await load(); }
    async function arrange(task, action) {
      const index = tasks.indexOf(task), siblings = tasks.filter(t=>Number(t.parent_id||0)===Number(task.parent_id||0)), siblingIndex=siblings.indexOf(task);
      if (action === 'up' || action === 'down') { const other=siblings[siblingIndex+(action==='up'?-1:1)]; if(!other)return; const otherIndex=tasks.indexOf(other); [tasks[index],tasks[otherIndex]]=[tasks[otherIndex],tasks[index]]; }
      if (action === 'indent') { const previous=siblings[siblingIndex-1]; if(!previous||previous.task_type!=='summary')throw Error('برای تورفتگی، ردیف قبلی باید سرگروه باشد.'); task.parent_id=Number(previous.id); }
      if (action === 'outdent') { if(!task.parent_id)return; const parent=tasks.find(t=>Number(t.id)===Number(task.parent_id)); task.parent_id=parent?.parent_id?Number(parent.parent_id):null; }
      await saveOrder();
    }
    q('.vg-add').addEventListener('click', () => open(null));
    q('.vg-cancel').addEventListener('click', () => { if(new URLSearchParams(new FormData(form)).toString()!==initialForm&&!window.confirm('تغییرات ذخیره‌نشده کنار گذاشته شود؟'))return; dialog.close(); });
    deleteButton.addEventListener('click', async () => {
      if (!editing || !window.confirm(`فعالیت «${editing.title}» حذف شود؟ این کار قابل بازگشت نیست.`)) return;
      try {
        deleteButton.disabled = true;
        await api(`/tasks/${editing.id}`, 'DELETE');
        dialog.close(); await load();
      } catch (err) { message(err.message, true); }
      finally { deleteButton.disabled = false; }
    });
    grid.addEventListener('click', async e => { const btn=e.target.closest('[data-action]'); if(!btn)return; const task=tasks.find(t=>Number(t.id)===Number(btn.closest('.vg-row').dataset.id)); try{if(btn.dataset.action==='edit')open(task);else{btn.disabled=true;await arrange(task,btn.dataset.action);}}catch(err){message(err.message,true);}finally{btn.disabled=false;} });
    form.elements.task_type.addEventListener('change', syncFormMode); form.elements.schedule_mode.addEventListener('change', syncFormMode);
    q('.vg-export').addEventListener('click', () => { const quote=v=>`"${String(v??'').replace(/"/g,'""')}"`; const rows=[['WBS','عنوان','نوع','مدت','شروع شمسی','پایان شمسی','پیش‌نیاز','سهم درصد'],...tasks.map(t=>[t.wbs_code,t.title,t.task_type,t.duration,J.display(t.start_date),J.display(t.end_date),predecessorText(t.id),t.weight_percent])]; const blob=new Blob(['\ufeff'+rows.map(r=>r.map(quote).join(',')).join('\r\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a');link.href=URL.createObjectURL(blob);link.download=`vetra-gantt-${projectId}.csv`;link.click();setTimeout(()=>URL.revokeObjectURL(link.href),1000); });
    q('.vg-zoom').addEventListener('change', render);
    root.querySelectorAll('[data-view]').forEach(btn => btn.addEventListener('click', () => { view=btn.dataset.view; root.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('vg-active',b===btn)); q('.vg-gantt').hidden=view!=='gantt'; q('.vg-calendar').hidden=view!=='calendar'; render(); }));
    grid.addEventListener('scroll', () => { chart.scrollTop=grid.scrollTop; });
    chart.addEventListener('scroll', () => { grid.scrollTop=chart.scrollTop; head.scrollLeft=chart.scrollLeft; });
    const divider=q('.vg-divider'); divider.addEventListener('pointerdown', e => { divider.setPointerCapture(e.pointerId); });
    divider.addEventListener('pointermove', e => { if (divider.hasPointerCapture(e.pointerId)) root.style.setProperty('--vg-grid-width',Math.max(320,Math.min(root.clientWidth-220,root.getBoundingClientRect().right-e.clientX))+'px'); });
    divider.addEventListener('keydown', e => { if (['ArrowLeft','ArrowRight'].includes(e.key)) { e.preventDefault(); const current=parseInt(getComputedStyle(root).getPropertyValue('--vg-grid-width'))||620; root.style.setProperty('--vg-grid-width',Math.max(320,Math.min(root.clientWidth-220,current+(e.key==='ArrowLeft'?20:-20)))+'px'); } });
    load();
  });
})();
