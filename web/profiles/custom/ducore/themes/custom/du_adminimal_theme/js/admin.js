//identify each environment and add class to body
(function() {
    'use strict';

    // local
    if(window.location.href.indexOf("pantheonlocal") > -1) {
        document.body.className = "local-env adminimal-admin-toolbar adminimal";
    }

    if(window.location.href.match(/(dev-).*.pantheonsite.io/) ){
        document.body.className = "dev-env adminimal-admin-toolbar adminimal";
    }

    // test
        if(window.location.href.match(/(test-).*.pantheonsite.io/) ){
            document.body.className = "test-env adminimal-admin-toolbar adminimal";
        }
    // see admin-styles.css for env styles

    // Some Widen selections have a max height issue
    Drupal.behaviors.contentWidenHeightMax = {
        attach: function (context, settings) {

            var widenFrame = document.querySelector("#entity-browser-browse-files-modal-form .file-browser-actions .entities-list .item-container img");

            if (widenFrame !== null) {
                // Set the max height to 425px so that the image doesn't get cut off and remove the "Use Selected" button
                widenFrame.style.maxHeight = "425px";
            }
        }
    };
})();