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
    // Obtener el ID de la categoría actual desde la URL
    $current_category_slug = get_query_var('product_cat');
    
    // Si no hay una categoría seleccionada, no hacer nada
    if (empty($current_category_slug)) {
        return '<p>No se ha seleccionado una categoría.</p>';
    }

    // Obtener el objeto de la categoría actual
    $current_category = get_term_by('slug', $current_category_slug, 'product_cat');
    
    // Si no existe la categoría, retornar un mensaje
    if (!$current_category || is_wp_error($current_category)) {
        return '<p>La categoría seleccionada no existe.</p>';
    }

    // Obtener todos los productos de la categoría, sin considerar la paginación
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1, // Obtener todos los productos
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $current_category->term_id,
                'include_children' => true, // Incluir subcategorías
            ),
        ),
    );

    $products_query = new WP_Query($args);

    // Obtener los IDs de los productos
    $product_ids = wp_list_pluck($products_query->posts, 'ID');

    // Si no hay productos, retornar un mensaje
    if (empty($product_ids)) {
        return '<p>No hay productos para filtrar.</p>';
    }

    // Obtener las categorías asociadas a estos productos
    $categories = wp_get_object_terms($product_ids, 'product_cat', ['orderby' => 'name', 'order' => 'ASC', 'fields' => 'all']);

    // Si no hay categorías asociadas, retornar un mensaje
    if (is_wp_error($categories) || empty($categories)) {
        return '<p>No hay categorías relacionadas.</p>';
    }

    // Organizar las categorías por su jerarquía
    $parent_categories = [];
    $child_categories = [];

    foreach ($categories as $category) {
        if ($category->parent == 0) {
            $parent_categories[] = $category;
        } else {
            $child_categories[$category->parent][] = $category;
        }
    }

    ob_start();
    ?>
    <form id="wcapf-category-filter" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <select name="product_cat" onchange="if(this.value) this.form.submit();">
            <option value="">Selecciona una categoría</option>
            
            <?php foreach ($parent_categories as $parent): ?>
                <optgroup label="<?php echo esc_html($parent->name); ?>">
                    <?php if (isset($child_categories[$parent->term_id])): ?>
                        <?php foreach ($child_categories[$parent->term_id] as $child): ?>
                            <option value="<?php echo esc_attr($child->slug); ?>" <?php selected(get_query_var('product_cat'), $child->slug); ?>>
                                <?php echo esc_html($child->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </optgroup>
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
