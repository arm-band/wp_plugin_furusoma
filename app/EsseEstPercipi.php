<?php

namespace Furusoma\app;

/**
 * 誰もいない森で倒れた木は音を立てるか
 *
 * desc: 初期化・準備
 */
class EsseEstPercipi
{
    /**
     * const
     */
    const FURUSOMA                       = 'furusoma';
    const FURUSOMA_SETTINGS              = '古杣 設定';
    const FURUSOMA_SETTINGS_EN           = self::FURUSOMA . '-settings';
    const FURUSOMA_TAXONOMIES_CHECKBOXES = self::FURUSOMA . '_taxonomies_checkboxes';
    const FURUSOMA_JS_TIMEOUT_LIMIT      = self::FURUSOMA . '_js_timeout_limit';
    const FURUSOMA_CHECKBOXES_VALIDATION = self::FURUSOMA . '_checkboxes_validation';
    const FURUSOMA_TIMEOUT_VALIDATION    = self::FURUSOMA . '_timeout_validation';
    const ENCODING                       = 'UTF-8';
    /**
     * var
     */
    protected $c;
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->c = [
            'FURUSOMA'                       => self::FURUSOMA,
            'FURUSOMA_SETTINGS'              => self::FURUSOMA_SETTINGS,
            'FURUSOMA_SETTINGS_EN'           => self::FURUSOMA_SETTINGS_EN,
            'FURUSOMA_TAXONOMIES_CHECKBOXES' => self::FURUSOMA_TAXONOMIES_CHECKBOXES,
            'FURUSOMA_JS_TIMEOUT_LIMIT'      => self::FURUSOMA_JS_TIMEOUT_LIMIT,
            'FURUSOMA_CHECKBOXES_VALIDATION' => self::FURUSOMA_CHECKBOXES_VALIDATION,
            'FURUSOMA_TIMEOUT_VALIDATION'    => self::FURUSOMA_TIMEOUT_VALIDATION,
            'ENCODING'                       => self::ENCODING,
        ];
    }
    /**
     * 定数返し
     *
     * @return array $c クラス内で宣言した定数を出力する
     */
    public function getConstant()
    {
        return $this->c;
    }
    /**
     * htmlspecialchars のラッパー関数
     *
     * esc_html ではクォートもエスケープされてしまうため、JS処理時は不都合がある
     *
     * @param string $str 文字列
     *
     * @return string $ANONYMOUS $str を エスケープして返す(クォートを除く)
     */
    public function _h( $str )
    {
        return htmlspecialchars( $str, ENT_NOQUOTES, ENCODING );
    }
    /**
     * チェーンソー: JSONエンコード
     *
     * @param array $data 配列データ
     *
     * @return string $ANONYMOUS $data を JSON文字列 にして返す
     */
    public function chainSaw( $data )
    {
        return json_encode( $data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
    }
    /**
     * かみは　バラバラに　なった: データ読み込み
     *
     * @return array $ANONYMOUS DB から
     */
    public function comeApart()
    {
        return maybe_unserialize( get_option( self::FURUSOMA_TAXONOMIES_CHECKBOXES ) );
    }
    /**
     * ノコギリ: データ整形
     *
     * @param array $dataArray データ配列
     *
     * @return array $formatedArray 整形されたデータ配列
     */
    public function attrSaw( $dataArray )
    {
        $formatedArray = [
            'id'   => [],
            'name' => [],
        ];
        foreach( $dataArray as $key => $val ) {
            $term_id = explode( '---', $key )[1];
            $taxonomy = explode( '___', $key )[0] === 'post_category' ? 'category' : explode( '___', $key )[0];
            $term = get_term( $term_id, $taxonomy );
            $formatedArray['id'][] = $term_id;
            $formatedArray['name'][] = $term->name;
        }

        return $formatedArray;
    }
    /**
     * ハラハラのこぎり: タイムリミット時間を返す
     *
     * @return array $ANONYMOUS DBに保存されたタイムリミット時間、または初期値 10
     */
    public function thrilledSaw()
    {
        return get_option( self::FURUSOMA_JS_TIMEOUT_LIMIT ) ? get_option( self::FURUSOMA_JS_TIMEOUT_LIMIT ) : 10;
    }
    /**
     * きこり: URLに指定文字列が含まれるかどうか判定する
     *
     * @param string $needle 判定したい文字列
     *
     * @return bool $ANONYMOUS 指定文字列が含まれるかどうか判定した結果
     */
    public function lumberjack( $needle )
    {
        return mb_strpos( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), $needle, 0, self::ENCODING );
    }
    /**
     * インスタンス返し
     *
     * @return array $instance コンストラクタで宣言した文字列の名前のファイルを探し、require_once して new してインスタンスを返す
     */
    public function getInstance()
    {
        $instance = [];
        try {
            $c = self::getConstant();
            $taxonomiesArray = self::comeApart();
            if( require_once( __DIR__ . '/src/TenguDaoshi.php' ) ) {
                $instance['TenguDaoshi'] = new \Furusoma\app\src\TenguDaoshi( $c, $taxonomiesArray );
            }
            else {
                throw new \Exception( 'クラスファイル読み込みに失敗しました: TenguDaoshi.php' );
            }
            if( require_once( __DIR__ . '/src/TenguNameshi.php' ) ) {
                $arrayTaxonomies = self::attrSaw( $taxonomiesArray );
                $instance['TenguNameshi'] = new \Furusoma\app\src\TenguNameshi( $c, $arrayTaxonomies );
            }
            else {
                throw new \Exception( 'クラスファイル読み込みに失敗しました: TenguNameshi.php' );
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $instance;
    }
}
