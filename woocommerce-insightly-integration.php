<?php
/*
Plugin Name: WooCommerce Insightly Integration
Plugin URI:
Description:
Version: 1.0.0
Author: Bryan Headrick
Author URI: https://catmanstudios.com
 License: GNU General Public License v3.0
 License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require __DIR__ . '/vendor/autoload.php';
require_once("admin-page-class/admin-page-class.php");


class WooCommerceInsightlyIntegration {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'WooCommerce Insightly Integration';
	const slug = 'woocommerce-insightly-integration';
	private $insightly_options;
	private $insightly;
	private $opportunity_format;
	private $mailbox_format;
	private $cat_options;
	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( $this, 'install_woocommerce_insightly_integration' ) );

		//Hook up to the init action
		add_action( 'init', array( $this, 'init_woocommerce_insightly_integration' ) );
	}

	/**
	 * Runs when the plugin is activated
	 */
	function install_woocommerce_insightly_integration() {
		// do not generate any output here
	}

	/**
	 * Runs when the plugin is initialized
	 */
	function init_woocommerce_insightly_integration() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

		// Register the shortcode [my_shortcode]


		if ( is_admin() ) {

			$config = array(
				'menu'           => 'woocommerce',             //sub page to settings page
				'page_title'     => __('Insightly','apc'),       //The name of this page
				'capability'     => 'edit_themes',         // The capability needed to view the page
				'option_group'   => self::slug,       //the name of the option to create in the database
				'id'             => 'admin_page',            // meta box id, unique per page
				'fields'         => array(),            // list of fields (can be added by field arrays)
				'local_images'   => false,          // Use local or hosted images (meta box images for add/remove)
				'use_with_theme' => false          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
			);

			/**
			 * instantiate your admin page
			 */
			$options_panel = new BF_Admin_Page_Class($config);
			$options_panel->OpenTabs_container('');

			/**
			 * define your admin page tabs listing
			 */
			$options_panel->TabsListing(array(
				'links' => array(
					'options_1' =>  __('Authentication','apc'),
					'options_2' =>  __('Categories','apc'),

				)
			));

			/**
			 * Open admin page first tab
			 */
			$options_panel->OpenTab('options_1');



			$options_panel->Title(__("Main Options","apc"));
			//An optionl descrption paragraph
			//$options_panel->addParagraph(__("This is a simple paragraph","apc"));
			//text field

			$options = 'order_number,billing_first_name,billing_last_name,billing_email,billing_country,billing_city,billing_state,billing_postcode,payment_method_title,insightly_category';
			$options_list = '<ul>';
			foreach(explode(',', $options) as $option){
				$options_list .= '<li>%'. $option . '%</li>';
			}
			$options_list .='</ul>';

			$options_panel->addText('api_key', array('name'=> __('Insightly API Key ','apc'),  'desc' => __('Insightly API Key','apc')));
			$options_panel->addText('opp_format', array('name'=> __('Opportunity Name Format ','apc'),  'desc' => __('','apc')));
			$options_panel->addText('mailbox_format', array('name'=> __('Insightly Mailbox Email Format ','apc'),  'desc' => __('','apc')));
			$options_panel->addParagraph('<h3>Format field options</h3> <p>These correspond to the post_meta keys.</p>'. $options_list);


			$options_panel->CloseTab();

			$options_panel->OpenTab('options_2');

			 $args = array(
				'taxonomy'     => 'product_cat',

				'show_count'   => 1,
				'pad_counts'   => 1,
				'hierarchical' => 1,
				'title_li'     => '',
				'hide_empty'   => 1
			);
			$all_categories = get_categories( $args );
			$cat_array = array();

			foreach($all_categories as $cat) {
				$cat_array[$cat->slug] = $cat->name. ' (' . $cat->count . ')';
				$args['parent'] = $cat->term_id;
				$sub_cats = get_categories($args);
				if ($sub_cats) {
					foreach ($sub_cats as $sub_cat) {
						$cat_array[$sub_cat->slug] = $sub_cat->name. ' (' . $cat->count . ')';
					}
				}
			}



			$product_categories = get_terms( array('taxonomy'=> 'product_cat' , 'fields'=>'ids') );



			$uncategorized = get_posts(array(
				'posts_per_page'=>-1,
				'post_type'=>'product',
				'tax_query'=>array(
					array(
						'taxonomy'=>'product_cat',
						'field'=>'id',
						'terms'=>$product_categories,
						'operator'=>'NOT IN'
					)
				)

			));

			$product_list = [];
			foreach($uncategorized as $prod){
				$sku = get_post_meta($prod->ID, '_sku', true);
				$product_list[$prod->ID] = $prod->post_title . '(#' . $sku . ')';
			}


			$repeater_fields[] = $options_panel->addText('cat_name',array('name'=> __('Category Name ','apc'), 'desc'=>'Category name as displayed in Insightly (not case sensitive)'),true);

			$repeater_fields[] = $options_panel->addSelect('cat_ids',$cat_array,array('name'=> __('WooCommerce Categories','apc'),'class' => 'select2','desc' => __('','apc'), 'multiple'=>true ), true);
			$repeater_fields[] = $options_panel->addSelect('prods',$product_list,array('name'=> __('Uncategorized WooCommerce Products','apc'),'class' => 'select2','desc' => __('','apc'), 'multiple'=>true ), true);
			$repeater_fields[] = $options_panel->addCheckbox('default_cat', array('name'=>'Default Category', 'desc'=>'Use this category if no other categories apply'), true);

			/*
             * Then just add the fields to the repeater block
             */
			//repeater block
			$options_panel->addRepeaterBlock('cat_options',array('sortable' => true, 'inline' => false, 'name' => __('Setup Categories below','apc'),'fields' => $repeater_fields));



			$options_panel->CloseTab();




			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}
		$this->insightly_options = get_option( self::slug );

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action('woocommerce_checkout_order_processed', array($this, 'save_order'));
//		add_action('save_post', array($this, 'schedule_save_order'));
		add_action('save_post', array($this, 'save_order'));
		add_action('scheduled_email', array($this, 'send_email'), 10, 2);
		add_action('scheduled_save', array($this, 'save_order'));
		add_filter('woocommerce_email_classes', array($this, 'add_order_email'));
		if($this->get_api_key() != '')
		$this->insightly = new Insightly($this->get_api_key());
		$this->cat_options = isset($this->insightly_options['cat_options'])?$this->insightly_options['cat_options']:[];

		$this->opportunity_format = isset($this->insightly_options['opp_format'])?$this->insightly_options['opp_format']:'';
		$this->mailbox_format = isset($this->insightly_options['mailbox_format'])?$this->insightly_options['mailbox_format']:'';
	}

	function add_order_email($email_classes){
		require_once( 'includes/class-wc-insightly-order-email.php' );

		// add the email class to the list of email classes that WooCommerce loads
		$email_classes['WC_Insightly_Order_Email'] = new WC_Insightly_Order_Email();

		return $email_classes;
	}

	function schedule_insightly_email($order_id, $order_meta){

		$email = $this->get_insightly_mailbox($order_id, $order_meta);
		wp_schedule_single_event(time() +300, 'scheduled_email', array($order_id, $email));
	}


	function schedule_save_order($order_id){
		wp_schedule_single_event(time() + 300, 'scheduled_save', array($order_id));
	}

	/**
	 * @param $order_id
	 * determine category based on order items
	 */
	function get_order_category($order_id, $products){
		$category_name = '';
		if(count($this->cat_options)>0){


		foreach($products as $product){



				$product_id = $product['product_id'];


			$terms = wp_get_post_terms($product_id, 'product_cat');
			if(count($terms)>0){
				foreach($terms as $term){
					foreach($this->cat_options as $option){
						if(isset($option['cat_ids']) )
							if(in_array($term->slug, $option['cat_ids'])){
								$category_name = $option['cat_name'];
								break;
							}
					}
					if($category_name !=''){
						break;
					}

				}
			}else{
				foreach($this->cat_options as $option){
					if(isset($option['prods']) && in_array($product_id, $option['prods'])){
						$category_name = $option['cat_name'];
						break;
					}
				}
			}

			if($category_name !=''){
				break;
			}

		}
			if($category_name ==''){

				foreach($this->cat_options as $option){
					if(isset($option['default_cat']) && $option['default_cat']=='on'){
						$category_name = $option['cat_name'];
					}
				}

			}


			return $category_name;

		}





	}

	/**
	 * @param $cat_name
	 * retrieve list of categories from insightly
	 * and find the one that matches the name
	 */
	function get_insightly_category($category_name){
	$insightly_category = '';
		$insightly_cats = $this->insightly->getOpportunityCategories();

		foreach($insightly_cats as $cat){
			if(strpos(strtolower($cat->CATEGORY_NAME), strtolower($category_name))!==false){
				$insightly_category = $cat;
				break;
			}
		}

		return $insightly_category;

	}

	/**
	 * @param $cat_name
	 * retrieve list of pipelines from insightly
	 * and find the one that matches the name
	 */
	function get_insightly_pipeline($category_name){
		$insightly_pipeline = '';

		$pipelines = $this->insightly->getPipelines();

		foreach($pipelines as $pipeline){
			if(strpos(strtolower($pipeline->PIPELINE_NAME), strtolower($category_name))!==false){
				$insightly_pipeline = $pipeline;
				break;
			}
		}

		return $insightly_pipeline;

	}

	function send_email($order_id, $email)
	{
        $mailer = WC()->mailer();
        $mail = $mailer->emails['WC_Insightly_Order_Email']->trigger($order_id, $email);
	//	do_action('woocommerce_insightly_update_notification', $order_id, $email);

	}

	function get_order_description($order_id, &$opportunity = []){

		$description = '';
		$order = new WC_Order( $order_id );
		$order_item = $order->get_items();




		foreach( $order_item as $key=> $item ) {

			$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
			$item_meta    = new WC_Order_Item_Meta( $item, $_product );


			$description .= apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ) ;

			$description .= ' (#' . $_product->get_sku() . ')';
			$description .= ' x ' . apply_filters( 'woocommerce_email_order_item_quantity', $item['qty'], $item );
			$description.= ' | '. $order->get_formatted_line_subtotal( $item );

			if ( ! empty( $item_meta->meta ) ) {
				$description .= '<br/><small>' . nl2br( $item_meta->display( true, true, '_', "\n" ) ) . '</small>';
			}
			$description .= '<br>';


		}

		if ( $totals = $order->get_order_item_totals() ) {

			foreach ( $totals as $total ) {

				$description .=  $total['label'] .  $total['value'] . '<br>';
			}
		}
			$order_category  =get_post_meta($order_id, '_insightly_category', true);
		if(false===$order_category || $order_category == '' ) {
			$order_category = $this->get_order_category($order_id, $order_item);
			update_post_meta($order_id, '_insightly_category', $order_category);
		}
		if($order_category){
			if(is_array($order_category)){
				$order_category = $order_category[0];
				update_post_meta($order_id, '_insightly_category', $order_category);
			}

			$cat = $this->get_insightly_category($order_category);
			if($cat !=null){
				$opportunity['CATEGORY_ID'] = $cat->CATEGORY_ID;
			}
			$pipe = $this->get_insightly_pipeline($order_category);
			if($pipe!=null){
				$opportunity['PIPELINE_ID'] = $pipe->PIPELINE_ID;
			}

		}



		$opportunity['OPPORTUNITY_DETAILS'] =  $description;
	}
	function save_order($order_id){

        if($_POST['post_type']!='shop_order'){
            return;
        }
		$order_meta = $this->get_order_meta($order_id);


		$contact = array();
		if($contact_id = $this->find_contact($order_meta['billing_email'])){
			//update contact
			$contact['CONTACT_ID'] = $contact_id;

		}else{
			//add contact
		}
		$this->set_contact_info($contact, $order_meta);

		$contact_id = $this->save_contact($contact);



		$opportunity = array('OPPORTUNITY_STATE'=>'Open');

		if($opp_id = get_post_meta($order_id, '_order_opportunity', true)){
			if(is_numeric($opp_id))
			$opportunity['OPPORTUNITY_ID'] = $opp_id;
		}else{
			//NEW OPPORTUNITY
		}

		if(isset($order_meta['order_total']))
			$this->set_opp_amount($opportunity, $order_meta['order_total']);

		$opp_links = array(
			array(
				'CONTACT_ID'=>$contact_id
			)
		);
		$opportunity['LINKS'] = $opp_links;

		 $this->get_order_description($order_id, $opportunity);
		$order_meta = $this->get_order_meta($order_id);
		$this->set_opp_name($opportunity, $this->get_opp_name($order_id, $order_meta));


		$insightly_mailbox = $this->get_insightly_mailbox($order_id, $order_meta);
		if(isset($opportunity['OPPORTUNITY_ID'])){
			$this->send_email($order_id, $insightly_mailbox);
		}else{
			$this->schedule_insightly_email($order_id, $order_meta);
		}


		 $this->add_opportunity($opportunity, $order_id);



	}



	function save_contact($contact){
		$saved = $this->insightly->addContact((object)$contact);

		return $saved->CONTACT_ID;
	}


	function get_order_meta($order_id){
		$order_meta = get_post_custom($order_id);
		if(count($order_meta)==0){
			$order_meta = $_POST;
		}else{
			foreach($order_meta as $key=>$meta){

				if(is_array($meta)){
					$meta = $meta[0];
				}
				unset($order_meta[$key]);
				$order_meta[ltrim($key, '_')] = $meta;
			}
		}
		return $order_meta;
	}

	function render_template($order_id, $order_meta, $format){
		$opp_name = $format;
		$opp_name = str_replace('%order_id', $order_id, $opp_name);
		foreach($order_meta as $key =>$meta){
			$opp_name = str_replace('%'.$key.'%', $meta, $opp_name);
		}
		if(strpos($opp_name, '%order_number%')!==false){
			$opp_name = str_replace('%order_number%', $order_id, $opp_name);
		}

		return $opp_name;

	}

	function get_insightly_mailbox($order_id, $order_meta){
		return $this->render_template($order_id, $order_meta, $this->mailbox_format);

	}

	function get_opp_name($order_id, $order_meta){
		return $this->render_template($order_id, $order_meta, $this->opportunity_format);


	}

	function set_opp_name(&$opportunity, $name){
		$opportunity['OPPORTUNITY_NAME'] = $name;
	}
	function set_opp_amount(&$opportunity, $amount){
		$opportunity['BID_AMOUNT'] = round($amount);
	}

	function set_opp_details(&$opportunity, $order_id){

	}

	function link_opp_contact(&$opportunity, $contact_id){
		$opportunity['LINKS'][]= array(
			'CONTACT_ID'=>$contact_id
		);
	}

	function merge_opportunity(&$opportunity){


		$current_opp = $this->insightly->getOpportunity($opportunity['OPPORTUNITY_ID']);

		if($current_opp !=null){
			if(count($current_opp->LINKS)>0){
				foreach($current_opp->LINKS as $link)
				$opportunity['LINKS'][] = $link;
			}
		}

	}
	
	function add_opportunity($opportunity, $order_id){

		$opp = null;
		if(!isset($opportunity['OPPORTUNITY_ID'])) {
			if (false === ($existing_opps = get_transient('existing_opportunities'))) {
				$existing_opps = $this->insightly->getOpportunities();
				set_transient('existing_opportunities', $existing_opps);
			}

			$order_num = get_post_meta($order_id, '_order_number', true);
			if (!$order_num) {
				$order_num = $order_id;
			}
			foreach ($existing_opps as $oppp) {
				if (strpos($oppp->OPPORTUNITY_NAME, $order_num) !== false) {
					$opp = $oppp;
					break;
				}
			}

			if ($opp != null) {
				$opportunity['OPPORTUNITY_ID'] = $opp->OPPORTUNITY_ID;
				$this->merge_opportunity($opportunity);
			}

		}else{
			$this->merge_opportunity($opportunity);
		}


		$opp = $this->insightly->addOpportunity((object)$opportunity);


		
		update_post_meta($order_id, '_order_opportunity', $opp->OPPORTUNITY_ID);
		
		return $opp;
	}

	function find_contact($email){
			$contacts = $this->insightly->getContacts(['email'=>$email]);

		if(count($contacts)>0){
			return $contacts[0]->CONTACT_ID;
		}ELSE{
			return false;
		}
	}

	function set_contact_info(&$contact, $order_meta){
		$contact['FIRST_NAME']=$order_meta['billing_first_name'];
			$contact['LAST_NAME']=$order_meta['billing_last_name'];


		$billing_addr = array(
			'ADDRESS_TYPE'=>'Home',
			'STREET'=> trim($order_meta['billing_address_1'] . ' ' . $order_meta['billing_address_2']),
			'CITY'=>$order_meta['billing_city'],
			'STATE'=>$order_meta['billing_state'],
			'POSTCODE'=>$order_meta['billing_postcode'],
			'COUNTRY'=>$order_meta['billing_country']
		);
		$shipping_addr = array(
			'ADDRESS_TYPE'=>'Postal',
			'STREET'=> trim($order_meta['shipping_address_1'] . ' ' . $order_meta['shipping_address_2']),
			'CITY'=>$order_meta['shipping_city'],
			'STATE'=>$order_meta['shipping_state'],
			'POSTCODE'=>$order_meta['shipping_postcode'],
			'COUNTRY'=>$order_meta['shipping_country']
		);

		$email = array(
			'TYPE'=>'Email',
			'LABEL'=>'Home',
			'DETAIL'=>$order_meta['billing_email']
		);
		$contact['ADDRESSES']= array();
		$contact['ADDRESSES'][] = $billing_addr;
		$contact['ADDRESSES'][] = $shipping_addr;
		$contact['CONTACTINFOS']= array($email);
		return $contact;
	}





	private function get_api_key(){

		return isset( $this->insightly_options['api_key'] ) ? esc_attr( $this->insightly_options['api_key']) : '';
	}





	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::slug . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {
			$this->load_file( self::slug . '-script', '/js/script.js', true );
			$this->load_file( self::slug . '-style', '/css/style.css' );
		} // end if/else
	} // end register_scripts_and_styles

	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {

				wp_enqueue_script($name, $url, array('jquery'), false, true ); //depends on jquery
			} else {

				wp_enqueue_style( $name, $url );
			} // end if
		} // end if

	} // end load_file

} // end class



	new WooCommerceInsightlyIntegration();
