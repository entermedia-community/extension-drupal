let assetfield;
let fieldid;
let emedialibraryUrl =  ''; 
let emedialibraryKey =  '';

var ckeditor;

(function ($, Drupal) {

  if (drupalSettings.emedia_library !== undefined) {
  
    emedialibraryUrl = drupalSettings.emedia_library.emedialibraryUrl || '';
    emedialibraryKey = drupalSettings.emedia_library.emedialibraryKey || '';
  }
  
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


