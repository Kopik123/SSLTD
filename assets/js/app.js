(function () {
  "use strict";

  // Minimal progressive enhancement, keep the app server-rendered.
  document.addEventListener("change", function (e) {
    var t = e.target;
    if (!t || !t.matches) return;
    if (!t.matches("input[type='file'][data-filelist]")) return;

    var id = t.getAttribute("data-filelist");
    if (!id) return;
    var el = document.getElementById(id);
    if (!el) return;

    var files = t.files ? Array.prototype.slice.call(t.files) : [];
    el.textContent = files.length ? (files.length + " file(s) selected") : "No files selected";
  });

  function initLogOverlay() {
    var body = document.body;
    if (!body) return;
    if (body.getAttribute("data-log-overlay") !== "1") return;

    var endpoint = body.getAttribute("data-log-endpoint") || "";
    if (!endpoint) return;

    var devCsrf = body.getAttribute("data-dev-csrf") || "";
    var basePath = body.getAttribute("data-base-path") || "";
    function url(p) { return (basePath || "") + p; }

    var stateKey = "ss_dev_log_overlay_v1";
    var st = { x: null, y: null, hidden: false, paused: false, offset: 0 };
    try {
      var raw = localStorage.getItem(stateKey);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (parsed && typeof parsed === "object") {
          st.x = typeof parsed.x === "number" ? parsed.x : null;
          st.y = typeof parsed.y === "number" ? parsed.y : null;
          st.hidden = !!parsed.hidden;
          st.paused = !!parsed.paused;
          st.offset = typeof parsed.offset === "number" ? parsed.offset : 0;
        }
      }
    } catch (_) {}

    function persist() {
      try { localStorage.setItem(stateKey, JSON.stringify(st)); } catch (_) {}
    }

    var tools = {
      tab: "logs",
      users: null,
      whoami: null,
      busy: false,
      msg: "",
    };

    var root = document.createElement("div");
    root.className = "log-overlay";
    if (st.hidden) root.classList.add("hidden");
    root.setAttribute("role", "dialog");
    root.setAttribute("aria-label", "Server logs overlay");

    var bar = document.createElement("div");
    bar.className = "log-overlay__bar";
    var title = document.createElement("div");
    title.className = "log-overlay__title";
    title.textContent = "Server logs (dev)";

    var btns = document.createElement("div");
    btns.className = "log-overlay__btns";

    function mkBtn(label) {
      var b = document.createElement("button");
      b.type = "button";
      b.className = "log-overlay__btn";
      b.textContent = label;
      return b;
    }

    var btnPause = mkBtn(st.paused ? "Resume" : "Pause");
    var btnHide = mkBtn(st.hidden ? "Show" : "Hide");
    var btnClear = mkBtn("Clear");
    var btnCopy = mkBtn("Copy");

    btns.appendChild(btnPause);
    btns.appendChild(btnHide);
    btns.appendChild(btnClear);
    btns.appendChild(btnCopy);

    bar.appendChild(title);
    bar.appendChild(btns);

    var tabs = document.createElement("div");
    tabs.className = "log-overlay__tabs";
    var tabLogs = document.createElement("button");
    tabLogs.type = "button";
    tabLogs.className = "log-overlay__tab is-active";
    tabLogs.textContent = "Logs";
    var tabTools = document.createElement("button");
    tabTools.type = "button";
    tabTools.className = "log-overlay__tab";
    tabTools.textContent = "Tools";
    tabs.appendChild(tabLogs);
    tabs.appendChild(tabTools);

    var bodyEl = document.createElement("div");
    bodyEl.className = "log-overlay__body";
    var meta = document.createElement("div");
    meta.className = "log-overlay__meta";
    meta.textContent = "Polling...";
    bodyEl.appendChild(meta);

    root.appendChild(bar);
    root.appendChild(tabs);
    root.appendChild(bodyEl);
    document.body.appendChild(root);

    function setTab(name) {
      tools.tab = name;
      if (name === "logs") {
        tabLogs.classList.add("is-active");
        tabTools.classList.remove("is-active");
        bodyEl.classList.remove("is-tools");
        meta.textContent = st.paused ? "Paused." : "Polling...";
      } else {
        tabTools.classList.add("is-active");
        tabLogs.classList.remove("is-active");
        bodyEl.classList.add("is-tools");
        renderTools();
      }
    }

    function setPos(x, y) {
      root.style.left = x + "px";
      root.style.top = y + "px";
      root.style.bottom = "auto";
      st.x = x;
      st.y = y;
      persist();
    }

    if (typeof st.x === "number" && typeof st.y === "number") {
      setPos(st.x, st.y);
    }

    function scrollToBottomIfNear() {
      // Keep following if user is near bottom.
      var near = (bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight) < 80;
      if (near) bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    function appendLines(lines, truncated) {
      if (!lines || !lines.length) return;
      var frag = document.createDocumentFragment();
      for (var i = 0; i < lines.length; i++) {
        var line = String(lines[i]);
        if (!line) continue;
        var div = document.createElement("div");
        div.className = "log-overlay__line";
        if (line.indexOf(" ERROR ") !== -1 || line.indexOf("ERROR") !== -1) {
          div.className += " log-overlay__line--error";
        }
        div.textContent = line;
        frag.appendChild(div);
      }
      // Insert before meta footer
      bodyEl.insertBefore(frag, meta);
      if (truncated) meta.textContent = "Polling... (truncated)";
      scrollToBottomIfNear();
    }

    function clearLines() {
      var nodes = bodyEl.querySelectorAll(".log-overlay__line");
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].remove();
      }
      meta.textContent = "Cleared.";
    }

    function clearToolsUi() {
      var nodes = bodyEl.querySelectorAll(".log-overlay__tools");
      for (var i = 0; i < nodes.length; i++) nodes[i].remove();
    }

    function postDev(url, dataObj) {
      var fd = [];
      var k;
      if (devCsrf) dataObj._csrf = devCsrf;
      for (k in dataObj) {
        if (!Object.prototype.hasOwnProperty.call(dataObj, k)) continue;
        fd.push(encodeURIComponent(k) + "=" + encodeURIComponent(String(dataObj[k])));
      }
      return fetch(url, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: fd.join("&"),
        cache: "no-store",
      }).then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      });
    }

    function fetchJson(url) {
      return fetch(url, { credentials: "same-origin", cache: "no-store" })
        .then(function (r) {
          if (!r.ok) throw new Error("HTTP " + r.status);
          return r.json();
        });
    }

    function renderTools() {
      clearToolsUi();

      var wrap = document.createElement("div");
      wrap.className = "log-overlay__tools";

      var row = document.createElement("div");
      row.className = "log-overlay__tools-row";

      function mkAction(label, onClick) {
        var b = document.createElement("button");
        b.type = "button";
        b.className = "log-overlay__btn";
        b.textContent = label;
        b.addEventListener("click", onClick);
        return b;
      }

      var btnRefresh = mkAction("Refresh", function () {
        tools.msg = "Refreshing...";
        renderTools();
        Promise.all([
          fetchJson(url("/app/dev/tools/whoami")),
          fetchJson(url("/app/dev/tools/users")),
        ]).then(function (vals) {
          tools.whoami = vals[0] && vals[0].user ? vals[0].user : null;
          tools.users = vals[1] && vals[1].items ? vals[1].items : [];
          tools.msg = "";
          renderTools();
        }).catch(function (err) {
          tools.msg = "Error: " + String(err && err.message ? err.message : err);
          renderTools();
        });
      });

      var btnLogoutFast = mkAction("Logout", function () {
        tools.msg = "Logging out...";
        renderTools();
        postDev(url("/app/dev/tools/logout"), {}).then(function () {
          tools.msg = "Logged out.";
          renderTools();
          setTimeout(function () { window.location.href = url("/login"); }, 200);
        }).catch(function (err) {
          tools.msg = "Logout failed: " + String(err && err.message ? err.message : err);
          renderTools();
        });
      });

      var btnClearRl = mkAction("Clear rate limits", function () {
        tools.msg = "Clearing rate limits...";
        renderTools();
        postDev(url("/app/dev/tools/ratelimit/clear"), {}).then(function (j) {
          tools.msg = "Rate limits cleared (deleted=" + String(j && j.deleted != null ? j.deleted : "?") + ").";
          renderTools();
        }).catch(function (err) {
          tools.msg = "Clear failed: " + String(err && err.message ? err.message : err);
          renderTools();
        });
      });

      row.appendChild(btnRefresh);
      row.appendChild(btnLogoutFast);
      row.appendChild(btnClearRl);
      wrap.appendChild(row);

      var who = document.createElement("div");
      who.className = "log-overlay__tools-block";
      var whoText = tools.whoami
        ? ("Session: " + tools.whoami.email + " (" + tools.whoami.role + ")")
        : "Session: (not logged in)";
      who.textContent = whoText;
      wrap.appendChild(who);

      if (tools.msg) {
        var msg = document.createElement("div");
        msg.className = "log-overlay__tools-msg";
        msg.textContent = tools.msg;
        wrap.appendChild(msg);
      }

      var links = document.createElement("div");
      links.className = "log-overlay__tools-block";
      links.innerHTML = ""
        + "<div class=\"log-overlay__tools-title\">Quick links</div>"
        + "<div class=\"log-overlay__tools-links\">"
        + "<a href=\"" + url("/") + "\" target=\"_blank\" rel=\"noopener\">Home</a>"
        + "<a href=\"" + url("/quote-request") + "\" target=\"_blank\" rel=\"noopener\">Quote</a>"
        + "<a href=\"" + url("/login") + "\" target=\"_blank\" rel=\"noopener\">Login</a>"
        + "<a href=\"" + url("/app") + "\" target=\"_blank\" rel=\"noopener\">Portal</a>"
        + "<a href=\"" + url("/app/leads") + "\" target=\"_blank\" rel=\"noopener\">Leads</a>"
        + "<a href=\"" + url("/app/projects") + "\" target=\"_blank\" rel=\"noopener\">Projects</a>"
        + "<a href=\"" + url("/app/messages") + "\" target=\"_blank\" rel=\"noopener\">Messages</a>"
        + "<a href=\"" + url("/app/admin/users") + "\" target=\"_blank\" rel=\"noopener\">Admin</a>"
        + "<a href=\"" + url("/health") + "\" target=\"_blank\" rel=\"noopener\">/health</a>"
        + "<a href=\"" + url("/health/db") + "\" target=\"_blank\" rel=\"noopener\">/health/db</a>"
        + "</div>";
      wrap.appendChild(links);

      var users = document.createElement("div");
      users.className = "log-overlay__tools-block";
      var titleUsers = document.createElement("div");
      titleUsers.className = "log-overlay__tools-title";
      titleUsers.textContent = "Login as (dev)";
      users.appendChild(titleUsers);

      var list = document.createElement("div");
      list.className = "log-overlay__tools-users";

      var items = tools.users;
      if (!items) {
        var hint = document.createElement("div");
        hint.className = "log-overlay__tools-hint";
        hint.textContent = "Click Refresh to load users.";
        users.appendChild(hint);
      } else if (!items.length) {
        var none = document.createElement("div");
        none.className = "log-overlay__tools-hint";
        none.textContent = "No users found.";
        users.appendChild(none);
      } else {
        for (var i = 0; i < items.length; i++) {
          (function (u) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "log-overlay__user";
            btn.textContent = (u.role || "?") + ": " + (u.email || ("#" + String(u.id)));
            btn.addEventListener("click", function () {
              tools.msg = "Switching session...";
              renderTools();
              postDev(url("/app/dev/tools/login-as"), { user_id: u.id }).then(function () {
                tools.msg = "Switched to " + (u.email || ("#" + String(u.id))) + ".";
                renderTools();
                setTimeout(function () { window.location.reload(); }, 150);
              }).catch(function (err) {
                tools.msg = "Login-as failed: " + String(err && err.message ? err.message : err);
                renderTools();
              });
            });
            list.appendChild(btn);
          })(items[i]);
        }
        users.appendChild(list);
      }
      wrap.appendChild(users);

      var autofill = document.createElement("div");
      autofill.className = "log-overlay__tools-block";
      autofill.innerHTML = ""
        + "<div class=\"log-overlay__tools-title\">Autofill helpers</div>"
        + "<div class=\"log-overlay__tools-hint\">Open these links in a new tab to auto-fill forms during manual QA.</div>"
        + "<div class=\"log-overlay__tools-links\">"
        + "<a href=\"" + url("/login?autofill=admin") + "\" target=\"_blank\" rel=\"noopener\">Login autofill (admin)</a>"
        + "<a href=\"" + url("/quote-request?mode=simple&autofill=1") + "\" target=\"_blank\" rel=\"noopener\">Quote Simple autofill</a>"
        + "<a href=\"" + url("/quote-request?mode=advanced&autofill=1") + "\" target=\"_blank\" rel=\"noopener\">Quote Advanced autofill</a>"
        + "</div>";
      wrap.appendChild(autofill);

      bodyEl.insertBefore(wrap, meta);
      meta.textContent = "Tools loaded.";
    }

    btnPause.addEventListener("click", function () {
      st.paused = !st.paused;
      btnPause.textContent = st.paused ? "Resume" : "Pause";
      meta.textContent = st.paused ? "Paused." : "Polling...";
      persist();
    });

    btnHide.addEventListener("click", function () {
      st.hidden = !st.hidden;
      if (st.hidden) root.classList.add("hidden");
      else root.classList.remove("hidden");
      btnHide.textContent = st.hidden ? "Show" : "Hide";
      persist();
    });

    btnClear.addEventListener("click", function () {
      clearLines();
    });

    btnCopy.addEventListener("click", function () {
      var lines = bodyEl.querySelectorAll(".log-overlay__line");
      var out = [];
      for (var i = 0; i < lines.length; i++) out.push(lines[i].textContent || "");
      var txt = out.join("\n");
      if (!txt) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(txt).then(function () {
          meta.textContent = "Copied to clipboard.";
        }, function () {
          meta.textContent = "Copy failed.";
        });
      } else {
        meta.textContent = "Clipboard API not available.";
      }
    });

    // Drag
    var dragging = false;
    var dx = 0, dy = 0;
    bar.addEventListener("mousedown", function (e) {
      dragging = true;
      var rect = root.getBoundingClientRect();
      dx = e.clientX - rect.left;
      dy = e.clientY - rect.top;
      root.style.right = "auto";
      e.preventDefault();
    });
    document.addEventListener("mousemove", function (e) {
      if (!dragging) return;
      var x = Math.max(0, Math.min(window.innerWidth - 80, e.clientX - dx));
      var y = Math.max(0, Math.min(window.innerHeight - 60, e.clientY - dy));
      root.style.left = x + "px";
      root.style.top = y + "px";
      root.style.bottom = "auto";
      st.x = x;
      st.y = y;
    });
    document.addEventListener("mouseup", function () {
      if (!dragging) return;
      dragging = false;
      persist();
    });

    // Keyboard toggle (Ctrl+`)
    document.addEventListener("keydown", function (e) {
      if (!e.ctrlKey) return;
      if (e.key !== "`") return;
      st.hidden = !st.hidden;
      if (st.hidden) root.classList.add("hidden");
      else root.classList.remove("hidden");
      btnHide.textContent = st.hidden ? "Show" : "Hide";
      persist();
    });

    function poll() {
      if (tools.tab === "tools") {
        setTimeout(poll, 1000);
        return;
      }
      if (st.hidden || st.paused) {
        setTimeout(poll, 1000);
        return;
      }

      var url = endpoint + (endpoint.indexOf("?") === -1 ? "?" : "&") + "offset=" + encodeURIComponent(String(st.offset || 0));
      fetch(url, { credentials: "same-origin", cache: "no-store" })
        .then(function (r) {
          if (!r.ok) throw new Error("HTTP " + r.status);
          return r.json();
        })
        .then(function (j) {
          if (j && typeof j.offset === "number") {
            st.offset = j.offset;
            persist();
          }
          appendLines(j && j.lines ? j.lines : [], !!(j && j.truncated));
          if (j && j.missing) meta.textContent = "No log file yet.";
          else if (!st.paused) meta.textContent = "Polling...";
        })
        .catch(function (err) {
          meta.textContent = "Polling error: " + String(err && err.message ? err.message : err);
        })
        .finally(function () {
          setTimeout(poll, 1000);
        });
    }

    poll();

    tabLogs.addEventListener("click", function () { setTab("logs"); });
    tabTools.addEventListener("click", function () { setTab("tools"); });

    // Simple autofill for quote request forms in dev when URL contains autofill=1
    (function () {
      try {
        var qs = window.location.search || "";
        var path = window.location.pathname || "";
        if (path.indexOf("/login") !== -1) {
          // /login?autofill=admin|pm|client|employee|sub|subworker
          if (qs.indexOf("autofill=") === -1) return;
          var which = "";
          try {
            var sp = new URLSearchParams(qs);
            which = sp.get("autofill") || "";
          } catch (_) {}
          which = String(which || "").toLowerCase();
          if (!which) which = "admin";
          var map = {
            "admin": { email: "admin@ss.local", password: "Admin123!" },
            "pm": { email: "pm@ss.local", password: "Pm123456!" },
            "client": { email: "client@ss.local", password: "Client123!" },
            "employee": { email: "employee@ss.local", password: "Employee123!" },
            "sub": { email: "sub@ss.local", password: "Sub123456!" },
            "subworker": { email: "subworker@ss.local", password: "Worker123!" }
          };
          var t = map[which] || map.admin;
          var le = document.querySelector("input[name='email']");
          var lp = document.querySelector("input[name='password']");
          if (le && !le.value) le.value = t.email;
          if (lp && !lp.value) lp.value = t.password;
          return;
        }
        if (qs.indexOf("autofill=1") === -1) return;
        if (path.indexOf("/quote-request") === -1) return;
        var name = document.querySelector("input[name='name']");
        var email = document.querySelector("input[name='email']");
        var phone = document.querySelector("input[name='phone']");
        var desc = document.querySelector("textarea[name='description']");
        var consent = document.querySelector("input[name='consent_privacy']");
        if (name && !name.value) name.value = "QA Tester";
        if (email && !email.value) email.value = "qa+" + String(Date.now()) + "@ss.local";
        if (phone && !phone.value) phone.value = "(555) 010-999";
        if (desc && !desc.value) desc.value = "Test request created via autofill helper.";
        if (consent && !consent.checked) consent.checked = true;
        var address = document.querySelector("input[name='address']");
        if (address && !address.value) address.value = "Boston, MA";
        var scopes = document.querySelectorAll("input[name='scope[]']");
        if (scopes && scopes.length) {
          for (var i = 0; i < scopes.length && i < 2; i++) scopes[i].checked = true;
        }
      } catch (_) {}
    })();
  }

  document.addEventListener("DOMContentLoaded", initLogOverlay);
})();
