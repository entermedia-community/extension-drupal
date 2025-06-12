let assetfield;
let fieldid;

var ckeditor;

(function ($, Drupal) {
  let emedialibraryUrl =  ''; 
  let emedialibraryKey =  '';

  Drupal.behaviors.emediaLibraryWidget = {
    attach: function (context, settings) {
     
    if (drupalSettings.emedia_library !== undefined) {
    
      emedialibraryUrl = drupalSettings.emedia_library.emedialibraryUrl || '';
      emedialibraryKey = drupalSettings.emedia_library.emedialibraryKey || '';
    }

     if (!emedialibraryUrl) {
        console.error('emediaLibraryUrl is not defined in plugin settings.');
        return; 
     }

      $('.pull-emedia-asset-button', context).not('.emediaLibraryWidget-processed').each(function () {
        $(this).addClass('emediaLibraryWidget-processed').on('click', function (e) {
          e.preventDefault();

          const parentcontainer = $(this).closest('.eml-field-container');
          fieldid = parentcontainer.data("fieldid");
          assetfield = parentcontainer.find('.emedia-image-assetid');

          if (assetfield.length === 0) {
            console.error('Target field not found in the parent container.');
            return;
          }
        
          const blockfindUrl = emedialibraryUrl + '/blockfind/';
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

  if (window.addEventListener) {
    window.addEventListener("message", emediaMessage, false);
  }
  else {
    window.attachEvent("onmessage", emediaMessage);
  }


  async function emediaMessage(event) {

    if (event.data !== null && event.data !== undefined) {
      var message = event.data;
        
        const assetid = message.assetid;
        const url = `${emedialibraryUrl}/mediadb/services/module/asset/data/${assetid}`;

        let responseData = null;

        try {
          const response = await fetch(url, {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              'X-tokentype': 'entermedia',
              'X-token': emedialibraryKey
            },
          });

          if (response.ok) {
            responseData = await response.json();
            console.log('Asset Data:', responseData);
          } else {
            console.error('Failed to fetch asset data:', response.statusText);
          }
        } catch (error) {
          console.error('Error during GET call:', error);
        }     

        const assetData = responseData?.data || null;

          if (message.target == 'ckeditor5') {
            // If the target is ckeditor5, we need to update the editor.
            if (ckeditor !== undefined) {
              updateEditor(ckeditor, assetData);
            }
          }
          else if (message.target != '') {
            assetfield.val(assetid);
            const assetSrc = assetData.downloads[0].download;
            
            
            let img = $('#eml-thumbnail-' + fieldid + '');

            if (img.length === 0) {
              
              img = $('<img>', {
                id: 'eml-thumbnail-' + fieldid,
                src: assetSrc, 
              });
            
              assetfield.closest('.eml-field-container').find('.emedia-thumbnail-wrapper').append(img);
            } else {
              img.attr('src', assetSrc);
            }
            $('.emedia-dialog').dialog('close');
          }

          // Close the Drupal dialog.
          jQuery('.emedia-dialog').dialog('close');

      }
  }

  function updateEditor(editor, assetData) {
    // Insert the asset data into the editor.
    const assetSrc = assetData.downloads[0].download;
    editor.model.change((writer) => {
     const emediaBox = writer.createElement('emediaBox');
     
     const emediaImg = writer.createElement('emediaImg', {
       src: assetSrc || '',
       alt: assetData.assettitle || ''
     });
     writer.append(emediaImg, emediaBox);

     let caption = ''
     //UN: Caption  copyrightnotice | headline en
     if (assetData.copyrightnotice != '') {
       caption = assetData.copyrightnotice;
       if (assetData.headline && assetData.headline.en != '') {
         caption += ' | ' + assetData.headline.en;
       }
     }
     else {
       caption = assetData.assettitle || '';
     }
 
     const emediaCaption = writer.createElement('emediaCaption');
     writer.insertText(caption, emediaCaption);
     writer.append(emediaCaption, emediaBox);
     
     const insertPosition = editor.model.document.selection.getFirstPosition() || 0;
     writer.insert(emediaBox, insertPosition);
     //editor.model.insertContent(emediaBox, editor.model.document.selection);
   });
 }

  
})(jQuery, Drupal);












/*

// Listen for the "message" event using addEventListener.
window.addEventListener('message', async (event) => {
  if (typeof event.data === 'string' && event.data.startsWith('assetpicked:')) {
    const params = event.data.substring(12);
    if (params !== null && params !== undefined && params !== '') {
      const assetid = JSON.parse(params).assetid;

      // Perform a GET call to fetch asset data.
      const url = `${emedialibraryUrl}/mediadb/services/module/asset/data/${assetid}`;
      let responseData = null;

      try {
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-tokentype': 'entermedia',
            'X-token': emedialibraryKey
          },
        });

        if (response.ok) {
          responseData = await response.json();
          console.log('Asset Data:', responseData);
        } else {
          console.error('Failed to fetch asset data:', response.statusText);
        }
      } catch (error) {
        console.error('Error during GET call:', error);
      }

      // Close the Drupal dialog.
      jQuery('.emedia-dialog').dialog('close');

      const assetData = responseData?.data || null;

      if (assetData && Array.isArray(assetData.downloads) && assetData.downloads.length > 0) {
        
        updateEditor(editor, assetData);
       
      } else {
        console.error('No downloads available in asset data.');
      }
    }
  }
});

*/