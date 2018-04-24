<?php
/*
Plugin Name: Buscador de Cortes de Tr&aacute;nsito de la Municipalidad de C&oacute;rdoba
Plugin URI: https://github.com/florenperetti/wp-cortes-transito-municipalidad-cordoba
Description: Este plugin a&ntilde;ade un shortcode que genera un buscador de cortes de tr&aacute;nsito en la ciudad de C&oacute;rdoba, y un scroller que muestra los cortes actuales.
Version: 1.1.8
Author: Florencia Peretti
Author URI: https://github.com/florenperetti/
*/

setlocale(LC_ALL,"es_ES");
date_default_timezone_set('America/Argentina/Cordoba');

add_action('plugins_loaded', array('CortesTransitoMuniCordoba', 'get_instancia'));

class CortesTransitoMuniCordoba
{
	public static $instancia = null;

	private static $URL_API_GOB = 'https://gobiernoabierto.cordoba.gob.ar/api/v2/cortes-de-transito/cortes-activos/';

	public $nonce_busquedas = '';

	public static function get_instancia()
	{
		if (null == self::$instancia) {
			self::$instancia = new CortesTransitoMuniCordoba();
		} 
		return self::$instancia;
	}

	private function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'cargar_assets'));

		add_action('wp_ajax_buscar_cortes_transito', array($this, 'buscar_cortes_transito')); 
		add_action('wp_ajax_nopriv_buscar_cortes_transito', array($this, 'buscar_cortes_transito'));
		
		add_action('wp_ajax_buscar_cortes_transito_pagina', array($this, 'buscar_cortes_transito_pagina')); 
		add_action('wp_ajax_nopriv_buscar_cortes_transito_pagina', array($this, 'buscar_cortes_transito_pagina'));
		
		add_shortcode('buscador_cortes_transito_cba', array($this, 'render_shortcode_buscador_cortes_transito'));
		add_shortcode('scroller_cortes_transito_cba', array($this, 'render_shortcode_scroller_cortes_transito'));

		add_action('init', array($this, 'boton_shortcode_buscador_cortes_transito'));
	}

	public function render_shortcode_scroller_cortes_transito($atributos = [], $content = null, $tag = '')
	{
	    $url = self::$URL_API_GOB.'?momento=10';

    	$api_response = wp_remote_get($url);

    	$resultado = $this->chequear_respuesta($api_response, 'los cortes de tr&aacute;nsito', 'cortes_transito_muni_cba');
		
		echo '<style>
#SCTM .simple-ticker {
	position: relative;
	width: 100%;
	padding: 10px;
	margin: 0;
	height:60px;
	overflow: hidden;
	text-align: center;
	font-size: 1.3em;
	color: white;
	font-weight: bold;
	background-color: #00a665;
	letter-spacing: 1px;
}
#SCTM .simple-ticker ul {
	position: relative;
	width: 100%;
	margin: 0;
	padding: 0;
	list-style: none;
}
#SCTM .simple-ticker ul li {
	display: none;
	width: 100%;
	line-height:40px;
	margin: 0;
	padding: 0;
}
@media all and (max-width: 500px) {
	#SCTM .simple-ticker {
	  height: 90px;
	}
}
@media all and (max-width: 380px) {
	#SCTM .simple-ticker {
	  height: 120px;
	}
}
</style>';

		if ($resultado['count'] > 0) {
			$cortes_html = '';
			$clase = '';
			if ($resultado['count'] == 1) {
				$clase = ' class="tickerHook" style="top: 0px; left: 0px; position: absolute; display: block; opacity: 1; z-index: 98;"';
			}
			
			foreach ($resultado['results'] as $key => $corte) {
				$esquina = $corte['esquina'] ? ' esq. ' .$corte['esquina'] : '';
				$entre = $corte['entre_calle_1'] && $corte['entre_calle_2'] ? ', entre '.$corte['entre_calle_1'].' y '.$corte['entre_calle_2'] : '';
				$mas_info = $corte['observaciones'] ? '<li><b>Observaciones:</b> '.$corte['observaciones'].'</li>' : '';
				$direccion = $corte['direccion_transito'] != 'Desconocida' ? '<li><b>Direccion del tr&aacute;nsito:</b> '.$corte['direccion_transito'].'</li>' : '';
				$carril = $corte['carril'] != 'Desconocido' ? '<li><b>Carril:</b> '.$corte['carril'].'</li>' : '';
				
				$cortes_html .= '<li'.$clase.'>'.($corte['alcance'] != 'Desconocido' ? $corte['alcance'] : 'Corte').' en '.$corte['calle_principal'].$esquina.$entre.'</li>';
			}
			
			echo '<div id="SCTM">
<div class="simple-ticker" id="js-ticker-roll">
	<ul>'.$cortes_html.'</ul>
</div></div>';
				
			if ($resultado['count'] > 1) {
				echo '<script>(function($) {
  $.simpleTicker = function(element, options) {
    var defaults = {
      speed : 500,
      delay : 6000,
      easing : "swing",
      effectType : "fade"
    }
    var param = {
      "ul" : "",
      "li" : "",
      "initList" : "",
      "ulWidth" : "",
      "liHeight" : "",
      "tickerHook" : "tickerHook",
      "effect" : {}
    }

    var plugin = this;
    plugin.settings = {}
    var $element = $(element),
      element = element;

    plugin.init = function() {
      plugin.settings = $.extend({}, defaults, options);
      param.ul = element.children("ul");
      param.li = element.find("li");
      param.initList = element.find("li:first");
      param.ulWidth = param.ul.width();
      param.liHeight = param.li.height();

      //element.css({height:(param.liHeight)});
      param.li.css({top:"0", left:"0", position:"absolute"});


      switch (plugin.settings.effectType) {
        case "fade":
          plugin.effect.fade();
          break;
        case "roll":
          plugin.effect.roll();
          break;
        case "slide":
          plugin.effect.slide();
          break;
      }
      plugin.effect.exec();
    }

    plugin.effect = {};
    plugin.effect.exec = function() {
      param.initList.css(param.effect.init.css)
        .animate(param.effect.init.animate,plugin.settings.speed,plugin.settings.easing)
        .addClass(param.tickerHook);
      setInterval(function(){
        element.find("." + param.tickerHook)
          .animate(param.effect.start.animate,plugin.settings.speed,plugin.settings.easing)
          .next()
          .css(param.effect.next.css)
          .animate(param.effect.next.animate,plugin.settings.speed,plugin.settings.easing)
          .addClass(param.tickerHook)
          .end()
          .appendTo(param.ul)
          .css(param.effect.end.css)
          .removeClass(param.tickerHook);
      }, plugin.settings.delay);
    }

    plugin.effect.fade = function() {
      param.effect = {
        "init" : {
          "css" : {display:"block",opacity:"0"},
          "animate" : {opacity:"1",zIndex:"98"}
        },
          "start" : {
          "animate" : {opacity:"0"}
        },
        "next" : {
          "css" : {display:"block",opacity:"0",zIndex:"99"},
          "animate" : {opacity:"1"}
        },
        "end" : {
          "css" : {display:"none",zIndex:"98"}
        }
      }
    }

    plugin.effect.roll = function() {
      param.effect = {
        "init" : {
          "css" : {top:"3em",display:"block",opacity:"0"},
          "animate" : {top:"0",opacity:"1",zIndex:"98"}
        },
        "start" : {
          "animate" : {top:"-3em",opacity:"0"}
        },
        "next" : {
          "css" : {top:"3em",display:"block",opacity:"0",zIndex:"99"},
          "animate" : {top:"0",opacity:"1"}
        },
        "end" : {
          "css" : {zIndex:"98"}
        }
      }
    }

    plugin.effect.slide = function() {
      param.effect = {
        "init" : {
          "css" : {left:(200),display:"block",opacity:"0"},
          "animate" : {left:"0",opacity:"1",zIndex:"98"}
        },
        "start" : {
          "animate" : {left:(-(200)),opacity:"0"}
        },
        "next" : {
          "css" : {left:(param.ulWidth),display:"block",opacity:"0",zIndex:"99"},
          "animate" : {left:"0",opacity:"1"}
        },
        "end" : {
          "css" : {zIndex:"98"}
        }
      }
    }

    plugin.init();
  }

  $.fn.simpleTicker = function(options) {
    return this.each(function() {
      if (undefined == $(this).data("simpleTicker")) {
        var plugin = new $.simpleTiecker(this, options);
        $(this).data("simpleTicker", plugin);
      }
    });
  }
})(jQuery);

(function($){
  $.simpleTicker($("#js-ticker-roll"), {"effectType":"roll"});
})(jQuery);</script>';
			}
		} else {
			echo '<div id="SCTM">
<div class="simple-ticker" id="js-ticker-roll">
	<ul><li class="tickerHook" style="top: 0px; left: 0px; position: absolute; display: block; opacity: 1; z-index: 98;"><a href="/obras/">Ver todos los cortes</a></ul>
</div></div>';
		}
	}

	public function render_shortcode_buscador_cortes_transito($atributos = [], $content = null, $tag = '')
	{
	    $atributos = array_change_key_case((array)$atributos, CASE_LOWER);
	    $atr = shortcode_atts([
            'pag' => 10
        ], $atributos, $tag);

	    $cantidad_por_pagina = $atr['pag'] == 0 ? '' : '?page_size='.$atr['pag'];

	    $url = self::$URL_API_GOB.$cantidad_por_pagina;

    	$api_response = wp_remote_get($url);

    	$resultado = $this->chequear_respuesta($api_response, 'los cortes de tr&aacute;nsito', 'cortes_transito_muni_cba');

		echo '<div id="CTM">
	<form>
		<div class="filtros">
			<div class="filtros__columnas">
				<label class="filtros__label" for="nombre">Buscar</label>
				<input type="text" name="nombre">
				<button id="filtros__buscar" type="submit">Buscar</button>
			</div>
			<div class="filtros__columnas">
				<button id="filtros__reset">Todos</button>
			</div>
		</div>
	</form>
	<div class="resultados">';
		echo $this->renderizar_resultados($resultado,$atr['pag'],'');
		echo '</div></div>';
	}
	
	private function renderizar_resultados($datos,$pag = 10, $q)
	{
		$html = '';
		
		if (count($datos['results']) > 0) {
			$html .= '<p class="cantidad-resultados">
				<small>Mostrando '.count($datos['results']).' de '.$datos['count'].' resultados</small></p>';
			
			foreach ($datos['results'] as $key => $corte) {
				$gravedad = $corte['gravedad'];
				if (strrpos($gravedad, 'alta') !== false) {
					$gravedad = ' resultado__estado--alta';
				} elseif (strrpos($gravedad, 'media') !== false) {
					$gravedad = ' resultado__estado--media';
				} elseif (strrpos($gravedad, 'desconocida') !== false) {
					$gravedad = ' resultado__estado--ninguno';
				} else {
					$gravedad = '';
				}
			
				$esquina = $corte['esquina'] ? ' esq. ' .$corte['esquina'] : '';
				$entre = $corte['entre_calle_1'] && $corte['entre_calle_2'] ? ', entre '.$corte['entre_calle_1'].' y '.$corte['entre_calle_2'] : '';
				$mas_info = $corte['observaciones'] ? '<li><b>Observaciones:</b> '.$corte['observaciones'].'</li>' : '';
				$direccion = $corte['direccion_transito'] != 'Desconocida' ? '<li><b>Direccion del tr&aacute;nsito:</b> '.$corte['direccion_transito'].'</li>' : '';
				$carril = $corte['carril'] != 'Desconocido' ? '<li><b>Carril:</b> '.$corte['carril'].'</li>' : '';
				
				$autoridades = $corte['autoridades_presentes'];
				$autoridades_html = '';
				
				if(!empty($autoridades)) {
					$autoridades_html ='<li><b>Autoridades presentes:</b><ul>';
					foreach($autoridades as $key => $aut) {
						$autoridades_html .= '<li>'.$aut['nombre'].'</li>';
					}
					$autoridades_html .= '</ul></li>';
				}
				
				$momento = $corte['momento'] == 'Corte programado' ? '<p style="margin-bottom:10px;"><b style="color:#00ac65">'.$corte['momento'].'</b></p>' : '<p style="margin-bottom:10px;"><b style="color:#c7c10a">'.$corte['momento'].'</b></p>';
				
				
				$duracion = $corte['momento'] == 'Corte programado' ? '<li><b>Duraci&oacute;n total:</b> '.$this->restar_fechas($corte['fecha_inicio_estimada'],$corte['fecha_finalizacion_estimada']).'</li>' : '';
				
				$html .= '<div class="resultado__container">
						<div class="resultado__cabecera"><span class="resultado__nombre">'.$corte['calle_principal'].$esquina.'</span><span class="resultado__estado'.$gravedad.'">'.str_replace('Gravedad','Importancia',$corte['gravedad']).'</span></div>
						<div class="resultado__info">
						
							<ul>
								<p style="margin:15px 0 5px">'.($corte['alcance'] != 'Desconocido' ? $corte['alcance'] : 'Corte').' en '.$corte['calle_principal'].$esquina.$entre.'.</p>'.$momento.'
								<li><b>Motivo:</b> '.$corte['motivo'].'</li>
								<li><b>Fecha de inicio estimada:</b> '.$this->formatear_fecha($corte['fecha_inicio_estimada']).'</li>
								<li><b>Fecha de finalizaci&oacute;n estimada:</b> '.$this->formatear_fecha($corte['fecha_finalizacion_estimada']).'</li>'
								.$duracion.
								'<li><b>Responsable:</b> '.$corte['responsable'].'</li>
								'.$carril.$mas_info.$direccion.$autoridades_html.'
							</ul>
						</div>
					</div>';
			}
			
			if ($datos['next'] != 'null' || $datos['previous'] != 'null') {
				$html .= $this->renderizar_paginacion($datos['previous'], $datos['next'], ($pag ? 10 : $pag), $datos['count'], $q);
			}
			
		} else {
			$html .= '<p class="resultados__mensaje">No hay resultados</p>';
		}
		
		return $html;
	}
	
	public function renderizar_paginacion($anterior, $siguiente, $tamanio = 10, $total, $query = '')
	{
		$html = '<div class="paginacion">';
		
		$botones = $total % $tamanio == 0 ? $total / $tamanio : ($total / $tamanio) + 1;

		$actual = 1;
		if ($anterior != null) {
			$actual = $this->obtener_parametro($anterior,'page', 1) + 1;;
		} elseif ($siguiente != null) {
			$actual = $this->obtener_parametro($siguiente,'page', 1) - 1;
		}
		
		$query = '&q='.$query;
		
		for	($i = 1; $i <= $botones; $i++) {
			if ($i == $actual) {
				$html .= '<button type="button" class="paginacion__boton paginacion__boton--activo" disabled>'.$i.'</button>';
			} else {
				$html .= '<button type="button" class="paginacion__boton" data-pagina="'.self::$URL_API_GOB.'?page='.$i.'&page_size='.$tamanio.$query.'">'.$i.'</button>';
			}
		}
		
		$html .= '</div>';
		
		return $html;
	}

	public function boton_shortcode_buscador_cortes_transito()
	{
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
			return;
		add_filter("mce_external_plugins", array($this, "registrar_tinymce_plugin")); 
		add_filter('mce_buttons', array($this, 'agregar_boton_tinymce_shortcode_buscador_cortes_transito'));
	}
	
	public function boton_shortcode_scroller_cortes_transito()
	{
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
			return;
		add_filter("mce_external_plugins", array($this, "registrar_tinymce_plugin")); 
		add_filter('mce_buttons', array($this, 'agregar_boton_tinymce_shortcode_scroller_cortes_transito'));
	}

	public function registrar_tinymce_plugin($plugin_array)
	{
		$plugin_array['busccortestransitocba_button'] = $this->cargar_url_asset('/js/shortcodeCortesTransito.js');
	    return $plugin_array;
	}

	public function agregar_boton_tinymce_shortcode_buscador_cortes_transito($buttons)
	{
	    $buttons[] = "busccortestransitocba_button";
	    return $buttons;
	}

	public function cargar_assets()
	{
		$urlJSBuscador = $this->cargar_url_asset('/js/buscadorCortesTransito.js');
		$urlCSSBuscador = $this->cargar_url_asset('/css/shortcodeCortesTransito.css');

		wp_register_style('buscador_transito_cba.css', $urlCSSBuscador);
		wp_register_script('buscador_transito_cba.js', $urlJSBuscador);
		
		global $post;
	    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buscador_cortes_transito_cba') ) {
			wp_enqueue_style('buscador_transito_cba.css', $urlCSSBuscador);
			
			wp_enqueue_script(
				'buscar_cortes_transito_ajax', 
				$urlJSBuscador, 
				array('jquery'), 
				'1.0.0',
				TRUE
			);
			wp_enqueue_style('buscador_cortes_transito.css', $this->cargar_url_asset('/css/shortcodeCortesTransito.css'));
			
			$nonce_busquedas = wp_create_nonce("buscar_cortes_transito_nonce");
			
			wp_localize_script(
				'buscar_cortes_transito_ajax', 
				'buscarCortesTransito', 
				array(
					'url'   => admin_url('admin-ajax.php'),
					'nonce' => $nonce_busquedas
				)
			);
		}
		
	}
	
	public function buscar_cortes_transito()
	{
		$nombre = $_REQUEST['nombre'];
		check_ajax_referer('buscar_cortes_transito_nonce', 'nonce');

		if(true && $nombre !== '') {
			$api_response = wp_remote_get(self::$URL_API_GOB.'?page_size=10&q='.$nombre);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			wp_send_json_success($this->renderizar_resultados($api_data, 10, $nombre));
		} elseif (true && $nombre == '') {
			$api_response = wp_remote_get(self::$URL_API_GOB.'?page_size=10');
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			wp_send_json_success($this->renderizar_resultados($api_data, 10,''));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}
	
	public function buscar_cortes_transito_pagina()
	{
		$pagina = $_REQUEST['pagina'];
		$nombre = $_REQUEST['nombre'];
		check_ajax_referer('buscar_cortes_transito_nonce', 'nonce');

		if(true && $pagina !== '') {
			$api_response = wp_remote_get($pagina);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			wp_send_json_success($this->renderizar_resultados($api_data, 10, $nombre));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}

	/*
	* Mira si la respuesta es un error, si no lo es, cachea por una hora el resultado.
	*/
	private function chequear_respuesta($api_response, $tipoObjeto)
	{
		if (is_null($api_response)) {
			return [ 'results' => [] ];
		} else if (is_wp_error($api_response)) {
			$mensaje = WP_DEBUG ? ' '.$this->mostrar_error($api_response) : '';
			return [ 'results' => [], 'error' => 'Ocurri&oacute; un error al cargar '.$tipoObjeto.'.'.$mensaje];
		} else {
			return json_decode(wp_remote_retrieve_body($api_response), true);
		}
	}


	/* Funciones de utilidad */

	private function mostrar_error($error)
	{
		if (WP_DEBUG === true) {
			return $error->get_error_message();
		}
	}

	private function formatear_fecha($original)
	{
		return date("d/m/Y H:m", strtotime($original));
	}
	
	private function restar_fechas($inicio, $fin)
	{
		$fecha1 = strtotime($inicio);
		$fecha2 = strtotime($fin);
		$resta_en_dias = intdiv(($fecha2 - $fecha1),86400);
		$en_horas = intdiv(($fecha2 - $fecha1),3600);
		$resultado = $resta_en_dias == 0 ? ($en_horas == 1 ? $en_horas.' hora' : $en_horas.' horas' ) : ($resta_en_dias == 1 ? $resta_en_dias.' d$iacute;a':$resta_en_dias.' d&iacute;as');
		return $resultado;
	}

	private function cargar_url_asset($ruta_archivo)
	{
		return plugins_url($this->minified($ruta_archivo), __FILE__);
	}

	// Se usan archivos minificados en producción.
	private function minified($ruta_archivo)
	{
		if (WP_DEBUG === true) {
			return $ruta_archivo;
		} else {
			$extension = strrchr($ruta_archivo, '.');
			return substr_replace($ruta_archivo, '.min'.$extension, strrpos($ruta_archivo, $extension), strlen($extension));
		}
	}
	
	private function obtener_parametro($url, $param, $fallback)
	{
		$partes = parse_url($url);
		parse_str($partes['query'], $query);
		$resultado = $query[$param] ? $query[$param] : $fallback;
		return $resultado;
	}
}