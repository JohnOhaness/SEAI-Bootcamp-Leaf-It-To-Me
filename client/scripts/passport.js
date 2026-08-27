function passportEscape(value) {
  const element = document.createElement("div");
  element.textContent = value || "";
  return element.innerHTML;
}
function passportDate(value) {
  return new Date(value + "T00:00:00").toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}
const passportId = new URLSearchParams(location.search).get("plant_id");
const passportContent = document.getElementById("passport-content"),
  passportMessage = document.getElementById("passport-message");
if (!/^\d+$/.test(passportId || "")) {
  passportContent.innerHTML =
    '<div class="empty-state">Choose a plant from the <a href="find-listing.html">open listings</a> to view its passport.</div>';
} else {
  fetch("../../server/passport.php?plant_id=" + encodeURIComponent(passportId))
    .then(function (response) {
      return response.json();
    })
    .then(function (response) {
      if (!response.success)
        throw new Error(response.error || "Could not load passport");
      const plant = response.data,
        timeline = plant.timeline || [];
      const entries = timeline.length
        ? timeline
            .map(function (sit) {
              return (
                '<article class="timeline-entry"><p class="listing-meta">' +
                passportDate(sit.start_date) +
                " — " +
                passportDate(sit.end_date) +
                "</p><h3>" +
                passportEscape(sit.status) +
                "</h3><p>" +
                (sit.sitter_username
                  ? "Sitter: " + passportEscape(sit.sitter_username)
                  : "Waiting for a sitter") +
                "</p>" +
                (sit.sitter_note
                  ? '<p class="sitter-note">“' +
                    passportEscape(sit.sitter_note) +
                    "”</p>"
                  : "") +
                "</article>"
              );
            })
            .join("")
        : '<div class="empty-state">No sits have been recorded for this plant yet.</div>';
      passportContent.innerHTML =
        '<section class="passport-record"><p class="eyebrow">PLANT PASSPORT · NO. ' +
        String(plant.id).padStart(6, "0") +
        "</p><h1>" +
        passportEscape(plant.name) +
        '</h1><p class="passport-species">' +
        passportEscape(plant.species) +
        '</p><div class="passport-line"></div><p class="listing-meta">REGISTERED BY ' +
        passportEscape(plant.owner_username) +
        "</p><h2>Care Instructions</h2><p>" +
        passportEscape(
          plant.care_notes || "No care notes have been added yet.",
        ) +
        '</p><span class="passport-stamp">OFFICIAL<br />PASSPORT</span></section><section class="timeline"><p class="eyebrow">CARE HISTORY</p><h2>Stamps in the timeline</h2>' +
        entries +
        "</section>";
    })
    .catch(function (error) {
      passportMessage.textContent = error.message;
      passportMessage.className = "status-message form-error";
      passportContent.innerHTML =
        '<div class="empty-state">The passport could not be opened.</div>';
    });
}
