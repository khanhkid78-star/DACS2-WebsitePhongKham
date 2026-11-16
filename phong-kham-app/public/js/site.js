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
            if (field.type === "checkbox") {
                field.checked = false;
            } else if (field.tagName === "SELECT") {
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

var removeLine = document.querySelector("[data-remove-line]");
if (removeLine) {
    removeLine.addEventListener("click", function () {
        var box = document.getElementById("rx-lines");
        if (!box) {
            return;
        }
        var rows = Array.prototype.slice.call(box.querySelectorAll("[data-line]"));
        var picked = rows.filter(function (row) {
            var tick = row.querySelector("[data-line-check]");
            return tick && tick.checked;
        });
        if (!picked.length) {
            alert("Chọn ít nhất một dòng thuốc.");
            return;
        }
        if (picked.length >= rows.length) {
            picked.forEach(function (row, index) {
                if (index === 0) {
                    row.querySelectorAll("input, select, textarea").forEach(function (field) {
                        if (field.type === "checkbox") {
                            field.checked = false;
                        } else if (field.tagName === "SELECT") {
                            field.selectedIndex = 0;
                        } else {
                            field.value = "";
                        }
                    });
                } else {
                    row.remove();
                }
            });
            return;
        }
        picked.forEach(function (row) {
            row.remove();
        });
    });
}

document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form || form.tagName !== "FORM") {
        return;
    }
    var field = form.querySelector("input[name=posted_user_id]");
    if (!field) {
        field = document.createElement("input");
        field.type = "hidden";
        field.name = "posted_user_id";
        form.appendChild(field);
    }
    field.value = document.body.getAttribute("data-user") || "0";
});

document.addEventListener("visibilitychange", function () {
    if (document.visibilityState !== "visible") {
        return;
    }
    var mine = document.body.getAttribute("data-user") || "0";
    fetch("/index.php?action=whoami", { credentials: "same-origin", cache: "no-store" })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (String(data.user_id) !== String(mine)) {
                window.location.reload();
            }
        })
        .catch(function () {});
});


function openBox(id) {
    var box = document.getElementById(id);
    if (!box) return;
    box.classList.add("is-open");
    document.body.classList.add("modal-lock");
}

function esc(value) {
    return String(value || "").replace(/[&<>"]/g, function (ch) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[ch];
    });
}

if (window.jQuery) {
    jQuery(document).on("click", "[data-close-box]", function () {
        jQuery(this).closest(".modal").removeClass("is-open");
        document.body.classList.remove("modal-lock");
    });

    jQuery(document).on("click", "[data-appt]", function () {
        var data = JSON.parse(jQuery(this).attr("data-appt"));
        jQuery("#appt-id").val(data.id);
        jQuery("#appt-label").text(data.id);
        jQuery("#appt-doctor").val(String(data.doctor_id));
        jQuery("#appt-date").val(data.date);
        jQuery("#appt-note").val(data.note);
        jQuery("#appt-status").val(data.status);
        openBox("appt-modal");
    });

    jQuery("#open-new-record").on("click", function () {
        jQuery("#record-title").text("Ghi bệnh án mới");
        jQuery("#record-form").attr("action", "/index.php?action=save_record");
        jQuery("#record-id").prop("disabled", true);
        jQuery("#record-create-fields, #record-rx").show();
        jQuery("#record-create-fields select").prop("disabled", false);
        jQuery("#record-symptoms, #record-diagnosis, #record-conclusion").val("");
        jQuery("#record-submit").text("Lưu bệnh án");
        openBox("record-modal");
    });

    jQuery(document).on("click", "[data-record]", function () {
        var data = JSON.parse(jQuery(this).attr("data-record"));
        jQuery("#record-title").text("Sửa bệnh án #" + data.id);
        jQuery("#record-form").attr("action", "/index.php?action=update_record");
        jQuery("#record-id").prop("disabled", false).val(data.id);
        jQuery("#record-create-fields, #record-rx").hide();
        jQuery("#record-create-fields select").prop("disabled", true);
        jQuery("#record-symptoms").val(data.symptoms);
        jQuery("#record-diagnosis").val(data.diagnosis);
        jQuery("#record-conclusion").val(data.conclusion);
        jQuery("#record-submit").text("Cập nhật");
        openBox("record-modal");
    });

    function bindList(formId, bodyId, url) {
        var form = document.getElementById(formId);
        if (!form) return;
        var timer;
        jQuery(form).on("input change", function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                jQuery.get(url, jQuery(form).serialize(), function (html) {
                    jQuery("#" + bodyId).html(html);
                });
            }, 250);
        });
    }
    bindList("appt-filters", "appt-body", "/index.php?action=appointment_rows");
    bindList("record-filters", "record-body", "/index.php?action=record_rows");

    jQuery("#mine-filter").on("input", function () {
        var q = jQuery(this).val().toLowerCase();
        jQuery("#mine-appts tr, #mine-records tr, #mine-invoices tr").each(function () {
            jQuery(this).toggle(jQuery(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    var bell = document.getElementById("nav-bell");
    if (bell) {
        function loadBell() {
            jQuery.getJSON("/index.php?action=notifications", function (data) {
                var count = data.count || 0;
                jQuery("#bell-count").text(count || "").toggle(count > 0);
                if (!data.items || !data.items.length) {
                    jQuery("#bell-panel").html('<p class="lead">Chưa có thông báo.</p>');
                    return;
                }
                var html = "";
                data.items.forEach(function (item) {
                    html += '<a class="bell-item' + (item.is_read ? "" : " is-new") + '" href="' + esc(item.link || "#") + '"><strong>' + esc(item.title) + "</strong><span>" + esc(item.body) + "</span></a>";
                });
                jQuery("#bell-panel").html(html);
            });
        }
        loadBell();
        setInterval(loadBell, 30000);
        bell.addEventListener("click", function (event) {
            event.stopPropagation();
            jQuery("#bell-panel").toggleClass("is-open");
            jQuery.post("/index.php?action=notifications_read");
            jQuery("#bell-count").text("").hide();
        });
        document.addEventListener("click", function () {
            jQuery("#bell-panel").removeClass("is-open");
        });
    }
}