<?php
/*
Plugin Name: WC Category Product Filter
Description: Filtra productos de WooCommerce por categorías dinámicas.
Version: 1.0
Author: Anabela Guillermo
*/

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Registrar el shortcode para el filtro
function wcapf_register_shortcode() {
    add_shortcode('wc_category_filter', 'wcapf_render_dynamic_category_filter');
}
add_action('init', 'wcapf_register_shortcode');

// Renderizar el filtro de categorías dinámico
function wcapf_render_dynamic_category_filter() {
    // Obtener términos relacionados con los productos visibles
    global $wp_query;

    // Recuperar los IDs de los productos en la página actual
    $product_ids = wp_list_pluck($wp_query->posts, 'ID');

    // Si no hay productos visibles, retornar un mensaje
    if (empty($product_ids)) {
        return '<p>No hay productos para filtrar.</p>';
    }

    // Obtener las categorías asociadas a estos productos
    $categories = wp_get_object_terms($product_ids, 'product_cat', ['orderby' => 'name', 'order' => 'ASC', 'fields' => 'all']);

    if (is_wp_error($categories) || empty($categories)) {
        return '<p>No hay categorías relacionadas.</p>';
    }

    ob_start();
    ?>
    <form id="wcapf-category-filter" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <select name="product_cat" onchange="this.form.submit()">
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected(get_query_var('product_cat'), $category->slug); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php
    return ob_get_clean();
}

function wcapf_enqueue_styles() {
    wp_enqueue_style('wcapf-style', plugins_url('css/style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'wcapf_enqueue_styles');
