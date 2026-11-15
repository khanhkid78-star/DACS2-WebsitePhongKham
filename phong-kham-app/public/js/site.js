var navbar = document.querySelector(".navbar");
var toggle = document.querySelector(".nav-toggle");

if (toggle && navbar) {
    toggle.addEventListener("click", function () {
        var open = navbar.classList.toggle("nav-open");
        toggle.textContent = open ? "Đóng" : "Menu";
    });
}

window.addEventListener("scroll", function () {
    if (!navbar) {
        return;
    }
    navbar.classList.toggle("is-stuck", window.scrollY > 8);
});

var nodes = document.querySelectorAll(
    ".hero, .specialty, .doctor, .step, .card, .steps-band"
);

nodes.forEach(function (node, index) {
    node.classList.add("reveal");
    node.style.transitionDelay = (index % 4) * 70 + "ms";
});

var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
            return;
        }
        entry.target.classList.add("is-in");
        observer.unobserve(entry.target);
    });
}, { threshold: 0.12 });

nodes.forEach(function (node) {
    observer.observe(node);
});


function closeAuth() {
    var modal = document.getElementById("auth-modal");
    if (!modal) {
        return;
    }
    modal.classList.remove("is-open");
    document.body.classList.remove("modal-lock");
}

function showAuthPanel(which) {
    var login = document.getElementById("panel-login");
    var register = document.getElementById("panel-register");
    var showLogin = which !== "register";
    if (login) {
        login.classList.toggle("is-hidden", !showLogin);
    }
    if (register) {
        register.classList.toggle("is-hidden", showLogin);
    }
    document.querySelectorAll("[data-tab]").forEach(function (btn) {
        var on = btn.getAttribute("data-tab") === (showLogin ? "login" : "register");
        btn.classList.toggle("is-on", on);
    });
}

function openAuth(which) {
    var modal = document.getElementById("auth-modal");
    if (!modal) {
        return;
    }
    modal.classList.add("is-open");
    document.body.classList.add("modal-lock");
    showAuthPanel(which || "login");
}

document.querySelectorAll("[data-open]").forEach(function (link) {
    link.addEventListener("click", function (event) {
        if (!document.getElementById("auth-modal")) {
            return;
        }
        event.preventDefault();
        openAuth(link.getAttribute("data-open"));
    });
});

document.querySelectorAll("[data-tab]").forEach(function (btn) {
    btn.addEventListener("click", function () {
        showAuthPanel(btn.getAttribute("data-tab"));
    });
});

document.querySelectorAll("[data-close]").forEach(function (btn) {
    btn.addEventListener("click", closeAuth);
});

var authModal = document.getElementById("auth-modal");
if (authModal) {
    authModal.addEventListener("click", function (event) {
        if (event.target === authModal) {
            closeAuth();
        }
    });
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeAuth();
    }
});

var addLine = document.querySelector("[data-add-line]");
if (addLine) {
    addLine.addEventListener("click", function () {
        var row = document.querySelector("[data-line]");
        if (!row || !row.parentNode) {
            return;
        }
        var copy = row.cloneNode(true);
        copy.querySelectorAll("input, select, textarea").forEach(function (field) {
            if (field.tagName === "SELECT") {
                field.selectedIndex = 0;
            } else {
                field.value = "";
            }
        });
        row.parentNode.appendChild(copy);
    });
}

var appointmentSelect = document.querySelector("[data-appointment]");
if (appointmentSelect) {
    appointmentSelect.addEventListener("change", function () {
        var option = this.options[this.selectedIndex];
        var note = option ? option.getAttribute("data-note") || "" : "";
        var symptoms = document.querySelector("[name=symptoms]");
        if (symptoms && symptoms.value === "") {
            symptoms.value = note;
        }
    });
}

var rolePick = document.querySelector("[data-role]");
if (rolePick) {
    var syncRole = function () {
        document.querySelectorAll("[data-role-box]").forEach(function (box) {
            var show = box.getAttribute("data-role-box") === rolePick.value;
            box.classList.toggle("is-hidden", !show);
        });
    };
    rolePick.addEventListener("change", syncRole);
    syncRole();
}