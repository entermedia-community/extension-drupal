import Plugin from '@ckeditor/ckeditor5-core/src/plugin.js';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview.js';

export default class EMediaLibraryPicker extends Plugin {
  static get pluginName() {
    return 'EMediaLibraryPicker';
  }

  init() {
    const editor = this.editor;

    editor.ui.componentFactory.add('emediaLibraryPicker', locale => {
      const button = new ButtonView(locale);

      button.set({
        label: 'Insert from Media Library',
        tooltip: true,
        withText: true,
      });

      button.on('execute', () => {
        const iframe = document.createElement('iframe');
        iframe.src = '/emedia-picker-ui';
        iframe.style.position = 'fixed';
        iframe.style.top = '10%';
        iframe.style.left = '10%';
        iframe.style.width = '80%';
        iframe.style.height = '80%';
        iframe.style.zIndex = '10000';
        iframe.style.border = '2px solid #000';
        iframe.id = 'emedia-picker-iframe';

        document.body.appendChild(iframe);

        const handleMessage = event => {
          if (event.origin !== window.location.origin) return;

          const imageUrl = event.data.imageUrl;
          if (imageUrl) {
            const imageHtml = `<img src="${imageUrl}" alt="Media">`;
            editor.model.change(writer => {
              const viewFragment = editor.data.processor.toView(imageHtml);
              const modelFragment = editor.data.toModel(viewFragment);
              editor.model.insertContent(modelFragment);
            });

            iframe.remove();
            window.removeEventListener('message', handleMessage);
          }
        };

        window.addEventListener('message', handleMessage);
      });

      return button;
    });
  }
}
