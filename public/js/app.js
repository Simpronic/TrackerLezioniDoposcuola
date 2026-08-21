document.addEventListener('DOMContentLoaded', () => {
  // Menu compatto per schermi piccoli.
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.nav-links');
  toggle?.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
  });

  // Conferma preventiva per i form che eseguono azioni distruttive.
  document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));

  // Comunica che il registro è in preparazione; il timeout riabilita il pulsante
  // perché un download non provoca una nuova navigazione della pagina corrente.
  document.querySelectorAll('.export-form').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button');
    if (!button) return;
    const originalLabel = button.textContent;
    button.disabled = true;
    button.textContent = 'Preparazione…';
    window.setTimeout(() => {
      button.disabled = false;
      button.textContent = originalLabel;
    }, 15000);
  }));

  // Propone la tariffa dello studente solo quando la tariffa della lezione è vuota.
  const student = document.querySelector('#studentSelect');
  const rate = document.querySelector('#lessonRate');
  student?.addEventListener('change', () => {
    if (!rate.value) rate.value = student.selectedOptions[0]?.dataset.rate || '';
  });

  // Attenua visivamente i campi fattura finché la lezione non risulta fatturata.
  const invoiceToggle = document.querySelector('#invoicedToggle');
  const invoiceFields = document.querySelector('#invoiceFields');
  const syncInvoice = () => invoiceFields?.classList.toggle('is-muted', !invoiceToggle?.checked);
  invoiceToggle?.addEventListener('change', syncInvoice); syncInvoice();

  // L'invio a Calendar è consentito soltanto per una lezione programmata.
  const lessonStatus = document.querySelector('#lessonStatus');
  const calendarFields = document.querySelector('#calendarFields');
  const calendarToggle = document.querySelector('#calendarToggle');
  const syncCalendarAvailability = () => {
    const available = lessonStatus?.value === 'programmata';
    calendarFields?.classList.toggle('is-muted', !available);
    if (calendarToggle) calendarToggle.disabled = !available;
  };
  lessonStatus?.addEventListener('change', syncCalendarAvailability); syncCalendarAvailability();

  const canvas = document.querySelector('#revenueChart');
  if (canvas && window.dashboardSeries) drawChart(canvas, window.dashboardSeries);
});

function drawChart(canvas, series) {
  // Grafico Canvas senza dipendenze esterne: grigio=maturato, verde=incassato.
  if (!series.length) { canvas.hidden = true; document.querySelector('#chartEmpty').hidden = false; return; }
  const ratio = window.devicePixelRatio || 1, width = canvas.clientWidth, height = canvas.clientHeight;
  canvas.width = width * ratio; canvas.height = height * ratio;
  const ctx = canvas.getContext('2d'); ctx.scale(ratio, ratio);
  const pad = {l: 42, r: 14, t: 18, b: 40};
  const max = Math.max(...series.flatMap(x => [x.maturato, x.incassato]), 1);
  ctx.font = '12px system-ui'; ctx.strokeStyle = '#e6e3dc'; ctx.fillStyle = '#746f66'; ctx.lineWidth = 1;
  for (let i=0;i<=4;i++) { const y = pad.t + (height-pad.t-pad.b)*i/4; ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(width-pad.r,y);ctx.stroke();ctx.fillText('€'+Math.round(max*(4-i)/4), 0, y+4); }
  const group = (width-pad.l-pad.r)/series.length, bar = Math.min(22, group*.28);
  series.forEach((item,i) => { const x=pad.l+group*i+group/2; const h1=(height-pad.t-pad.b)*item.maturato/max; const h2=(height-pad.t-pad.b)*item.incassato/max; ctx.fillStyle='#d9d3c7';ctx.fillRect(x-bar,height-pad.b-h1,bar,h1);ctx.fillStyle='#20634f';ctx.fillRect(x,height-pad.b-h2,bar,h2);ctx.fillStyle='#746f66';ctx.textAlign='center';ctx.fillText(item.label,x,height-pad.b+22); });
}
