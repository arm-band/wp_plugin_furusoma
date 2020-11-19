<?php

namespace Furusoma\app\src;

require_once( ABSPATH . '/wp-admin/includes/template.php' );

/**
 * 天狗ナメシ
 *
 * desc: 管理画面用ウォーカー
 */
class TenguNameshi extends \Walker_Category_Checklist
{
    /**
     * var
     */
    protected $c;
    protected $hidden_ids;
    /**
     * コンストラクタ
     */
    function __construct( $c, $arrayTaxonomies )
    {
        $this->c          = $c;
        $this->hidden_ids = $arrayTaxonomies['id'];
    }

    /**
     * Start the element output.
     *
     * @see Walker::start_el()
     *
     * @since 2.5.1
     *
     * @param string $output   Used to append additional content (passed by reference).
     * @param object $category The current term object.
     * @param int    $depth    Depth of the term in reference to parents. Default 0.
     * @param array  $args     An array of arguments. @see wp_terms_checklist()
     * @param int    $id       ID of the current term.
     */
    function start_el(
        &$output,
        $category,
        $depth = 0,
        $args = Array(),
        $id = 0
    )
    {
        extract( $args );
        if( empty( $taxonomy )) {
            $taxonomy = 'category';
        }

        if( $taxonomy == 'category' ) {
            $name = 'post_category';
        }
        else {
            $name = 'tax_input[' . $taxonomy . ']';
        }

        $class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';

        if( in_array( $category->term_id, $this->hidden_ids ) ) {
            // リストに自身が存在する場合は非表示にするためのクラスを付与
            $hiddenClass = ' ' . esc_attr( $this->c['FURUSOMA'] ) . '-lamberjack';
        }
        else {
            $hiddenClass = '';
        }
        $output .= "\n<li id=\"{$taxonomy}-{$category->term_id}\"{$class}><label class=\"selectit{$hiddenClass}\"><input value=\"" . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_attr( apply_filters('the_category', $category->name )) . '</label>';
    }
}
