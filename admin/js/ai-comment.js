jQuery(document).ready(function ($) {

    $(document).on("click", ".ai-improve-btn", function () {

        let fieldKey = $(this).data("field");
        let textarea = $("textarea[name='" + fieldKey + "']");

        if (!textarea.length) {
            alert("Textarea not found for field: " + fieldKey);
            return;
        }

        let text = textarea.val().trim();
        if (text === "") {
            alert("Please enter comment before improving.");
            return;
        }

        $(this).text("Improving...").prop("disabled", true);
  jQuery('#ajax-loader').show();
        $.post(AI_IMPROVER.ajax_url, {
            action: "ai_improve_text",
            nonce: AI_IMPROVER.nonce,
            text: text,
            field: fieldKey
        }, function (res) {

            if (res.success) {
                textarea.val(res.data.improved);
            } else {
                alert("AI failed: " + res.data);
            }

            $(".ai-improve-btn[data-field='" + fieldKey + "']")
                .text("✨ Improve with AI")
                .prop("disabled", false);
            jQuery('#ajax-loader').hide();
        });
    });

});
