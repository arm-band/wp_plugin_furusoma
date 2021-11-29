<?php
/*
Plugin Name: Furusoma
Description: 管理者画面(記事編集画面、投稿一覧ページでの簡易編集画面)で任意のカテゴリー、タグ、カスタムタクソノミーのチェックボックスを非表示にする簡易なプラグイン
Version:     0.2.2
Author:      アルム＝バンド
License: MIT
*/

namespace Furusoma;

date_default_timezone_set('Asia/Tokyo');
mb_language('ja');
mb_internal_encoding('UTF-8');

/**
 * 古杣
 *
 * desc: メイン処理
 */
class Furusoma
{
    /**
     * var
     */
    protected $c;
    protected $instance;
    protected $EsseEstPercipi;
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        try {
            if( !require_once(__DIR__ . '/app/EsseEstPercipi.php') ) {
                throw new \Exception( '初期化ファイル読み込みに失敗しました: EsseEstPercipi.php' );
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $this->EsseEstPercipi = new \Furusoma\app\EsseEstPercipi();
        $this->c = $this->EsseEstPercipi->getConstant();
        $this->instance = $this->EsseEstPercipi->getInstance();
    }
    /**
     * 管理者画面にメニューと設定画面を追加、プラグインの機能有効化(天狗ナメシ)
     */
    public function initialize()
    {
        // メニューを追加
        add_action( 'admin_menu', [ $this, 'furusoma_create_menu' ] );
        // 設定画面用のJSを読み込み
        add_action( 'admin_print_footer_scripts', [ $this, 'kigiribo' ], 100000 );
        // 独自関数をコールバック関数とする
        add_action( 'admin_init', [ $this, 'register_furusoma_settings' ] );

        // プラグインの機能有効化: 投稿一覧(edit.php), 投稿編集画面(post-new.php, post.php)
        // `wp_terms_checklist_args` のアクションフックで引数操作などが入るのでフックが発火する前に条件分岐させている
        if(
            $this->EsseEstPercipi->lumberjack('post.php') !== false
             || $this->EsseEstPercipi->lumberjack('post-new.php') !== false
             || $this->EsseEstPercipi->lumberjack('edit.php') !== false
        ) {
            // 全体にフックすると天狗ナメシが `wp_terms_checklist` コール時に使用されてしまうので、場所を限定してフックする
            add_action( 'wp_terms_checklist_args', [ $this, 'furusoma_tengu_nameshi' ] );
        }
        // プラグインの機能有効化: 投稿編集画面(post-new.php, post.php)
        add_action( 'admin_print_footer_scripts', [ $this, 'karagi_daoshi' ], 100000 );
        // プラグインの機能有効化: css
        add_action( 'admin_print_styles', [ $this, 'soraki_gaeshi' ] );
    }
    /**
     * メニュー追加
     */
    public function furusoma_create_menu()
    {
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        //  $page_title : 設定ページの `title` 部分
        //  $menu_title : メニュー名
        //  $capability : 権限 ( 'manage_options' や 'administrator' など)
        //  $menu_slug  : メニューのslug
        //  $function   : 設定ページの出力を行う関数
        //  $icon_url   : メニューに表示するアイコン
        //  $position   : メニューの位置 ( 1 や 99 など )
        add_menu_page(
            $this->c['FURUSOMA_SETTINGS'],
            $this->c['FURUSOMA_SETTINGS'],
            'administrator',
            $this->c['FURUSOMA'],
            [ $this, $this->c['FURUSOMA'] . '_settings_page' ],
            'dashicons-palmtree'
        );
    }
    /**
     * コールバック
     */
    public function register_furusoma_settings()
    {
        // register_setting( $option_group, $option_name, $sanitize_callback )
        //  $option_group      : 設定のグループ名
        //  $option_name       : 設定項目名(DBに保存する名前)
        //  $sanitize_callback : 入力値調整をする際に呼ばれる関数
        register_setting(
            $this->c['FURUSOMA_SETTINGS_EN'],
            $this->c['FURUSOMA_TAXONOMIES_CHECKBOXES'],
            [ $this, $this->c['FURUSOMA_CHECKBOXES_VALIDATION'] ]
        );
        register_setting(
            $this->c['FURUSOMA_SETTINGS_EN'],
            $this->c['FURUSOMA_JS_TIMEOUT_LIMIT'],
            [ $this, $this->c['FURUSOMA_TIMEOUT_VALIDATION'] ]
        );
    }
    /**
     * チェックボックスのバリデーション。コールバックから呼ばれる
     *
     * @param array $newInput 設定画面で入力されたパラメータ
     *
     * @return string $newInput / $ANONYMOUS バリデーションに成功した場合は $newInput そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     */
    public function furusoma_checkboxes_validation( $newInput )
    {
        // nonce check
        check_admin_referer( $this->c['FURUSOMA'] . '_options', 'name_of_nonce_field' );

        // validation
        $errCnt = 0;
        foreach( $newInput as $key => $value ) {
            if( preg_match('/^[\d]{1}$/i', $value) ) {
                $newInput[$key] = (int) $value;
                if ( $newInput[$key] !== 0 && $newInput[$key] !== 1 ) {
                    $errCnt++;
                }
            }
            else {
                $errCnt++;
            }
        }
        if( $errCnt > 0 ) {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                $this->c['FURUSOMA'],
                $this->c['FURUSOMA'] . '_checkboxes-validation_error',
                __(
                    '選択したタクソノミーの一覧に不正なデータが含まれています。',
                    $this->c['FURUSOMA']
                ),
                'error'
            );

            return get_option( $this->c['FURUSOMA'] . '_taxonomies_checkboxes' ) ? get_option( $this->c['FURUSOMA'] . '_taxonomies_checkboxes' ) : [];
        }
        else {
            return $newInput;
        }
    }
    /**
     * タイムリミットのバリデーション。コールバックから呼ばれる
     *
     * @param array $newInput 設定画面で入力されたパラメータ
     *
     * @return string $newInput / $ANONYMOUS バリデーションに成功した場合は $newInput そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     */
    public function furusoma_timeout_validation( $newInput )
    {
        // validation
        if( preg_match('/^[\d]+$/i', $newInput) && (int)$newInput >= 0 ) {
            return (int)$newInput;
        }
        else {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                $this->c['FURUSOMA'],
                $this->c['FURUSOMA'] . '_timeout-validation_error',
                __(
                    '設定しようとしたパラメータに不正なデータが含まれています。',
                    $this->c['FURUSOMA']
                ),
                'error'
            );

            return $this->EsseEstPercipi->thrilledSaw();
        }
    }
    /**
     * 設定画面ページの生成
     */
    public function furusoma_settings_page()
    {
        if( get_settings_errors( $this->c['FURUSOMA'] ) ) {
            // エラーがあった場合はエラーを表示
            settings_errors( $this->c['FURUSOMA'] );
        }
        else if( true == $_GET['settings-updated'] ) {
            //設定変更時にメッセージ表示
?>
            <div id="settings_updated" class="updated notice is-dismissible"><p><strong>設定を保存しました。</strong></p></div>
<?php
        }
?>

        <div class="wrap">
            <h1><?= esc_html( $this->c['FURUSOMA_SETTINGS'] ); ?></h1>
            <h2>非表示にする項目の設定</h2>
            <p>以下のチェックリストから、非表示にしたい項目にチェックを入れてください。</p>
            <p>※子孫タームを持つタームにチェックを入れると、そのターム自身とその子孫も両方とも一斉に非表示にします。</p>
            <form method="post" action="options.php">
<?php settings_fields( $this->c['FURUSOMA_SETTINGS_EN'] ); ?>
<?php do_settings_sections( $this->c['FURUSOMA_SETTINGS_EN'] ); ?>
                <table class="form-table" id="<?= esc_attr( $this->c['FURUSOMA_TAXONOMIES_CHECKBOXES'] ); ?>-table">
<?php
        $postTypes = get_post_types( [], 'objects' );
        foreach ( $postTypes as $postType ) {
            $termsObjs = get_object_taxonomies( $postType->name, 'objects' );
            foreach ( $termsObjs as $termsObj ) {
                if( is_taxonomy_hierarchical( $termsObj->name ) || $termsObj->name === 'category' ) {
?>
                    <tr>
                        <th><?= esc_html( $postType->label ); ?>: <?= esc_html( $termsObj->label ); ?></th>
                        <td>
                            <ul>
<?php
                    $tengudaoshi = $this->instance['TenguDaoshi'];
                    wp_terms_checklist( 0, [
                        'walker'        => $tengudaoshi,
                        'taxonomy'      => $termsObj->name,
                        'checked_ontop' => false,
                    ] );
?>
                            </ul>
                        </td>
                    </tr>
<?php
                }
            }
        }
?>
                </table>
                <h2>タイムアウト期限</h2>
                <p>記事編集画面のJavaScriptによる出力を制御する際に、最大何秒まで監視を続けるかの秒数をしていしてください(サーバ環境などによっては、大きな値が必要になるかもしれません)。</p>
                <table class="form-table" id="<?= esc_attr( $this->c['FURUSOMA_JS_TIMEOUT_LIMIT'] ); ?>-table">
                    <tr>
                        <th></th>
                        <td>
                        <input type="number" name="<?= esc_attr( $this->c['FURUSOMA_JS_TIMEOUT_LIMIT'] ); ?>" id="<?= esc_attr( $this->c['FURUSOMA_JS_TIMEOUT_LIMIT'] ); ?>" value="<?= esc_attr( $this->EsseEstPercipi->thrilledSaw() ); ?>" required="required">
                        </td>
                    </tr>
                </table>
<?php wp_nonce_field( $this->c['FURUSOMA'] . '_options', 'name_of_nonce_field' ); ?>
<?php submit_button( '設定を保存', 'primary large', 'submit', true, [ 'tabindex' => '1' ] ); ?>
            </form>
        </div>

<?php
    }
    /**
     * 天狗ナメシ発動(プラグインの機能有効化: 投稿一覧(edit.php), 投稿編集画面(post-new.php, post.php))
     *
     * @param array $args 引数
     * @param int $post_id 投稿ID
     */
    public function furusoma_tengu_nameshi( $args, $post_id = null )
    {
        $arrayTaxonomies = $this->EsseEstPercipi->attrSaw( $this->EsseEstPercipi->comeApart() );
        $args['checked_ontop'] = false; //チェックが付いているものを上に表示させる: オフ
        $args['walker'] = $this->instance['TenguNameshi'];

        return $args;
    }
    /**
     * 空木倒し発動(プラグインの機能有効化: css / 投稿編集画面(post-new.php, post.php), 投稿一覧(edit.php), トップレベルページ)
     */
    public function soraki_gaeshi()
    {
        global $hook_suffix;
        if(
            'post.php' === $hook_suffix
             || 'post-new.php' === $hook_suffix
             || 'edit.php' === $hook_suffix
             || 'toplevel_page_' . $this->c['FURUSOMA'] === $hook_suffix
        ) {
            wp_enqueue_style( 'sorakigaeshi', plugins_url( '', __FILE__ ) . '/css/sorakigaeshi.css' );
        }
    }
    /**
     * 空木倒し発動(プラグインの機能有効化: 投稿編集画面(post-new.php, post.php))
     */
    public function karagi_daoshi()
    {
        global $hook_suffix;
        if(
            'post.php' === $hook_suffix
             || 'post-new.php' === $hook_suffix
        ) {
            $arrayTaxonomies = $this->EsseEstPercipi->attrSaw( $this->EsseEstPercipi->comeApart() );
            $nameTaxsonomies = $this->EsseEstPercipi->chainSaw( $arrayTaxonomies['name'] );
?>
<script type="text/javascript" defer="defer">
    jQuery(function($) {
        $(window).on('load', function() {
            const MAX_RETRY_COUNT = <?= $this->EsseEstPercipi->_h( $this->EsseEstPercipi->thrilledSaw() ); ?>;
            let retry_counter = 0;

            // 天崩し
            const TenKuzushi = () => {
                const nameTaxsonomies = <?= $this->EsseEstPercipi->_h( $nameTaxsonomies ); ?>;
                //投稿画面
                $('.editor-post-taxonomies__hierarchical-terms-choice').each(function() {
                    if($.inArray($(this).children('.components-base-control').children('.components-base-control__field').children('.components-checkbox-control__label').text(), nameTaxsonomies) >= 0) {
                        $(this).addClass('<?= esc_html( $this->c['FURUSOMA'] ); ?>-lamberjack');
                    }
                });
            }

            // 天狗礫
            const TenguTsubute = () => {
                retry_counter++;
                //リトライ回数MAXに到達したら諦める
                if(retry_counter > MAX_RETRY_COUNT) {
                    clearInterval(setIntervalID);
                    delete setIntervalID;
                }
                if($('.editor-post-taxonomies__hierarchical-terms-list').length > 0) {
                    if(typeof(setIntervalID) != 'undefined') {
                        clearInterval(setIntervalID);
                        delete setIntervalID;
                        TenKuzushi();
                    }
                }
            }

            let setIntervalID = setInterval(TenguTsubute, 1000);

            //設定の歯車アイコン、「文書」「ブロック」タブの切替、タクソノミーのプルダウンをクリックした場合
            $('.edit-post-layout, .edit-post-sidebar, .components-button.components-panel__body-toggle').on('DOMSubtreeModified propertychange', function() {
                setIntervalID = setInterval(TenguTsubute, 1000);
            });
        });
    });
</script>
<?php
        }
    }
    /**
     * 木伐り坊発動(設定画面でチェックボックスにチェックを付けた時点で子・孫……の要素のチェックボックスもチェックを付ける)
     */
    public function kigiribo()
    {
        global $hook_suffix;
        if( 'toplevel_page_' . $this->c['FURUSOMA'] === $hook_suffix ) {
?>
<script type="text/javascript" defer="defer">
    jQuery(function($) {
        $(window).on('load', function() {
            $('#<?= esc_attr( $this->c['FURUSOMA_TAXONOMIES_CHECKBOXES'] ); ?>-table ul input:checkbox').on('click', function() {
                if ( $(this).is(':checked') ) {
                    $(this)
                        .closest('li')
                        .find('input:checkbox')
                        .prop('checked', true);
                } else {
                    if (
                        $(this)
                            .closest('li')
                            .closest('ul')
                            .prev('label')
                            .prev('input:checkbox')
                            .length
                             > 0
                         && $(this)
                            .closest('li')
                            .closest('ul')
                            .prev('label')
                            .prev('input:checkbox')
                            .is(':checked')
                    ) {
                        // チェックボックスの親要素が存在し、かつ、存在する親要素にチェックが入っている場合
                        if (
                            $(this)
                                .next('label')
                                .find('.<?= esc_attr( $this->c['FURUSOMA'] ); ?>_notice')
                                .length
                                 === 0
                            ) {
                            // notice が表示されていない場合、 notice を追加
                            $(this)
                                .next('label')
                                .html(
                                    $(this)
                                        .next('label')
                                        .text()
                                         + '<span class="<?= esc_attr( $this->c['FURUSOMA'] ); ?>_notice">チェックを解除できません (親要素にチェックが付いているため)</span>'
                                )
                        }
                        // チェックボックスのクリックイベントを防止
                        return false;
                    }
                    else {
                        // チェックボックスの親要素が存在しない、または存在するが親要素にチェックが入っていない場合
                        // チェックを外す
                        $(this)
                            .closest('li')
                            .find('input:checkbox')
                            .prop('checked', false);
                        // 子孫の notice 削除
                        $(this)
                            .closest('li')
                            .find('.<?= esc_attr( $this->c['FURUSOMA'] ); ?>_notice')
                            .remove();
                    }
                }
            });
        });
    });
</script>
<?php
        }
    }
}

// 処理
$wp_ab_furusoma = new Furusoma();

if( is_admin() ) {
    // 管理者画面を表示している場合のみ実行
    $wp_ab_furusoma->initialize();
}
