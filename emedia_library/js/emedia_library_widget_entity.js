

(function ($, Drupal) {

  Drupal.behaviors.emediaLibraryWidgetEntity = {
    attach: function (context, settings) {
     
     if (!emedialibraryUrl) {
        console.error('emediaLibraryUrl is not defined in plugin settings.');
        return; 
     }

      $('.pull-emedia-entity-button', context).not('.emediaLibraryWidget-processed').each(function () {
        $(this).addClass('emediaLibraryWidget-processed').on('click', function (e) {
          e.preventDefault();

          const parentcontainer = $(this).closest('.eml-field-container');
          fieldid = parentcontainer.data("fieldid");
          entityfield = parentcontainer.find('.emedia-entityid');
          entityfieldprimarymedia = parentcontainer.find('.emedia-primarymediaid'); //TODO: use fieldid in the other side to get this fields

          if (entityfield.length === 0) {
            console.error('Target field not found in the parent container.');
            return;
          }
          
          var blockfindUrl = $(this).data('blockfind-url');
          if (!blockfindUrl) {
            blockfindUrl = emedialibraryUrl + '/blockfind/';
          }
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
                src: blockfindUrl,
                id: 'blockfind',
                name: 'blockfind',
                width: '100%',
                height: '100%',
                frameborder: 0
              });
              $(this).append(iframe);
              const iframeelement = document.getElementById('blockfind');
              iframeelement.addEventListener("load", function(e) {
                var message = {
                  parenturl: currentDrupalUrl,
                  target: fieldid,
                  name: 'setEmediaLibraryPicker'
                };
                iframeelement.contentWindow.postMessage(message , "*");
              });
            },
            close: function () {
              // Remove the dialog element after closing.
              $(this).dialog('destroy').remove();
            },
          });

          
          
        });
      });
    },
  };

    
})(jQuery, Drupal);






