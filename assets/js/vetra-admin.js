(() => {
  'use strict';
  const root = document.querySelector('.vg-admin');
  if (!root) return;
  const J = window.VetraJalali;
  const q = selector => root.querySelector(selector);
  const dialog = q('.vg-project-dialog'), form = q('.vg-project-form'), tbody = q('tbody');
  let projects = [], editing = null, initialState = '';
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  async function api(path, method = 'GET', body) {
    const response = await fetch(VG_ADMIN.restBase + path, {method, credentials:'same-origin', headers:{'X-WP-Nonce':VG_ADMIN.nonce,...(body?{'Content-Type':'application/json'}:{})}, body:body?JSON.stringify(body):undefined});
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw Error(data.message || 'ارتباط با سرور ناموفق بود.');
    return data;
  }
  function notify(text, error = false) {
    const box = q('.vg-admin-notice'); box.hidden = !text; box.className = `notice vg-admin-notice ${error?'notice-error':'notice-success'}`; q('.vg-admin-notice p').textContent = text;
  }
  function serialize() { return JSON.stringify(Object.fromEntries(new FormData(form))); }
  function render() {
    q('.vg-project-loading').hidden = true; q('.vg-project-empty').hidden = projects.length > 0; q('.vg-project-table-wrap').hidden = projects.length === 0;
    tbody.innerHTML = projects.map(p => { const shortcode = VG_ADMIN.shortcode.replace('%d', Number(p.id)); return `<tr data-id="${Number(p.id)}"><td data-label="عنوان"><strong>${escape(p.title)}</strong></td><td data-label="شروع" dir="ltr">${J.display(p.start_date)}</td><td data-label="فعالیت‌ها">${Number(p.task_count||0)}</td><td data-label="کد کوتاه"><code>${escape(shortcode)}</code></td><td data-label="عملیات"><div class="vg-row-actions"><button class="button" data-action="edit">ویرایش</button><button class="button" data-action="copy" data-code="${escape(shortcode)}">کپی کد</button><button class="button button-link-delete" data-action="delete">حذف</button></div></td></tr>`; }).join('');
  }
  async function load() { try { projects = await api('/projects'); render(); } catch (error) { q('.vg-project-loading').hidden=true; notify(error.message,true); } }
  function yearRange(startIso) { const y = Number(String(startIso).slice(0, 4)); return { from: y + '-01-01', to: (y + 2) + '-12-31' }; }
  async function fillOfficialHolidays(replace) {
    let startIso = null; try { startIso = J.parseOptional(form.elements.start_jalali.value); } catch (e) {}
    if (!startIso) startIso = J.today();
    const { from, to } = yearRange(startIso);
    const res = await api(`/holidays?from=${from}&to=${to}`);
    const lines = (res.holidays || []).map(h => J.display(h.date));
    const existing = replace ? [] : form.elements.holidays_jalali.value.split(/\r?\n/).map(x => x.trim()).filter(Boolean);
    form.elements.holidays_jalali.value = [...new Set([...existing, ...lines])].join('\n');
  }
  async function open(project = null) {
    editing = project; form.reset(); q('#vg-project-dialog-title').textContent = project ? 'ویرایش پروژه' : 'افزودن پروژه';
    const days = project ? String(project.workdays).split(',') : ['0','1','2','3','4','6'];
    form.elements.title.value = project?.title || '';
    form.querySelectorAll('[name=workdays]').forEach(box => { box.checked = days.includes(box.value); });
    if (project) {
      form.elements.start_jalali.value = J.display(project.start_date);
      form.elements.holidays_jalali.value = (project.holidays_list||[]).map(J.display).join('\n');
    } else {
      form.elements.start_jalali.value = J.display(J.today());
      try { await fillOfficialHolidays(true); } catch (e) {}
    }
    q('.vg-form-error').textContent=''; initialState=serialize(); dialog.showModal();
  }
  function close() { if (serialize() !== initialState && !window.confirm('تغییرات ذخیره‌نشده کنار گذاشته شود؟')) return; dialog.close(); }
  form.addEventListener('submit', async event => {
    event.preventDefault(); const submit=form.querySelector('[type=submit]'); q('.vg-form-error').textContent='';
    try {
      const workdays=[...form.querySelectorAll('[name=workdays]:checked')].map(x=>Number(x.value)); if(!workdays.length) throw Error('حداقل یک روز کاری انتخاب کنید.');
      const holidays=[...new Set(form.elements.holidays_jalali.value.split(/\r?\n|,/).map(x=>x.trim()).filter(Boolean).map(J.parse))];
      const body={title:form.elements.title.value.trim(),start_date:J.parse(form.elements.start_jalali.value),workdays,holidays};
      submit.disabled=true; await api(editing?`/projects/${editing.id}`:'/projects',editing?'PUT':'POST',body); initialState=serialize(); dialog.close(); notify('پروژه ذخیره شد.'); await load();
    } catch(error){q('.vg-form-error').textContent=error.message;} finally{submit.disabled=false;}
  });
  q('.vg-project-add').addEventListener('click',()=>open()); q('.vg-project-cancel').addEventListener('click',close);
  q('.vg-holidays-load').addEventListener('click', async () => {
    const btn = q('.vg-holidays-load');
    try { btn.disabled = true; await fillOfficialHolidays(false); initialState = serialize(); notify('تعطیلات رسمی بارگذاری شد.'); }
    catch (e) { notify(e.message, true); }
    finally { btn.disabled = false; }
  });
  form.elements.start_jalali.addEventListener('change', async () => {
    if (editing) return;
    try { await fillOfficialHolidays(true); initialState = serialize(); } catch (e) {}
  });
  if (window.VetraDatePicker) window.VetraDatePicker.init(root);
  dialog.addEventListener('cancel',event=>{event.preventDefault();close();});
  tbody.addEventListener('click',async event=>{const button=event.target.closest('[data-action]');if(!button)return;const row=button.closest('tr'),project=projects.find(p=>Number(p.id)===Number(row.dataset.id));
    if(button.dataset.action==='edit')open(project);
    if(button.dataset.action==='copy'){try{await navigator.clipboard.writeText(button.dataset.code);notify('کد کوتاه کپی شد.');}catch(e){notify('کپی خودکار ممکن نشد؛ کد را دستی کپی کنید.',true);}}
    if(button.dataset.action==='delete'&&window.confirm(`پروژه «${project.title}» و ${Number(project.task_count||0)} فعالیت آن حذف شود؟`)){try{button.disabled=true;await api(`/projects/${project.id}`,'DELETE');notify('پروژه حذف شد.');await load();}catch(e){notify(e.message,true);}finally{button.disabled=false;}}
  });
  load();
})();
