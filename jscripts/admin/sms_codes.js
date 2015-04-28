// Kliknięcie dodania kodu SMS
$(document).delegate("#button_add_sms_code", "click", function () {
    action_box.create();
    getnset_template(action_box.box, "admin_add_sms_code", true, {}, function () {
        action_box.show();
    });
});

// Usuwanie kodu SMS
$(document).delegate("[id^=delete_row_]", "click", function () {
    var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));
    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: {
            action: "delete_sms_code",
            id: row_id.children("td[headers=id]").text()
        },
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            if (jsonObj.return_id == "deleted") {
                // Usuń row
                row_id.fadeOut("slow");
                row_id.css({"background": "#FFF4BA"});

                // Odśwież stronę
                refresh_brick("sms_codes", true);
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy usuwaniu kodu SMS.", false);
        }
    });
});

// Dodanie kodu SMS
$(document).delegate("#form_add_sms_code", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: $(this).serialize() + "&action=add_sms_code",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content)))
                return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function (name, text) {
                    var id = $("#form_add_sms_code [name=\"" + name + "\"]");
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == "added") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_brick("sms_codes", true);
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy dodawaniu kodu SMS.", false);
        }
    });
});