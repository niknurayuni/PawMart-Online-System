document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-live-search]').forEach((form) => {
    const target = document.getElementById(form.dataset.liveTarget || '');
    if (!target) return;
    const search = form.querySelector('input[name="q"]');
    const category = form.querySelector('select[name="category"]');
    const count = document.getElementById(form.dataset.liveCount || '');
    const empty = document.getElementById(form.dataset.liveEmpty || '');
    const rows = [...target.querySelectorAll('[data-filter-item]')];

    const filter = () => {
      const query = (search?.value || '').trim().toLocaleLowerCase();
      const categoryId = category?.value || '0';
      let visible = 0;
      rows.forEach((row) => {
        const text = (row.dataset.search || '').toLocaleLowerCase();
        const matchesText = !query || text.includes(query);
        const matchesCategory = categoryId === '0' || row.dataset.category === categoryId;
        const show = matchesText && matchesCategory;
        row.hidden = !show;
        if (show) visible += 1;
      });
      if (count) count.textContent = `${visible} item${visible === 1 ? '' : 's'}`;
      if (empty) {
        empty.hidden = visible !== 0;
        if (target.id === 'admin-products-table') {
          empty.textContent = query ? 'No products matched your search.' : 'No products yet.';
        }
      }
    };

    search?.addEventListener('input', filter);
    category?.addEventListener('change', filter);
    form.addEventListener('submit', (event) => { event.preventDefault(); filter(); });
    filter();
  });
});
