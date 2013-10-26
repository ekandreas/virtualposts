<?php

/**
 * Options page , class
 */
class VirtualPostsSettingsUI {

	protected $setttings;

	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_footer', array( &$this, 'admin_footer' ) );
	}

	function admin_footer() {
		if ( $_REQUEST['page'] != 'virtualposts_settings_ui' ) return;
		echo '<script type="text/javascript" src="' . WP_PLUGIN_URL . '/virtualposts/assets/js/vendor/knockout/knockout-3.0.0.js"></script>';
	}

	public function admin_menu() {
		add_submenu_page( 'options-general.php', 'Virtual Posts', __( 'Virtual Posts', 'vpp_' ), 'manage_options', 'virtualposts_settings_ui', array( &$this, 'ui' ) );
	}

	function display_tabs( $current = 'general' ) {
		$tabs = array(
			'feeds' => __( 'Feeds', 'vpp_' ),
			'posts' => __( 'Posts', 'vpp_' ),
			'general' => __( 'General', 'vpp_' )
		);

		if ( ! sizeof( $current ) ) $current = 'general';

		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo '<a class="nav-tab' . $class . '" href="?page=virtualposts_settings_ui&tab=' . $tab . '">' . $name . '</a>';
		}
		echo '</h2>';
	}

	public function ui() {

		if ( $_REQUEST['page'] != 'virtualposts_settings_ui' ) return;

		$tab = $_REQUEST['tab'];
		if ( empty( $tab ) ) $tab = 'general';

		if ( isset( $_REQUEST['virtualposts_settings_ui-update'] ) && wp_verify_nonce( $_REQUEST['virtualposts_settings_ui-nonce'], 'virtualposts-settings' ) ) {

			$save_method = $tab . '_save';
			if ( is_callable( array( $this, $m = $save_method ) ) ) {
				$this->$save_method();
			}

			?>
			<div class="updated">
				<p><?php _e( 'Settings saved', 'vpp_' );
					echo ' ' . date( 'Y-m-d H:i:s' ); ?></p>
			</div>
		<?php
		}

		?>
		<div class="wrap">
			<?php $this->display_tabs( $tab ); ?>
			<form method="post">

				<?php
				$form_method = $tab . '_form';
				if ( is_callable( array( $this, $m = $form_method ) ) ) {
					$this->$form_method();
				}
				?>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save', 'mob3' ) ?>" />
					<input type="hidden" name="virtualposts_settings_ui-nonce" value="<?php echo esc_attr( wp_create_nonce( 'virtualposts-settings' ) ); ?>" />
					<input type="hidden" name="virtualposts_settings_ui-update" value="true" />
					<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />
				</p>

			</form>

		</div>
	<?php
	}

	function general_form() {

		$settings = VirtualPostsSettings::get( 'general' );

		$cache_types = array_merge( array( 'auto' ), phpFastCache::$supported_api );

		?>
		<h3>
			General settings for Virtual Posts
		</h3>

		<table class="widefat" style="max-width: 700px">
			<thead>
			<tr>
				<th>Field</th>
				<th>Value</th>
				<th>Notes</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th>Field</th>
				<th>Value</th>
				<th>Notes</th>
			</tr>
			</tfoot>
			<tbody>
			<tr>
				<td>Cache:</td>
				<td>
					<select name="cache" data-bind="value: cache, options: availableCache"></select>
				</td>
				<td><i>Cache type for this installation. Drivers installed: <?php

						$system = phpFastCache::systemInfo();
						if( !$system['drivers'] && is_array( $system['drivers'] ) ) {
							echo 'none';
						}
						else{
							$first = true;
							foreach( $system['drivers'] as $driver=>$value ){
								if( !$first ) echo ', ';
								echo $driver;
								$first = false;
							}
						}

						?></i></td>
			</tr>
			<tr>
				<td>Standard Timeout:</td>
				<td>
					<input name="timeout" type="text" value="<?php echo $settings['timeout'] ? $settings['timeout'] : '3600'; ?>" />
				</td>
				<td><i>seconds</i></td>
			</tr>
			</tbody>
		</table>

		<script>

			var general_model;

			jQuery(document).ready(function ($) {
				general_model = new VirtualPostsGeneralModel();
				ko.applyBindings( general_model );
			});

			var VirtualPostsGeneralModel = function () {

				var self = this;
				self.cache = ko.observable('<?php echo $settings['cache']; ?>');
				self.availableCache = ko.observableArray(<?php echo json_encode( $cache_types  ); ?>)

			}

		</script>

		<?php
	}

	function general_save() {
		$settings          = VirtualPostsSettings::get( 'general' );
		$settings['cache'] = esc_attr( $_REQUEST['cache'] );
		$settings['timeout'] = (int)esc_attr( $_REQUEST['timeout'] );
		VirtualPostsSettings::update( 'general', $settings );
	}

	function feeds_form() {

		$settings = VirtualPostsSettings::get( 'feeds' );

		?>
		<h3>
			Add feeds to Virtual Posts
		</h3>

		<div style="vertical-align: middle; background:#EEEEEE;color:#000;border:1px solid #CCC;padding:10px;margin-top:12px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;max-width: 680px">
			<table>
				<tr>
					<td>
						Name
					</td>
					<td>
						Url
					</td>
					<td>
						Max items
					</td>
					<td>
						Timeout
					</td>
					<td>

					</td>
				</tr>
				<tr>
					<td>
						<input size="15" type="text" placeholder="Name" data-bind="value: name" />
					</td>
					<td>
						<input size="15" type="text" placeholder="Feed Url" data-bind="value: url" />
					</td>
					<td>
						<input size="5" type="text" placeholder="Max items" data-bind="value: max" />
					</td>
					<td>
						<input size="5" type="text" placeholder="Timeout" data-bind="value: timeout" />
					</td>
					<td>
						<input type="button" class="button-secondary" value="Add" data-bind="click: addRow" />
					</td>
				</tr>
			</table>


		</div>

		<br />

		<table class="widefat" style="max-width: 700px" data-bind="visible: feeds().length">
			<thead>
			<tr>
				<th>Name</th>
				<th>Url</th>
				<th>Max items</th>
				<th>Timeout</th>
				<th></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th>Name</th>
				<th>Url</th>
				<th>Max items</th>
				<th>Timeout</th>
				<th></th>
			</tr>
			</tfoot>
			<tbody data-bind="foreach: feeds">
			<tr>
				<td data-bind="text: name"></td>
				<td data-bind="text: url"></td>
				<td data-bind="text: max"></td>
				<td data-bind="text: timeout"></td>
				<td><a href="javascript:return false;" data-bind="click: $root.removeRow">Replace</a></td>
			</tr>
			</tbody>
		</table>

		<input name="feeds" type="hidden" data-bind="value: ko.toJSON(feeds, null, 2)" />

		<script>

			var feeds_model;

			jQuery(document).ready(function ($) {
				feeds_model = new VirtualPostsFeedsModel();
				ko.applyBindings( feeds_model );
			});

			var VirtualPostsFeedsModel = function () {

				var self = this;

				self.id = ko.observable('');
				self.name = ko.observable('');
				self.url = ko.observable('');
				self.max = ko.observable(10);
				self.timeout = ko.observable(3600);

				self.feeds = ko.observableArray(<?php echo json_encode( $settings ); ?>);

				self.addRow = function () {

					self.feeds.push({
						id				: self.uid(),
						name  		: self.name(),
						url   		: self.url(),
						max   		: parseInt( self.max() ) | 0,
						timeout   : parseInt( self.timeout() ) | 0
					});

				}

				self.removeRow = function (row) {
					self.name( row.name );
					self.url( row.url );
					self.max( row.max );
					self.timeout( row.timeout );
					self.feeds.remove(row);
				};

				self.alphaChars = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
				self.uid = function(uniqIDLength) {
					if(!uniqIDLength) uniqIDLength = 10;
					var uniqueID = '', dateStamp = Date().toString().replace(/\s/g, '');
					for(var uniqIDCounter = 0; uniqIDCounter < uniqIDLength; uniqIDCounter++) {
						uniqueID += self.alphaChars[Math.round(Math.random() * 25).toString()];
						uniqueID += Math.round(Math.random() * 10);
						uniqueID += dateStamp.charAt(Math.random() * (dateStamp.length - 1));
					}
					return uniqueID;
				}

			}

		</script>

	<?php
	}

	function feeds_save() {
		//$feeds          = VirtualPostsSettings::get( 'feeds' );
		$feeds = json_decode( stripslashes( $_REQUEST['feeds'] ), true );
		VirtualPostsSettings::update( 'feeds', $feeds );
	}

	function posts_form() {

		$feeds    = VirtualPostsSettings::get( 'feeds' );

		?>
		<h3>
			Cached posts
		</h3>

		<table class="widefat" style="max-width: 700px">
			<thead>
			<tr>
				<th>Headline</th>
				<th>Feed</th>
				<th>Local link</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th>Headline</th>
				<th>Feed</th>
				<th>Local link</th>
			</tr>
			</tfoot>
			<tbody>
			<tbody data-bind="foreach: posts">
			<tr>
				<td data-bind="text: headline"></td>
				<td data-bind="text: feed"></td>
				<td data-bind="text: link"></td>
			</tr>
			</tbody>
		</table>

		<script>

			var posts_model;

			jQuery(document).ready(function ($) {
				posts_model = new VirtualPostsPostsModel();
				ko.applyBindings( posts_model );
				posts_model.reload();
			});

			var VirtualPostsPostsModel = function () {

				var self = this;
				self.feeds = ko.observableArray(<?php echo json_encode( $feeds ); ?>);
				self.posts = ko.observableArray([]);

				self.reload = function(){

					for( var i=0; i<self.feeds().length; i++ ){
						jQuery.ajax({
							url     : '<?php echo admin_url('admin-ajax.php'); ?>',
							method  : "post",
							data    : {
								action: 'virtualposts_feed',
								id : self.feeds()[i].id,
								nonce : '<?php echo wp_create_nonce( 'virtualposts_feed' ); ?>'
							},
							dataType: 'json',
							async   : false,
							success : function (data) {
								for( var d=0; d<data.length; d++ ){
									self.posts.push(data[d]);
								}
							}
						});

					}

				}

			}

		</script>

	<?php
	}

	function posts_save() {
		//$posts          = VirtualPostsSettings::get( 'posts' );
		//VirtualPostsSettings::update( 'posts', $posts );
	}

}

$_virtualposts_settings_ui = new VirtualPostsSettingsUI();
