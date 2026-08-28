const profileMessage = document.getElementById("profile-message");
if (!isLoggedIn()) location.href = "login.html";
function escapeHtml(value) {
  const element = document.createElement("div");
  element.textContent = value || "";
  return element.innerHTML;
}
function formatDate(value) {
  return new Date(value + "T00:00:00").toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}
function renderTimeline(entries) {
  const container = document.getElementById("profile-timeline");
  if (!entries.length) {
    container.innerHTML = '<div class="empty-state">No sits yet.</div>';
    return;
  }
  container.innerHTML = entries
    .map(function (entry) {
      const leafClass =
        entry.role === "owner" ? "timeline-leaf-owner" : "timeline-leaf-sitter";
      const counterpart =
        entry.role === "owner"
          ? entry.sitter_username
            ? "Sitter: " + escapeHtml(entry.sitter_username)
            : "Not yet claimed"
          : "Owner: " + escapeHtml(entry.owner_username);
      return (
        '<article class="timeline-leaf ' +
        leafClass +
        '"><p class="timeline-leaf-meta">' +
        formatDate(entry.start_date) +
        " — " +
        formatDate(entry.end_date) +
        "</p><h3>" +
        escapeHtml(entry.plant_name) +
        '</h3><p class="timeline-leaf-meta">' +
        escapeHtml(entry.status) +
        " · " +
        counterpart +
        "</p></article>"
      );
    })
    .join("");
}
function loadProfile() {
  fetch("../../server/profile.php", {
    headers: { Authorization: "Bearer " + getToken() },
  })
    .then(function (r) {
      return r.json();
    })
    .then(function (response) {
      if (!response.success)
        throw new Error(response.error || "Could not load profile");
      document.getElementById("profile-username").value =
        response.data.username;
      document.getElementById("profile-email").value = response.data.email;
      document.getElementById("profile-region").value = response.data.region;
      renderTimeline(response.data.timeline || []);
    })
    .catch(function (error) {
      profileMessage.textContent = error.message;
      profileMessage.className = "form-message form-error";
    });
}
document
  .getElementById("profile-form")
  .addEventListener("submit", function (event) {
    event.preventDefault();
    postForm("../../server/profile.php", {
      region: document.getElementById("profile-region").value,
    })
      .then(function (response) {
        if (!response.success)
          throw new Error(response.error || "Could not update profile");
        profileMessage.textContent = "District saved.";
        profileMessage.className = "form-message form-success";
      })
      .catch(function (error) {
        profileMessage.textContent = error.message;
        profileMessage.className = "form-message form-error";
      });
  });
loadProfile();
