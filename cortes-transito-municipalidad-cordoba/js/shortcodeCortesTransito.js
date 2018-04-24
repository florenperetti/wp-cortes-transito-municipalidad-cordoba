(function() {
  tinymce.create('tinymce.plugins.busccortestransitocba_button', {
    init: function(ed, url) {
      ed.addCommand('busccortestransitocba_insertar_shortcode', function() {
        selected = tinyMCE.activeEditor.selection.getContent();
        var content = '';

        ed.windowManager.open({
          title: 'Buscador de Cortes de Tránsito',
          body: [{
            type: 'textbox',
            name: 'pag',
            label: 'Cantidad de Resultados'
          }],
          onsubmit: function(e) {
            var pags = Number(e.data.pag.trim());
            ed.insertContent( '[buscador_cortes_transito_cba' + (pags && Number.isInteger(pags) ? ' pag="'+pags+'"' : '') + ']' );
          }
        });
        tinymce.execCommand('mceInsertContent', false, content);
      });
      ed.addButton('busccortestransitocba_button', {title : 'Insertar buscador de Cortes de Tránsito', cmd : 'busccortestransitocba_insertar_shortcode', image: url.replace('/js', '') + '/images/logo-shortcode.png' });
    }
  });
  tinymce.PluginManager.add('busccortestransitocba_button', tinymce.plugins.busccortestransitocba_button);
})();