console.log("Page-title-notification loaded.");

(function(window, document){

  window.pageTitleNotification = (function () {

      var config = {
          currentTitle: null,
          interval: null
      };

      var on = function (notificationText, intervalSpeed) {
          if (!config.interval) {
              config.currentTitle = document.title;
              config.interval = window.setInterval(function() {
                  document.title = (config.currentTitle === document.title)
                      ? notificationText
                      : config.currentTitle;
              }, (intervalSpeed) ? intervalSpeed : 1000);
          }
      };

      var off = function () {
          window.clearInterval(config.interval);
          config.interval = null;
          if (config.currentTitle !== null)
          {
            document.title = config.currentTitle;
          }
      };

      return {
          on: on,
          off: off
      };

  })();

}(window, document));