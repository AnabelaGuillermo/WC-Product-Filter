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

    // Crear un arreglo para organizar las categorías por su jerarquía
    $parent_categories = [];
    $child_categories = [];

    foreach ($categories as $category) {
        if ($category->parent == 0) {
            // Categoría padre
            $parent_categories[] = $category;
        } else {
            // Subcategoría
            $child_categories[$category->parent][] = $category;
        }
    }

    ob_start();
    ?>
    <form id="wcapf-category-filter" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <select name="product_cat" onchange="this.form.submit()">
            <option value="">Selecciona una categoría</option>
            
            <?php foreach ($parent_categories as $parent): ?>
                <option value="" disabled style="font-weight: bold;"><?php echo esc_html($parent->name); ?></option>
                
                <?php if (isset($child_categories[$parent->term_id])): ?>
                    <?php foreach ($child_categories[$parent->term_id] as $child): ?>
                        <option value="<?php echo esc_attr($child->slug); ?>" <?php selected(get_query_var('product_cat'), $child->slug); ?>>
                            <?php echo esc_html($child->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
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
