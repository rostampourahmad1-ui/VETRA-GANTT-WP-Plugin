/* Shared predecessor parser used by the inline grid; DOM-free so it can be unit tested. */
window.VetraGanttUtil = (() => {
  function parsePredecessors(value, tasks, selfId) {
    if (!String(value ?? '').trim()) return [];
    return String(value).split(',').map(part => {
      const m = /^\s*([\w.]+?)\s*(FS|SS|FF|SF)?\s*([+-]\d+)?\s*$/.exec(part.trim().toUpperCase());
      if (!m) throw Error('فرمت پیش‌نیاز نامعتبر است.');
      const t = tasks.find(x => x.wbs_code === m[1]) || tasks.find(x => String(x.id) === m[1]);
      if (!t || t.id === selfId) throw Error('پیش‌نیاز در این پروژه یافت نشد.');
      return { depends_on: Number(t.id), dep_type: m[2] || 'FS', lag_days: Number(m[3] || 0) };
    });
  }
  return { parsePredecessors };
})();
