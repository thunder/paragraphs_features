(function($, Drupal, drupalSettings) {

  /**
   * Click handler for paragraphs behavior action buttons.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphBehaviorsToggle = {
    attach(context) {
      $(context)
        .find(".js-paragraphs-button-behaviors")
        .once("add-click-handler")
        .each((index, element) => {
          const $button = $(element);
          const $parWidget = $button.closest(".paragraph-top").parent();

          $button.addClass("content-active");
          $parWidget.addClass("content-active");

          $button.on("click", event => {
            const $trigger = $(event.target);

            const $currentParWidget = $trigger
              .closest(".paragraph-top")
              .parent();

            if ($currentParWidget.hasClass("content-active")) {
              $trigger
                .removeClass("content-active")
                .addClass("behavior-active");
              event.target.value = "Content";

              $currentParWidget.find(".paragraphs-behavior").show();
              $currentParWidget.find(".paragraphs-subform").hide();
              $currentParWidget
                .removeClass("content-active")
                .addClass("behavior-active");
            } else {
              $trigger
                .removeClass("behavior-active")
                .addClass("content-active");
              event.target.value = "Settings";

              $currentParWidget.find(".paragraphs-behavior").hide();
              $currentParWidget.find(".paragraphs-subform").show();
              $currentParWidget
                .removeClass("behavior-active")
                .addClass("content-active");
            }

            event.preventDefault();

            return false;
          });
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
