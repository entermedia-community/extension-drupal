let emediaimagetarget;

(function ($, Drupal) {
  /*
  const channel = new MessageChannel();
  channel.port1.onmessage = function (event) {
    console.log("Received message from iframe:", event.data);
  }
*/
  Drupal.behaviors.emediaLibraryWidget = {
    attach: function (context, settings) {
      
      $('.pull-emedia-asset-button', context).not('.emediaLibraryWidget-processed').each(function () {
        $(this).addClass('emediaLibraryWidget-processed').on('click', function (e) {
          e.preventDefault();

          // Get the default URL and target field ID from the button attributes.
          const parentcontainer = $(this).closest('.field--widget-emedia-library-widget');
          emediaimagetarget = parentcontainer.find('.emedia-image-assetid');
          if (emediaimagetarget.length === 0) {
            console.error('Target field not found in the parent container.');
            return;
          }

          const emedialibraryUrl = $(this).data('emedialibrary-url');
          const currentDrupalUrl = window.location.href; 

          // Open a dialog with an iframe to load content from the external URL.
          $('<div class="emedia-dialog"></div>').dialog({
            classes: {
              "ui-dialog": "emediadialog"
            },
            modal: true,
            open: function () {
              
              // Create an iframe and set its source to the external URL.
              const iframe = $('<iframe>', {
                src: emedialibraryUrl,
                id: 'blockfind',
                name: 'blockfind',
                width: '100%',
                height: '100%',
                frameborder: 0,
                'data-parenturl': currentDrupalUrl,
              });
              $(this).append(iframe);
              const iframeelement = document.getElementById('blockfind');
              iframeelement.addEventListener("load", function(e) {
                iframeelement.contentWindow.postMessage("parenturl:" + currentDrupalUrl, "*");
              });
            },
            close: function () {
              // Remove the dialog element after closing.
              $(this).dialog('destroy').remove();
            },
          });

          if (window.addEventListener) {
            window.addEventListener("message", emediaMessage, false);
          }
          else {
            window.attachEvent("onmessage", emediaMessage);
          }
          
        });
      });

     

      function emediaMessage(event) {

        if (typeof event.data === "string" && event.data.startsWith("assetpicked:")) {
          
          const params = event.data.substring(12);
          if (params !== null && params !== undefined && params !== "") {
            const assetid = JSON.parse(params).assetid;
            if (emediaimagetarget)
            {
              emediaimagetarget.val(assetid);

              /*const iframe = document.getElementById('blockfind');
              if (iframe) {
                iframe.remove(); // Remove the iframe from the DOM.
              }*/
              // Close the Drupal dialog.
              $('.emedia-dialog').dialog('close');
            }
          }
        }
      }



    },
  };

  
})(jQuery, Drupal);


