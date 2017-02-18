<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://tech.cbjck.de/wp/rpr
 * @since      0.8.0
 *
 * @package    recipepress-reloaded
 * @subpackage recipepress-reloaded/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.8.0
 * @package    recipepress-reloaded
 * @subpackage recipepress-reloaded/includes
 * @author     Jan Köster <rpr@cbjck.de>
 */
class RPR {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    0.8.0
     * @access   protected
     * @var      RPR_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    0.8.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    0.8.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The current database version of the plugin.
     *
     * @since    0.8.0
     * @access   protected
     * @var      string    $version    The current version of the database of the plugin.
     */
    protected $dbversion;

    /**
     * A list of all activated modules
     * @since: 1.0.0
     * @todo: The list should be generated in a procedure during contruction from the options set.
     * For testing the list is defined fixed here
     */
    protected $modules = array ();
    
    /**
     * Instance of the admin class
     * @var RPR_Admin 
     * @since 1.0.0
     */
    protected $admin;
    
    /**
     * Instance of the public class
     * @var RPR_Public
     * @since 1.0.0
     */
    protected $public;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the modules, load the dependencies, define the locale
     * and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    0.8.0
     */
    public function __construct() {

        $this->plugin_name = 'recipepress-reloaded';
        $this->version = RPR_VERSION;
        $this->dbversion = RPR_DBVER;

        $this->register_posttype();
        $this->load_modules();
        $this->load_dependencies();
        
        $this->admin = new RPR_Admin( $this->version, $this->dbversion );
        $this->public = new RPR_Public( $this->version );
        
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Register the post type for all the recipes
     * 
     * @since 1.0.0
     */
    private function register_posttype(){
        /**
         * The class defining the custom post type
         * It needs to be instantiated here, as AFP is using its own loader and hooks
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-rpr-recipeposttype.php';
        new RPR_RecipePostType( $this->plugin_name, $this->version );
    }
    /**
     * Load all enabled modules and instantiate objects
     * 
     * @since 1.0.0
     * @todo Generate the list of modules from options
     */
    private function load_modules() {
        /**
         * Load the abstract class for RPR_Modules
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/abstract-class-rpr-module.php';

        /**
         * For development we use a static list of modules.
         * Needs to be generated from optiosn later!
         */
        $active_modules = array ( 'Demo' );

        foreach ( $active_modules as $active_module ) {
            /**
             * Load the module file
             */
            $filename = plugin_dir_path( dirname( __FILE__ ) ) . 'modules/' . strtolower( $active_module ) . '/class-rpr-module-' . strtolower( $active_module ) . '.php';

            if ( file_exists( $filename ) ) {
                require_once $filename;

                /**
                 * Create the module object and store it in $this->modules
                 */
                $classname = 'RPR_Module_' . $active_module;
                $this->modules[ $active_module ] = new $classname();
            }
        }
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - RPR_Loader. Orchestrates the hooks of the plugin.
     * - RPR_i18n. Defines internationalization functionality.
     * - RPR_Admin. Defines all hooks for the admin area.
     * - RPR_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    0.8.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rpr-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rpr-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-rpr-admin.php';


        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-rpr-public.php';
        

        /**
         * The admin page framework to create options pages and metaboxes
         * 
         * @link http://www.admin-page-framework.michaeluno.jp/
         * @since 0.8.0
         * 
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'libraries/apf/admin-page-framework.php';

        /**
         * Load dependencies for all modules:
         */
        foreach ( $this->modules as $module ) {
            if ( is_a( $module, 'RPR_Module' ) ) {
                $module->load_dependencies();
            }
        }

        $this->loader = new RPR_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the RPR_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    0.8.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new RPR_i18n( $this->plugin_name, $this->version );

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    0.8.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new RPR_Admin( $this->version, $this->dbversion );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Migration from older versions
        $this->loader->add_action( 'admin_init', $plugin_admin->migration, 'check_migration' );
        $this->loader->add_action( 'admin_init', $plugin_admin->migration, 'rpr_do_migration' );
        $this->loader->add_action( 'admin_notices', $plugin_admin->migration, 'notice_migration' );

        // Install demo data / sample recipes
        $this->loader->add_action( 'admin_init', $plugin_admin->demo, 'do_install_base_options' );
        //$this->loader->add_action( 'admin_init', $plugin_admin->demo, 'rpr_do_install_samples' );
        $this->loader->add_action( 'admin_notices', $plugin_admin->demo, 'notice_demo' );


        // Options page
        $this->loader->add_action( 'init', $plugin_admin, 'create_options' );
        // Meta boxes:
        $this->loader->add_action( 'do_meta_boxes', $plugin_admin->generalmeta, 'metabox_postimage' );
        $this->loader->add_action( 'do_meta_boxes', $plugin_admin->generalmeta, 'metabox_description' );
        $this->loader->add_action( 'do_meta_boxes', $plugin_admin->generalmeta, 'metabox_details' );

        if ( AdminPageFramework::getOption( 'rpr_options', array ( 'metadata', 'use_nutritional_data' ), false ) ) {
            $this->loader->add_action( 'do_meta_boxes', $plugin_admin->nutrition, 'metabox_nutrition' );
        }

        $this->loader->add_action( 'do_meta_boxes', $plugin_admin->ingredients, 'metabox_ingredients' );
        $this->loader->add_action( 'do_meta_boxes', $plugin_admin->instructions, 'metabox_instructions' );
        $this->loader->add_action( 'do_meta_boxes', $plugin_admin->generalmeta, 'metabox_notes' );

        // Save recipe
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_recipe', 10, 2 );

        // Display error messages
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notice_handler' );

        // Shortcodes to embed recipes into post types
        // Shortcode for recipe
        $this->loader->add_action( 'media_buttons', $plugin_admin->shortcodes, 'add_button_scr' );
        $this->loader->add_action( 'in_admin_footer', $plugin_admin->shortcodes, 'load_in_admin_footer_scr' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin->shortcodes, 'load_ajax_scripts_scr' );
        $this->loader->add_action( 'wp_ajax_rpr_get_results', $plugin_admin->shortcodes, 'process_ajax_scr' );

        // Shortcode for listings
        $this->loader->add_action( 'media_buttons', $plugin_admin->shortcodes, 'add_button_scl' );
        $this->loader->add_action( 'in_admin_footer', $plugin_admin->shortcodes, 'load_in_admin_footer_scl' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin->shortcodes, 'load_ajax_scripts_scl' );

        /**
         * Define the admin hooks for all modules
         */
        foreach ( $this->modules as $module ) {
            if ( is_a( $module, 'RPR_Module' ) ) {
                $module->define_admin_hooks( $this->loader );
            }
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    0.8.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new RPR_Public( $this->plugin_name, $this->version );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Manipulate the query to include recipes to home page (if set)
        $this->loader->add_action( 'pre_get_posts', $plugin_public, 'query_recipes' );
        // Add recipes to main rss
        $this->loader->add_filter( 'request', $plugin_public, 'add_recipes_to_feed' );

        // Do the recipe specific content and layout operations
        $this->loader->add_filter( 'the_excerpt', $plugin_public, 'get_recipe_excerpt' );
        $this->loader->add_filter( 'the_content', $plugin_public, 'get_recipe_content' );

        // Do the recipe shortcodes
        add_shortcode( 'rpr-recipe', array ( $plugin_public, 'do_recipe_shortcode' ) );
        add_shortcode( "rpr-recipe-index", array ( $plugin_public, 'do_recipe_index_shortcode' ) );
        add_shortcode( "rpr-tax-list", array ( $plugin_public, 'do_taxlist_shortcode' ) );

        // register the widgets
        $this->loader->add_action( 'widgets_init', $plugin_public, 'register_widgets' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    0.8.0
     */
    public function run() {
        $this->loader->run();
    }
}
