(function(window, document, $) {

  const $CTM = $('#CTM');
  const $form = $CTM.find('form');
  const $resultados = $CTM.find('.resultados');
  const $reset = $CTM.find('#filtros__reset');

  $reset.click(function(e) {
    e.preventDefault();
    $form[0].reset();
    $form.submit();
  });

  $form.submit(function(e) {
    e.preventDefault();
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarCortesTransito.url,
      data: {
        action: 'buscar_cortes_transito',
        nonce: buscarCortesTransito.nonce,
        nombre: $form.serializeArray()[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
        }
      }
    });
  });

  $(document).on('click','#CTM .paginacion__boton', function(e) {
    const pagina = $(this).data('pagina');
    const $boton = $(e.target);
    const texto = $boton.html();
    $boton.html('...');
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarCortesTransito.url,
      data: {
        action: 'buscar_cortes_transito_pagina',
        nonce: buscarCortesTransito.nonce,
        pagina: pagina,
        nombre: $form.serializeArray()[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
          $('body').animate({scrollTop: 50}, 1000);
        }
      },
      done: function() {
        $boton.html(texto);
      }
    });
  });
})(window, document, jQuery);