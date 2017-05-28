<?php
/**
 * Plugin Name:     Category Menus
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     category-menus
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Category_Menus
 */


/**
 * If a menu item is a category, and the menu is at the top level, 
 * and it's a top-level category, then append all the 
 * child categories as a submenu.
 *
 * https://codex.wordpress.org/Class_Reference/Walker
 */
class JK_CategoryMenuPlugin {
    function menuArgFilter($args) {
        $args['walker'] = new JK_Walker_Nav_Menu();
        return $args;
    }
    function init() {
        add_filter('wp_nav_menu_args', array('JK_CategoryMenuPlugin', 'menuArgFilter'));
        // add_filter('walker_nav_menu_start_el', array('JK_CategoryMenuPlugin', 'menuArgFilter'));
    }
}
JK_CategoryMenuPlugin::init();

class JK_Walker_Nav_Menu extends Walker_Nav_Menu {
    var $out = '';
    function __construct() {
    }

    /**
     * We override start_el to detect menu items that are categories.
     * When we find a category, we then emit a menu of the child
     * categories.
     */
    public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        if ($item->object=='category') {
            $this->emit_category_hierarchy($output, $item->object_id, $item, $depth, $args, $id);
        } else {
            parent::start_el( $output, $item, $depth, $args, $id );
        }
    }

    /**
     * We need to use our own recursive Visitor to drill down the
     * category hierarchy.  We use the entire argument list for start_el
     * because we call end_el() from within this method.
     */
    private function emit_category_hierarchy(&$output, $term_id, $item, $depth, $args, $id) {
        $children = $this->get_term_children($term_id, 'category');
        if (count($children)>0) {
            $this->start_category_el( $output, $term_id, true );
            $this->start_lvl( $output, $depth, $args );
            foreach($children as $child) {
                $this->emit_category_hierarchy( $output, $child, $item, $depth, $args, $id );
            }
            $this->end_lvl( $output, $depth, $args );
            $this->end_el( $output, $item, $depth, $args );
        } else {
            $this->start_category_el( $output, $term_id, false );
            $this->end_el( $output, $item, $depth, $args );
        }
    }

    /**
     * Similar to start_el, except that we don't want this called by
     * the walker.  It's called only by $this->emit_category_hierarchy().
     */
    private function start_category_el( &$output, $term_id, $has_children ) {
        $term = get_term($term_id);
        $name = $term->name;
        $link = get_category_link($term_id);

        $classes = array('menu-item');
        $id = 'menu-item-'.$term_id;
        $classes[] = $id;
        if ($has_children) {
            $classes[] = 'menu-item-has-children';
        }

        $output .= '<li id="'.$id.'" class="'.join(' ', $classes).'"><a href="'.$link.'">'.$name.'</a>';
    }

    /**
     * Similar to get_term_children(), except it doesn't
     * merge all the child terms into a flat array. It returns terms one level
     * below the current term.
     */
    private function get_term_children($term_id, $taxonomy) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
        }

        $term_id = intval( $term_id );

        $terms = _get_term_hierarchy($taxonomy);

        if ( ! isset($terms[$term_id]) )
            return array();

        return $terms[$term_id];
    }
}
