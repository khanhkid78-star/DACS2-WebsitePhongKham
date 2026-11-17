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
    var tab = sessionStorage.getItem("pk_tab") || "";
    fetch("/index.php?action=whoami&tab=" + encodeURIComponent(tab), { credentials: "same-origin", cache: "no-store" })
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

    jQuery(document).on("click", "[data-open-box]", function () {
        openBox(jQuery(this).attr("data-open-box"));
    });

    jQuery(document).on("click", "[data-appt]", function () {
        var data = JSON.parse(jQuery(this).attr("data-appt"));
        if (data.status === "Cancelled") {
            return;
        }
        jQuery("#appt-id").val(data.id);
        jQuery("#appt-label").text(data.id);
        jQuery("#appt-doctor").val(String(data.doctor_id));
        jQuery("#appt-date").val(data.date);
        jQuery("#appt-note").val(data.note);
        jQuery("#appt-status-fixed").remove();
        if (data.status === "Completed") {
            jQuery("#appt-status").val("Scheduled").prop("disabled", true).removeAttr("name");
            jQuery("#appt-status").after('<input type="hidden" name="status" id="appt-status-fixed" value="Completed">');
            jQuery("#appt-status-lock").show();
        } else {
            jQuery("#appt-status").attr("name", "status").prop("disabled", false).val("Scheduled");
            jQuery("#appt-status-lock").hide();
        }
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

    // Click cả row (trừ nút Sửa) → popup chi tiết hồ sơ + đơn thuốc
    jQuery(document).on("click", "tr.record-row", function (e) {
        if (jQuery(e.target).closest("[data-record], button, a").length) {
            return;
        }
        var id = jQuery(this).attr("data-record-id");
        if (!id) return;

        jQuery("#detail-title").text("Chi tiết hồ sơ #" + id);
        jQuery("#detail-body").html("<p class=\"lead\">Đang tải…</p>");
        openBox("record-detail-modal");

        jQuery.getJSON("/index.php?action=record_detail&id=" + encodeURIComponent(id))
            .done(function (res) {
                if (!res || !res.ok || !res.data) {
                    jQuery("#detail-body").html("<p class=\"lead\">" + (res && res.error ? res.error : "Không tải được hồ sơ") + "</p>");
                    return;
                }
                var d = res.data;
                var visit = d.visit_date ? new Date(d.visit_date) : null;
                var visitStr = visit && !isNaN(visit)
                    ? ("0" + visit.getHours()).slice(-2) + ":" + ("0" + visit.getMinutes()).slice(-2) + " "
                      + ("0" + visit.getDate()).slice(-2) + "/" + ("0" + (visit.getMonth() + 1)).slice(-2) + "/" + visit.getFullYear()
                    : (d.visit_date || "");

                var html = "";
                html += "<p><strong>Bệnh nhân:</strong> " + esc(d.patient_name || "") + "</p>";
                html += "<p><strong>Bác sĩ:</strong> " + esc(d.doctor_name || "") + "</p>";
                html += "<p><strong>Ngày khám:</strong> " + esc(visitStr) + "</p>";
                if (d.appointment_id) {
                    html += "<p><strong>Mã lịch hẹn:</strong> #" + esc(String(d.appointment_id)) + "</p>";
                }
                html += "<p><strong>Triệu chứng:</strong><br>" + esc(d.symptoms || "").replace(/\n/g, "<br>") + "</p>";
                html += "<p><strong>Chẩn đoán:</strong><br>" + esc(d.diagnosis || "").replace(/\n/g, "<br>") + "</p>";
                html += "<p><strong>Kết luận:</strong><br>" + esc(d.conclusion || "").replace(/\n/g, "<br>") + "</p>";

                var meds = d.medicines || [];
                html += "<h3 style=\"margin-top:1rem\">Đơn thuốc</h3>";
                if (!meds.length) {
                    html += "<p class=\"lead\">Không có đơn thuốc.</p>";
                } else {
                    html += "<div class=\"table-wrap\"><table><thead><tr>";
                    html += "<th>Thuốc</th><th>Đơn vị</th><th>SL</th><th>Liều</th><th>Cách dùng</th><th>Đơn giá</th>";
                    html += "</tr></thead><tbody>";
                    meds.forEach(function (m) {
                        html += "<tr>";
                        html += "<td>" + esc(m.medicine_name || "") + "</td>";
                        html += "<td>" + esc(m.unit || "") + "</td>";
                        html += "<td>" + esc(String(m.quantity != null ? m.quantity : "")) + "</td>";
                        html += "<td>" + esc(m.dosage || "") + "</td>";
                        html += "<td>" + esc(m.usage_instructions || "") + "</td>";
                        html += "<td>" + esc(m.price != null ? String(m.price) : "") + "</td>";
                        html += "</tr>";
                    });
                    html += "</tbody></table></div>";
                }
                jQuery("#detail-body").html(html);
            })
            .fail(function () {
                jQuery("#detail-body").html("<p class=\"lead\">Lỗi kết nối khi tải hồ sơ.</p>");
            });
    });

    jQuery("#open-new-medicine").on("click", function () {
        jQuery("#medicine-title").text("Thêm thuốc");
        jQuery("#medicine-form")[0].reset();
        jQuery("#medicine-id").prop("disabled", true).val("");
        jQuery("#medicine-submit").text("Thêm thuốc");
        openBox("medicine-modal");
    });

    jQuery(document).on("click", "[data-medicine]", function () {
        var data = JSON.parse(jQuery(this).attr("data-medicine"));
        jQuery("#medicine-title").text("Sửa thuốc #" + data.id);
        jQuery("#medicine-id").prop("disabled", false).val(data.id);
        jQuery("#medicine-name").val(data.name);
        jQuery("#medicine-unit").val(data.unit);
        jQuery("#medicine-price").val(data.price);
        jQuery("#medicine-description").val(data.description);
        jQuery("#medicine-submit").text("Cập nhật");
        openBox("medicine-modal");
    });

    function bindList(formId, bodyId, url) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            jQuery.get(url, jQuery(form).serialize(), function (html) {
                jQuery("#" + bodyId).html(html);
            });
        });
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
    bindList("medicine-filters", "medicine-body", "/index.php?action=medicine_rows");

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
                    jQuery("#bell-panel").html('<p class="bell-empty">Chưa có thông báo.</p>');
                    return;
                }
                var html = "";
                data.items.forEach(function (item) {
                    html += '<a class="bell-item' + (item.is_read ? "" : " is-new") + '" href="' + esc(item.link || "#") + '">'
                        + '<strong>' + esc(item.title) + '</strong>'
                        + '<span>' + esc(item.body) + '</span></a>';
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


    document.addEventListener("click", function (event) {
        var link = event.target.closest ? event.target.closest("a") : null;
        if (!link || !link.href) return;
        var url = new URL(link.href, location.origin);
        if (url.origin !== location.origin || url.searchParams.get("tab")) return;
        url.searchParams.set("tab", sessionStorage.getItem("pk_tab"));
        link.href = url.toString();
    }, true);

    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!form || form.tagName !== "FORM") return;
        var field = form.querySelector("input[name=tab]");
        if (!field) {
            field = document.createElement("input");
            field.type = "hidden";
            field.name = "tab";
            form.appendChild(field);
        }
        field.value = sessionStorage.getItem("pk_tab") || "";
    }, true);

    if (window.jQuery) {
        jQuery.ajaxPrefilter(function (options) {
            var id = sessionStorage.getItem("pk_tab");
            if (!id || !options.url) return;
            options.url += (options.url.indexOf("?") === -1 ? "?" : "&") + "tab=" + encodeURIComponent(id);
        });
    }
}