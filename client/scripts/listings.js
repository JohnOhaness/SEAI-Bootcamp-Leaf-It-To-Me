function setMessage(element, message, kind) { element.textContent = message; element.className = kind ? "form-message form-" + kind : "form-message"; }
function formatDate(value) { return new Date(value + "T00:00:00").toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" }); }
function escapeHtml(value) { const element = document.createElement("div"); element.textContent = value || ""; return element.innerHTML; }

function setupCreateListing() {
  const form = document.getElementById("create-listing-form"); if (!form) return;
  const message = document.getElementById("create-message"), start = document.getElementById("start_date"), end = document.getElementById("end_date"), today = new Date().toISOString().slice(0, 10);
  start.min = today; end.min = today; start.addEventListener("change", function () { end.min = start.value || today; });
  form.addEventListener("submit", function (event) {
    event.preventDefault(); if (!isLoggedIn()) { location.href = "login.html"; return; }
    if (end.value < start.value) { setMessage(message, "The end date must be on or after the start date.", "error"); return; }
    const button = form.querySelector("button[type=submit]"); button.disabled = true; setMessage(message, "Creating its passport…");
    postForm("../../server/create-plant.php", { name: form.elements.name.value, species: form.elements.species.value, care_notes: form.elements.care_notes.value })
      .then(function (plantResponse) { if (!plantResponse.success) throw new Error(plantResponse.error || "Could not create plant"); return postForm("../../server/create-sit.php", { plant_id: plantResponse.data.plant_id, start_date: start.value, end_date: end.value }); })
      .then(function (sitResponse) { if (!sitResponse.success) throw new Error(sitResponse.error || "Could not create listing"); setMessage(message, "Passport created and sit listed. It is ready to be claimed!", "success"); form.reset(); start.min = today; end.min = today; })
      .catch(function (error) { setMessage(message, error.message || "Could not reach the server.", "error"); })
      .finally(function () { button.disabled = false; });
  });
}

function claimSit(event) {
  const button = event.currentTarget, message = document.getElementById("listing-message"); if (!isLoggedIn()) { location.href = "login.html"; return; } button.disabled = true;
  postForm("../../server/claim-sit.php", { sit_id: button.dataset.sitId }).then(function (response) { if (!response.success) throw new Error(response.error || "Could not claim this sit"); button.closest("article").remove(); message.textContent = "You claimed it — thank you for being a good plant neighbour."; message.className = "status-message form-success"; if (!document.querySelector(".listing-card")) document.getElementById("listing-grid").innerHTML = '<div class="empty-state">That was the last open sit for now.</div>'; }).catch(function (error) { button.disabled = false; message.textContent = error.message; message.className = "status-message form-error"; });
}

function setupFindListings() {
  const grid = document.getElementById("listing-grid"); if (!grid) return; const message = document.getElementById("listing-message");
  const regionSelect = document.getElementById("filter-region");
  const proximitySelect = document.getElementById("filter-proximity");
  function loadListings() {
  const params = new URLSearchParams({ region: regionSelect.value, proximity: proximitySelect.value });
  fetch("../../server/find-listing.php?" + params.toString()).then(function (response) { return response.json(); }).then(function (response) {
    if (!response.success) throw new Error(response.error || "Could not load listings"); if (!response.data.length) { grid.innerHTML = '<div class="empty-state">No plants need a sitter right now. Check back soon.</div>'; return; }
    grid.innerHTML = response.data.map(function (listing) { return '<article class="card listing-card"><p class="listing-meta">' + formatDate(listing.start_date) + ' — ' + formatDate(listing.end_date) + '</p><h2><a href="passport.html?plant_id=' + listing.plant_id + '">' + escapeHtml(listing.plant_name) + '</a></h2><p><strong>' + escapeHtml(listing.species || "Unknown species") + '</strong> · listed by ' + escapeHtml(listing.owner_username) + '</p><p class="listing-meta">' + escapeHtml(listing.owner_region) + '</p><p>' + escapeHtml(listing.care_notes || "No care notes added yet.") + '</p><a class="listing-meta" href="passport.html?plant_id=' + listing.plant_id + '">View passport →</a><button class="btn btn-primary claim-button" data-sit-id="' + listing.sit_id + '">Claim this sit</button></article>'; }).join("");
    grid.querySelectorAll(".claim-button").forEach(function (button) { button.addEventListener("click", claimSit); });
  }).catch(function (error) { grid.innerHTML = '<div class="empty-state">Could not load listings.</div>'; message.textContent = error.message; message.className = "status-message form-error"; });
  }
  document.getElementById("apply-filter").addEventListener("click", loadListings);
  loadListings();
}
setupCreateListing(); setupFindListings();
