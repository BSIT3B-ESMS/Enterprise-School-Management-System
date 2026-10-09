$(function () {
  var toastElement = document.getElementById("portal-toast");
  var portalToast = toastElement ? bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 2800 }) : null;
  var uploadedDocuments = new Set();
  var pageLabels = {
    overview: "Overview",
    application: "My application",
    documents: "Requirements",
    exam: "Entrance exam",
    notifications: "Notifications",
    profile: "My profile"
  };

  function showToast(message) {
    $("#toast-message").text(message);
    if (portalToast) portalToast.show();
  }

  function openView(viewName) {
    var $target = $("#view-" + viewName);
    if (!$target.length) return;

    $(".view-section").removeClass("active");
    $target.addClass("active");
    $(".sidebar-nav .nav-link").removeClass("active").filter('[data-view="' + viewName + '"]').addClass("active");
    $("#page-breadcrumb").text(pageLabels[viewName]);
    $("#sidebar").removeClass("open");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  $(document).on("click", "[data-view]", function (event) {
    event.preventDefault();
    openView($(this).data("view"));
  });

  $(".menu-toggle").on("click", function () {
    $("#sidebar").toggleClass("open");
  });

  $(document).on("click", function (event) {
    if ($(window).width() <= 850 && $("#sidebar").hasClass("open") &&
        !$(event.target).closest("#sidebar, .menu-toggle").length) {
      $("#sidebar").removeClass("open");
    }
  });

  $(".alert-close").on("click", function () {
    $(this).closest(".welcome-alert").slideUp(150);
  });

  function saveForm(messageSelector) {
    $(messageSelector).text("Saved just now.");
    showToast("Your changes have been saved.");
    window.setTimeout(function () {
      $(messageSelector).text("");
    }, 3000);
  }

  $("#save-application").on("click", function () {
    saveForm("#save-message");
  });

  $("#application-form").on("submit", function (event) {
    event.preventDefault();
    var form = this;
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    saveForm("#save-message");
  });

  $("#profile-form").on("submit", function (event) {
    event.preventDefault();
    saveForm("#profile-message");
  });

  function updateDocumentSummary() {
    $(".summary-progress").text(uploadedDocuments.size + " of 2 uploaded");
  }

  function handleFile(file, statusSelector) {
    if (!file) return;
    uploadedDocuments.delete(statusSelector);
    updateDocumentSummary();
    var allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
    if (!allowedTypes.includes(file.type)) {
      $(statusSelector).text("Please choose a PDF, JPG, or PNG file.").css("color", "#b64949");
      return;
    }
    if (file.size > 10 * 1024 * 1024) {
      $(statusSelector).text("This file is larger than 10 MB. Please choose a smaller file.").css("color", "#b64949");
      return;
    }
    $(statusSelector).text("✓ " + file.name + " selected").css("color", "#34845e");
    uploadedDocuments.add(statusSelector);
    updateDocumentSummary();
    showToast("Document selected. Submit your application to send it for review.");
  }

  $("#transcript-upload").on("change", function () {
    handleFile(this.files[0], "#transcript-status");
  });

  $("#id-upload").on("change", function () {
    handleFile(this.files[0], "#id-status");
  });

  $(".upload-zone").on("dragover", function (event) {
    event.preventDefault();
    $(this).addClass("drag-over");
  }).on("dragleave", function () {
    $(this).removeClass("drag-over");
  }).on("drop", function (event) {
    event.preventDefault();
    $(this).removeClass("drag-over");
    var file = event.originalEvent.dataTransfer.files[0];
    var input = $(this).find(".file-input")[0];
    if (file && input) {
      handleFile(file, "#" + input.id.replace("-upload", "-status"));
    }
  });

  $("#mark-read").on("click", function () {
    $(".notification-item").removeClass("unread").find(".unread-indicator").remove();
    $(".notification-dot").remove();
    showToast("All notifications marked as read.");
  });

  $(".reserve-button").on("click", function () {
    var session = $(this).data("session");
    showToast("Session " + session + " selected. Complete your application to confirm your booking.");
  });

  $(".logout-button").on("click", function () {
    showToast("Sign out will be available when connected to the ADMITONE account service.");
  });
});
