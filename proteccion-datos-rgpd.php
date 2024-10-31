<?php
/**
 * Plugin Name: Protección de datos - RGPD
 * Plugin URI:  https://taller.abcdatos.net/plugin-rgpd-wordpress/
 * Description: Arrange your site to GDPR (General Data Protection Regulation) and LSSICE as well as other required tasks based on required configurations ettings.
 * Version:     0.67
 * Author:      ABCdatos
 * Author URI:  https://taller.abcdatos.net/
 * License:     GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: proteccion-datos-rgpd
 * Domain Path: /languages
 *
 * @package proteccion-datos-rgpd
 */

defined( 'ABSPATH' ) || die( 'No se permite el acceso.' );

// i18n.
/** Requerido o se obtiene error Plugin is not compatible with language packs: Missing load_plugin_textdomain(). en el canal de Slack #meta-language-packs.
 *
 * O usamos este hook o el requisito mínimo es WP 4.6.
 */
function pdrgpd_load_plugin_textdomain() {
	load_plugin_textdomain( 'proteccion-datos-rgpd', false, basename( __DIR__ ) . '/languages' );
}
add_action( 'plugins_loaded', 'pdrgpd_load_plugin_textdomain' );

// Administration features (settings).
if ( is_admin() ) {
	include_once 'admin/options.php';
}

// Legal Advice related code loading.
require_once plugin_dir_path( __FILE__ ) . 'aviso-legal.php';

// Privacy Policy related code loading.
require_once plugin_dir_path( __FILE__ ) . 'politica-privacidad.php';

// Primera capa del deber de información y casilla de aceptación en formularios.
require_once plugin_dir_path( __FILE__ ) . 'formularios.php';

// Cookie Policy related code loading.
require_once plugin_dir_path( __FILE__ ) . 'politica-cookies.php';

// Cookies loading related code.
require_once plugin_dir_path( __FILE__ ) . 'insercion-cookies.php';

// Notas a pie de página.
require_once plugin_dir_path( __FILE__ ) . 'pie.php';

// Lista de variables usadas en tabla options.
require_once plugin_dir_path( __FILE__ ) . 'lista-opciones.php';

// Si está disponible Jetpack y en las opciones se indicó que se utiliza, carga el código para el shortcode de remplazo del formulario de suscripción.
if ( class_exists( 'Jetpack' ) ) {
	if ( pdrgpd_existe_suscripcion_jetpack() ) {
		include_once plugin_dir_path( __FILE__ ) . 'jetpack-suscripcion.php';
	}
}

/** Añade un enlace de configuración en la página de administración de plugins.
 *
 * Basado en https://www.smashingmagazine.com/2011/03/ten-things-every-wordpress-plugin-developer-should-know/
 *
 * @param array  $links Lista existente de enlaces.
 * @param string $file  Nombre del archivo del plugin.
 * @return array        Lista de enlaces con el enlace de configuración añadido.
 */
function pdrgpd_plugin_action_links( $links, $file ) {
	static $this_plugin;
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}
	if ( $file === $this_plugin ) {
		// The "page" query string value must be equal to the slug of the Settings admin page.
		$settings_link = '<a href="' . admin_url( 'admin.php?page=proteccion-datos-rgpd' ) . '">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'pdrgpd_plugin_action_links', 10, 2 );

/** Obtiene la versión del plugin para el encabezado de la página de opciones.
 *
 * @return string La versión del plugin.
 */
function pdrgpd_get_version() {
	$plugin_data    = get_plugin_data( __FILE__ );
	$plugin_version = $plugin_data['Version'];
	return $plugin_version;
}

/** Common functions. */

/** Determina si un código es NIF, CIF o NIE por su sintaxis.
 *
 * @param string $codigo El código a comprobar.
 * @return string        El tipo de código ('DNI', 'CIF', 'NIE').
 */
function pdrgpd_nif_o_cif( $codigo ) {
	// Determina si es NIF, CIF o NIE por la sintaxis.
	$tipo = 'DNI';
	if ( preg_match( '/^[a-z]/i', $codigo ) ) {
		$tipo = 'CIF';
		if ( preg_match( '/^(x|y|z)/i', $codigo ) ) {
			$tipo = 'NIE';
		}
	}
	return $tipo;
}

/** Obtiene el nombre del tema padre actual. Si el tema actual no es un child theme,
 * se devuelve el nombre del tema actual.
 *
 * @return string El nombre del tema padre si el tema actual es un child theme, o el nombre del tema actual si no es un child theme.
 */
function tema_padre() {
	$tema_actual = wp_get_theme();

	if ( $tema_actual->parent() ) {
		// Si el tema actual es un child theme, obtenemos el nombre del tema padre.
		$tema_padre = $tema_actual->parent()->get( 'Name' );
	} else {
		// Si el tema actual no es un child theme, obtenemos el nombre del tema actual.
		$tema_padre = $tema_actual->get( 'Name' );
	}

	return $tema_padre;
}

/** Genera un enlace HTML que abre en una nueva ventana.
 *
 * @param string $url La URL a la que se debe enlazar.
 * @param string $anchor El texto que se mostrará para el enlace.
 * @return string El enlace HTML generado.
 */
function pdrgpd_enlace_nueva_ventana( $url, $anchor ) {
	$html = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . esc_attr( $anchor ) . '</a>';
	return $html;
}

/** Retira el punto final de una cadena si lo tiene.
 *
 * @param string $texto La cadena de la cual se quiere retirar el punto final.
 * @return string La cadena sin el punto final.
 */
function pdrgpd_retira_punto_final( $texto ) {
	$texto = rtrim( $texto, '.' );
	return $texto;
}

/** Asegura que una cadena termine con un punto final.
 *
 * @param string $texto La cadena a la que se quiere agregar el punto final.
 * @return string La cadena con un punto final.
 */
function pdrgpd_agrega_punto_final( $texto ) {
	$texto  = pdrgpd_retira_punto_final( $texto );
	$texto .= '.';
	return $texto;
}

/** Pone punto final si no estaba y agrega un espacio.
 *
 * @param string $texto Texto al que se le quiere agregar el punto y el espacio final.
 * @return string Texto con el punto y el espacio final agregados.
 */
function pdrgpd_finaliza_frase( $texto ) {
	$texto  = pdrgpd_agrega_punto_final( $texto );
	$texto .= ' ';
	return $texto;
}

/** Verifica si el módulo Jetpack para comentarios está activo.
 *
 * @return bool Verdadero si el módulo de comentarios de Jetpack está activo, falso en caso contrario.
 */
function pdrgpd_modulo_jetpack_comentarios_activo() {
	return pdrgpd_modulo_jetpack_activo( 'comments' );
}

/** Verifica si el módulo Jetpack para suscripciones está activo.
 *
 * @return bool Verdadero si el módulo de suscripciones de Jetpack está activo, falso en caso contrario.
 */
function pdrgpd_modulo_jetpack_suscripciones_activo() {
	return pdrgpd_modulo_jetpack_activo( 'subscriptions' );
}

/** Verifica si un módulo específico de Jetpack está activo.
 *
 * @param string $modulo El nombre del módulo Jetpack que se quiere verificar.
 * @return bool Verdadero si el módulo especificado de Jetpack está activo, falso en caso contrario.
 */
function pdrgpd_modulo_jetpack_activo( $modulo ) {
	$activo = false;
	if ( class_exists( 'Jetpack' ) ) {
		$modulos_jetpack = get_option( 'jetpack_active_modules' );
		$activo          = in_array( $modulo, $modulos_jetpack, true );
	}
	return $activo;
}

/** Devuelve el idioma actual sin localización.
 *
 * @return string Código del idioma actual sin localización.
 */
function pdrgpd_idioma() {
	$locale       = get_locale();
	$locale_parts = explode( '_', $locale );
	$idioma       = $locale_parts[0];
	return $idioma;
}
