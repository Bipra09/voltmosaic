const form = document.getElementById('payment');
const status = document.getElementById('status');
const endpoint = new URL('api.php', location.href);
let pending;
async function loadHistory() {
  const res = await fetch(endpoint);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error);
  document.getElementById('history').replaceChildren(...data.payments.map(row => {
    const li = document.createElement('li');
    li.textContent = row.service + ' · ¥' + Number(row.amount).toLocaleString() + ' · ' + row.created_at + ' · ' + row.id;
    return li;
  }));
}
form.onsubmit = async event => {
  event.preventDefault();
  const button = form.querySelector('button');
  button.disabled = true;
  try {
    const fields = Object.fromEntries(new FormData(form));
    fields.amount = Number(fields.amount);
    const signature = JSON.stringify(fields);
    if (pending?.signature !== signature) pending = {signature, id:crypto.randomUUID()};
    const response = await fetch(endpoint, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({...fields, request_id:pending.id})});
    const data = await response.json();
    if (!response.ok) throw new Error(data.error);
    pending = undefined;
    form.reset();
    status.textContent = 'Simulated transaction complete · ' + data.id;
    try { await loadHistory(); }
    catch { status.textContent += ' · History refresh failed.'; }
  } catch (error) { status.textContent = error.message; }
  finally { button.disabled = false; }
};
loadHistory().catch(error => { status.textContent = error.message; });
