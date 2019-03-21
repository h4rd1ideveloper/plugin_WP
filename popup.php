<?php
/**
 * Plugin Name:       Popupvwp
 * Description:       Help you to build a beaulty HTMLelement!
 * Version:           1.0.0
 * Author:            Yan Santos policarpo
 * Author URI:        https://github.com/h4rd1ideveloper
 * Text Domain:       h4rd1ideveloper
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

/*
 * Plugin constants
 */
if (!defined('Popupvwp_URL') )
    define('Popupvwp_URL', plugin_dir_url(__FILE__) );
if (!defined('Popupvwp_PATH') )
    define('Popupvwp_PATH', plugin_dir_path(__FILE__) );

/*
 * Main class
 */
/**
 * Class Popupvwp
 *
 * This class creates the option page and add the web app script
 */
class Popupvwp
{
    /**
     * The option name
     *
     * @var string
     */
    private $option_name = 'Popupvwp_data';
    /**
     * Returns the saved options data as an array
     *
     * @return array
     */
   
    /**
     * Popupvwp constructor.
     *
     * The main plugin actions registered for WordPress
     */
    public function __construct()
    {
        // Admin page calls:
        add_action('admin_menu', array( $this, 'addAdminMenu') );
        add_action('wp_ajax_store_admin_data', array( $this, 'storeAdminData') );
        add_action('admin_enqueue_scripts', array( $this, 'addAdminScripts') );
        add_action('wp_footer', array( $this, 'addFooterCode'));
    }
    /**
     * Callback for the Ajax request
     *
     * Updates the options data
     *
     * @return void
     */
    public function storeAdminData()
    {
    
        if ( wp_verify_nonce($_POST['security'], $this->_nonce ) === false )
        die('Invalid Request!');
    
        $data = $this->getData();
        
        foreach ($_POST as $field=>$value) {
    
            if ( substr($field, 0, 8) !== "Popupvwp_" || empty($value) )
        continue;
    
            // We remove the Popupvwp_ prefix to clean things up
        $field = substr($field, 8);
    
            $data[$field] = $value;
    
        }
    
        update_option( $this->option_name, $data );
    
        echo __( 'Saved!', 'Popupvwp' );
        die();
    
    }

    /** 
     * The security nonce 
     *
     * @var string 
     */
    private $_nonce = 'Popupvwp_admin';
    /**
     * Adds Admin Scripts for the Ajax call
     */
    public function addAdminScripts()
    {
        wp_enqueue_script('Popupvwp-admin', Popupvwp_URL. '/assets/js/admin.js', array(), 1.0);
        $admin_options = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        '_nonce'   => wp_create_nonce( $this->_nonce ),
        );
        wp_localize_script('Popupvwp-admin', 'Popupvwp_exchanger', $admin_options);
    }
   
    /**
     * Make an API call to the Popupvwp API and returns the response
     *
     * @param string $private_key
     * @return array
     */
    private function getData() {
        return get_option( $this->option_name, array() );
    }

    private function getSurveys( $private_key )
    {
    
        $data = array();
        $response = wp_remote_get('https://api.Popupvwp.com/v1/carriers/?api_key='. $private_key );
    
        if ( is_array( $response ) && !is_wp_error( $response ) ) {
        $data = json_decode( $response['body'], true );
        }
    
        return $data;
    
    }
    /**
     * Adds the Popupvwp label to the WordPress Admin Sidebar Menu
     */
    public function addAdminMenu()
    {
        add_menu_page(
            __('Popupvwp', 'Popupvwp'),
            __('Popupvwp', 'Popupvwp'),
            'manage_options',
            'Popupvwp',
            array( $this, 'adminLayout'),
            ''
        );
    }
    /**
     * Add the web app code to the page's footer
     *
     * This contains the widget markup used by the web app and the widget API call on the frontend
     * We use the options saved from the admin page
     *
     * @return void
     */
    public function addFooterCode(){
    $data = $this->getData();
    // Only if the survey id is selected and saved
    if(empty($data) || !isset($data['widget_carrier_id']))
        return;
    ?>
    <div class="Popupvwp-widget"
                data-type="engager"
                data-position="<?php echo (isset($data['widget_position'])) ? $data['widget_position'] : 'left'; ?>"
                data-display-probability="<?php echo (isset($data['widget_display_probability'])) ? $data['widget_display_probability'] : '100'; ?>"
                data-shake="<?php echo (isset($data['widget_shake'])) ? $data['widget_shake'] : 'false'; ?>"
                data-carrier-id="<?php echo (isset($data['widget_carrier_id'])) ? $data['widget_carrier_id'] : '0'; ?>"
                data-key="<?php echo (isset($data['public_key'])) ? $data['public_key'] : '0'; ?>"></div>

    <script src="https://Popupvwp.com/js/widgets/widgets.min.js" type="text/javascript" async></script>
    <?php
    }
   
    /**
     * Outputs the Admin Dashboard layout containing the form with all its options
     *
     * @return void
     */
    public function adminLayout() {
        $data = $this->getData();
        $surveys = $this->getSurveys( $data['private_key']);
        ?>
        <div class="wrap">
            <h3><?php _e('Popupvwp API Settings', 'Popupvwp'); ?></h3>
            <p><?php _e('You can get your Popupvwp API settings from your <b>Integrations</b> page.', 'Popupvwp'); ?></p>
            <hr/>
            <form id="Popupvwp-admin-form">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td scope="row">
                                <label><?php _e('Public key', 'Popupvwp'); ?></label>
                            </td>
                            <td>
                                <input name="Popupvwp_public_key" id="Popupvwp_public_key" class="regular-text"
                                    value="<?php echo ( isset( $data['public_key']) ) ? $data['public_key'] : ''; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td scope="row">
                                <label><?php _e('Private key', 'Popupvwp'); ?></label>
                            </td>
                            <td>
                                <input name="Popupvwp_private_key" id="Popupvwp_private_key" class="regular-text"
                                    value="<?php echo ( isset( $data['private_key']) ) ? $data['private_key'] : ''; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <hr>
                                <h4><?php _e('Widget options', 'Popupvwp'); ?></h4>
                            </td>
                        </tr>
                        <?php if (!empty( $data['private_key']) && !empty( $data['public_key']) ) : ?>
                        <?php // if we don't even have a response from the API
                        if (empty( $surveys) ) : ?>
                        <tr>
                            <td>
                                <p class="notice notice-error">
                                    <?php _e('An error happened on the WordPress side. Make sure your server allows remote calls.', 'Popupvwp'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php // If we have an error returned by the API
                            elseif ( isset( $surveys['error']) ) : ?>
                        <tr>
                            <td>
                                <p class="notice notice-error">
                                    <?php echo $surveys['error']; ?>
                                </p>
                            </td>
                        </tr>
                        <?php // If the surveys were returned
                            else : ?>
                        <tr>
                            <td>
                                <p class="notice notice-success">
                                    <?php _e('The API connection is established!', 'Popupvwp'); ?>
                                </p>
                                <div>
                                    <label><?php _e('Choose a survey', 'Popupvwp'); ?></label>
                                </div>
                                <select name="Popupvwp_widget_carrier_id" id="Popupvwp_widget_carrier_id">
                                    <?php   // We loop through the surveys
                                        foreach ( $surveys['data'] as $survey ) : ?>
                                    <?php  // We also only keep the id -> x from the carrier_x returned by the API
                                        $survey['id'] = substr( $survey['id'], 8); ?>
                                        <option value="<?php echo $survey['id']; ?>"
                                            <?php echo ( $survey['id'] === $data['widget_carrier_id']) ? 'selected' : '' ?>>
                                            <?php echo $survey['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <hr/>
                        </tr>
                        <tr>
                            <td>
                                <div class="label-holder">
                                    <label><?php _e('Display probability (from 0 to 100)', 'Popupvwp'); ?></label>
                                </div>
                                <input name="Popupvwp_widget_display_probability" id="Popupvwp_widget_display_probability"
                                    class="regular-text"
                                    value="<?php echo ( isset( $data['widget_display_probability']) ) ? $data['widget_display_probability'] : '100'; ?>" />
                            </td>
                            <td>
                                <div class="label-holder">
                                    <label><?php _e('Shaking effect (shake after 10s without click)', 'Popupvwp'); ?></label>
                                </div>
                                <input name="Popupvwp_widget_shake" id="Popupvwp_widget_shake" type="checkbox"
                                    <?php echo ( isset( $data['widget_shake']) && $data['widget_shake']) ? 'checked' : ''; ?> />
                            </td>
                            <td>
                                <div class="label-holder">
                                    <label><?php _e('Position', 'Popupvwp'); ?></label>
                                </div>
                                <select name="Popupvwp_widget_position" id="Popupvwp_widget_position">
                                    <option value="left"
                                        <?php echo (!isset( $data['widget_position']) || ( isset( $data['widget_position']) && $data['widget_position'] === 'left') ) ? 'checked' : ''; ?>>
                                        <?php _e('Left side', 'Popupvwp'); ?>
                                    </option>
                                    <option value="right"
                                        <?php echo ( isset( $data['widget_position']) && $data['widget_position'] === 'right') ? 'checked' : ''; ?>>
                                        <?php _e('Right side', 'Popupvwp'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php else : ?>
                        <tr>
                            <td>
                                <p>Please fill up your API keys to see the widget options.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="2">
                                <button class="button button-primary" id="Popupvwp-admin-save"
                                    type="submit"><?php _e('Save', 'Popupvwp'); ?></button>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </form>
        </div>
    <?php }
}

/*
 * Starts our plugin class, easy!
 */
new Popupvwp();