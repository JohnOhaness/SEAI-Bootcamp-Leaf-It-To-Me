function esc(value) { const element = document.createElement("div"); element.textContent = value || ""; return element.innerHTML; }
function date(value) { return new Date(value + "T00:00:00").toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" }); }
function empty(text) { return '<div class="empty-state">' + text + '</div>'; }
const message = document.getElementById("my-sits-message");
if (!isLoggedIn()) location.href = "login.html";
function loadSits() {
  fetch("../../server/my-sits.php", { headers: { Authorization: "Bearer " + getToken() } }).then(function (r) { return r.json(); }).then(function (response) {
    if (!response.success) throw new Error(response.error || "Could not load your sits");
    const owned = response.data.owned_sits, claimed = response.data.claimed_sits;
    document.getElementById("owned-sits").innerHTML = owned.length ? owned.map(function (sit) {
      const action = sit.status === "open" ? '<button class="btn btn-secondary cancel-sit" data-id="' + sit.sit_id + '">Cancel listing</button>' : '';
      return '<article class="card listing-card"><p class="listing-meta">' + date(sit.start_date) + ' — ' + date(sit.end_date) + '</p><h3><a href="passport.html?plant_id=' + sit.plant_id + '">' + esc(sit.plant_name) + '</a></h3><p><span class="sit-status">' + esc(sit.status) + '</span>' + (sit.sitter_username ? ' · Sitter: ' + esc(sit.sitter_username) : '') + '</p>' + action + '</article>';
    }).join("") : empty("You have not listed a plant yet.");
    document.getElementById("claimed-sits").innerHTML = claimed.length ? claimed.map(function (sit) {
      const action = sit.status === "claimed" ? '<label for="note-' + sit.sit_id + '">Final care note</label><textarea id="note-' + sit.sit_id + '" rows="3" placeholder="How did the visit go?"></textarea><button class="btn btn-primary complete-sit" data-id="' + sit.sit_id + '">Mark complete</button>' : '';
      return '<article class="card listing-card"><p class="listing-meta">' + date(sit.start_date) + ' — ' + date(sit.end_date) + '</p><h3><a href="passport.html?plant_id=' + sit.plant_id + '">' + esc(sit.plant_name) + '</a></h3><p><span class="sit-status">' + esc(sit.status) + '</span> · Owner: ' + esc(sit.owner_username) + '</p>' + (sit.sitter_note ? '<p>“' + esc(sit.sitter_note) + '”</p>' : '') + action + '</article>';
    }).join("") : empty("You have not claimed a sit yet.");
    document.querySelectorAll(".cancel-sit").forEach(function (button) { button.addEventListener("click", function () { sendAction("cancel-sit.php", button.dataset.id, ""); }); });
    document.querySelectorAll(".complete-sit").forEach(function (button) { button.addEventListener("click", function () { sendAction("complete-sit.php", button.dataset.id, document.getElementById("note-" + button.dataset.id).value); }); });
  }).catch(function (error) { message.textContent = error.message; message.className = "status-message form-error"; });
}
function sendAction(endpoint, sitId, sitterNote) {
  postForm("../../server/" + endpoint, { sit_id: sitId, sitter_note: sitterNote }).then(function (response) {
    if (!response.success) throw new Error(response.error || "Could not update sit"); message.textContent = response.data.message; message.className = "status-message form-success"; loadSits();
  }).catch(function (error) { message.textContent = error.message; message.className = "status-message form-error"; });
}
loadSits();
