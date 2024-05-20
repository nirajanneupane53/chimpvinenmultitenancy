jQuery(document).ready(function ($) {
  $("#bulk-upload-form").on("submit", function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append("action", "bulk_upload");
    formData.append("nonce", $("#cv_organization_nonce").val());

    $("#loading").show();
    $("#log").empty();

    $.ajax({
      url: ajax_object.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $("#loading").hide();
        if (response.success) {
          $("#log").append(
            "<div>Success: " +
              response.data.successCount +
              " rows processed successfully.</div>"
          );
          $("#log").append(
            "<div>Error: " +
              response.data.errorCount +
              " rows encountered errors.</div>"
          );
          $.each(response.data.logs, function (index, log) {
            $("#log").append("<div >" + log + "</div>");
          });
        } else {
          $("#log").append("<div>Error: " + response.data + "</div>");
        }
      },
      error: function (xhr, status, error) {
        $("#loading").hide();
        $("#log").append("<div>Error: " + error + "</div>");
      },
    });
  });
});
