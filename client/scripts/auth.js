// client/scripts/auth.js — shared session helpers + auth nav state
// Uses localStorage + fetch (no CDN, no Axios required).

const LEAF_TOKEN_KEY = "leaf_token";
const LEAF_USERNAME_KEY = "leaf_username";

// ---------- session helpers ----------

function setSession(token, username) {
  localStorage.setItem(LEAF_TOKEN_KEY, token);
  localStorage.setItem(LEAF_USERNAME_KEY, username);
}

function getToken() {
  return localStorage.getItem(LEAF_TOKEN_KEY);
}

function getUsername() {
  return localStorage.getItem(LEAF_USERNAME_KEY);
}

function clearSession() {
  localStorage.removeItem(LEAF_TOKEN_KEY);
  localStorage.removeItem(LEAF_USERNAME_KEY);
}

function isLoggedIn() {
  return getToken() !== null;
}

// POST an object as application/x-www-form-urlencoded (matches $_POST on the PHP side)
function postForm(endpoint, data) {
  return fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data).toString(),
  }).then(function (res) {
    return res.json();
  });
}

// ---------- auth-aware nav ----------

function updateNav() {
  const links = document.querySelectorAll(".nav-links a");
  let loginLi = null;
  let signupLi = null;

  links.forEach(function (a) {
    const href = a.getAttribute("href") || "";
    if (href.indexOf("login.html") !== -1) loginLi = a.parentElement;
    if (href.indexOf("signup.html") !== -1) signupLi = a.parentElement;
  });

  if (!loginLi || !signupLi) return;

  if (isLoggedIn()) {
    const username = getUsername();

    loginLi.innerHTML = "";
    const greeting = document.createElement("span");
    greeting.className = "nav-greeting";
    greeting.textContent = "Hi, " + username;

    signupLi.innerHTML = "";
    const logoutBtn = document.createElement("button");
    logoutBtn.className = "nav-logout";
    logoutBtn.type = "button";
    logoutBtn.textContent = "Logout";
    logoutBtn.addEventListener("click", function () {
      clearSession();
      // pages under client/pages/ are one level deeper than client/index.html
      const fromPages = location.pathname.indexOf("/pages/") !== -1;
      location.href = fromPages ? "../index.html" : "index.html";
    });

    loginLi.appendChild(greeting);
    signupLi.appendChild(logoutBtn);
  }
}

// run once the DOM is ready (works whether the script is in <head> or at the end of body)
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", updateNav);
} else {
  updateNav();
}
