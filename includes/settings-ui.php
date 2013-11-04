<?php

/**
 * Options page , class
 */
class VirtualPostsSettingsUI {

	protected $setttings;

	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_head', array( &$this, 'admin_head' ) );
		add_action( 'admin_footer', array( &$this, 'admin_footer' ) );
		add_action( 'wp_ajax_virtualposts_feeds_save', array( &$this, 'feeds_save' ) );
	}

	function admin_footer() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] != 'virtualposts_settings_ui' ) return;
		echo '<script type="text/javascript" src="' . WP_PLUGIN_URL . '/virtualposts/assets/js/vendor/knockout/knockout-3.0.0.js"></script>';
	}

	function admin_head() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] != 'virtualposts_settings_ui' ) return;
		echo '<link rel="stylesheet" type="text/css" media="all" href="' . WP_PLUGIN_URL . '/virtualposts/assets/css/src/whhg-font/css/whhg.css" />';
	}

	public function admin_menu() {
		add_submenu_page( 'options-general.php', 'Virtual Posts', __( 'Virtual Posts', 'vpp_' ), 'manage_options', 'virtualposts_settings_ui', array( &$this, 'ui' ) );
	}

	function display_tabs( $current = 'feeds' ) {

		$settings = VirtualPostsSettings::get( 'general' );

		$tabs = array(
			'feeds'   => __( 'Feeds', 'vpp_' ),
			'cache'   => __( 'Cache', 'vpp_' ),
			'general' => __( 'General', 'vpp_' )
		);

		if ( ! sizeof( $current ) ) $current = 'feeds';

		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo '<a class="nav-tab' . $class . '" href="?page=virtualposts_settings_ui&tab=' . $tab . '">' . $name;
			if ( $tab == 'general' && ! $settings['interval'] ) {
				echo esc_html( ' <i class="icon-erroralt" style="font-size: 16px;color: red;"></i>' );
			}
			echo '</a>';
		}
		echo '</h2>';
	}

	public function ui() {

		if ( $_REQUEST['page'] != 'virtualposts_settings_ui' ) return;

		$tab = empty( $_REQUEST['tab'] ) ? $tab = 'feeds' : $_REQUEST['tab'];

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

		<?php
		if ( ! $settings['interval'] ) echo esc_html( '<p><i class="icon-erroralt" style="font-size: 16px;color: red;"></i> Please save your settings before usage!</p>' );
		?>

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
		if ( ! $system['drivers'] && is_array( $system['drivers'] ) ) {
			echo 'none';
		}
		else {
			$first = true;
			foreach ( $system[ 'drivers' ] as $driver => $value ) {
				if ( ! $first ) echo ', ';
				echo esc_attr( $driver );
				$first = false;
			}
		}

					?></i></td>
			</tr>
			<tr>
				<td>Fetch interval:</td>
				<td>
					<input name="interval" type="text" value="<?php echo absint( $settings['interval'] ? $settings['interval'] : '10' ); ?>" />
				</td>
				<td><i>minutes between cron jobs to fetch feeds</i></td>
			</tr>
			</tbody>
		</table>

		<script>

			var general_model;

			jQuery(document).ready(function ($) {
				general_model = new VirtualPostsGeneralModel();
				ko.applyBindings(general_model);
			});

			var VirtualPostsGeneralModel = function () {

				var self = this;
				self.cache = ko.observable( '<?php echo esc_attr( array_key_exists( 'cache', $settings ) ? esc_attr( $settings['cache'] ) : 'auto' ); ?>' );
				self.availableCache = ko.observableArray(<?php echo json_encode( $cache_types  ); ?>)

			}

		</script>

	<?php
	}

	function general_save() {
		$settings             = VirtualPostsSettings::get( 'general' );
		$settings['cache']    = esc_attr( $_REQUEST['cache'] );
		$settings['interval'] = (int) esc_attr( $_REQUEST['interval'] );
		VirtualPostsSettings::update( 'general', $settings );
	}

	function feeds_form() {

		$settings = VirtualPostsSettings::get( 'feeds' );
		$ajaxurl  = admin_url( 'admin-ajax.php' );

		?>
		<h3>
			Add feeds to Virtual Posts
		</h3>

		<div id="new_feed" style="vertical-align: middle; background:#FFFFFF;color:#000;border:1px solid #CCC;padding:10px;margin-top:12px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;max-width: 680px">
			<i class="icon-addtolist"></i>
			<input size="20" type="text" placeholder="Name" id="new_name" />
			<input size="20" type="text" placeholder="Feed Url" id="new_url" />
			<input size="10" type="text" placeholder="Max items" id="new_max" />
			<input type="button" class="button-secondary" value="Add new" onclick="return add_new_feed();" />
		</div>

		<br />

		<div id="feed_list">
			<table class="widefat" style="max-width: 700px" data-bind="visible: feeds().length">
				<thead>
				<tr>
					<th>Name</th>
					<th>Url</th>
					<th>Max items</th>
					<th>Found</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<th>Name</th>
					<th>Url</th>
					<th>Max items</th>
					<th>Found</th>
				</tr>
				</tfoot>
				<tbody data-bind="foreach: feeds">
				<tr>
					<td>
						<strong data-bind="text: name"></strong>

						<div class="row-actions">
							<span class="edit"><a href="#" title="Edit" data-bind="click: $root.editRow">Edit</a> | </span>
							<span class="trash"><a href="#" title="Delete" class="submitdelete" data-bind="click: $root.removeRow">Delete</a> | </span>
							<span class="fetch"><a href="#" title="Fetch" data-bind="click: $root.test">Fetch</a></span>
						</div>
					</td>
					<td data-bind="text: url"></td>
					<td data-bind="text: max"></td>
					<td style="text-align: center;">
						<span data-bind="if: $data.found && !$data.loading"><span data-bind="text: found"></span></span>
						<span data-bind="if: $data.error && !$data.loading"><a data-bind="attr: { title: error },click: $root.alertError" style="color:#FF3333"><i class="icon-erroralt" style="font-size: 16px;"></i></a></span>
						<span style="display:none;" data-bind="attr: { id: 'ajax_' + $data.id }"><img src="<?php echo esc_url( WP_PLUGIN_URL . '/virtualposts/images/AjaxLoader.gif' ); ?>" alt="Fetching feed" /></span>
					</td>
				</tr>
				<tr data-bind="visible: $parent.edit().id==$data.id, template: { name : 'template' }" style="background-color: #FFFFFF;">
				</tr>
				</tbody>
			</table>

			<input id="feeds_data" name="feeds" type="hidden" data-bind="value: ko.toJSON(feeds, null, 2)" />
		</div>

		<script id="template" type="text/html">
			<td><input size="20" type="text" placeholder="Name" data-bind="value: name" /></td>
			<td><input size="20" type="text" placeholder="Feed Url" data-bind="value: url" /></td>
			<td><input size="10" type="text" placeholder="Max items" data-bind="value: max" /></td>
			<td><input type="button" class="button-secondary" value="Save" data-bind="click: $parent.saveRow" /></td>
		</script>

		<script>

			var feed_list;

			jQuery(document).ready(function ($) {
				feed_list = new VirtualPostsFeedsModel();
				ko.applyBindings(feed_list, document.getElementById('feed_list'));
			});

			var VirtualPostsFeedsModel = function () {

				var self = this;

				self.id = ko.observable('');
				self.name = ko.observable('');
				self.url = ko.observable('');
				self.max = ko.observable(10);
				self.ttl = ko.observable(3600);

				self.loading = ko.observable('');
				self.edit = ko.observableArray([]);
				self.edittable = ko.observable('');

				self.feeds = ko.observableArray(<?php echo json_encode( $settings ); ?>);

				self.addRow = function () {

					self.feeds.push({
						id   : self.uid(),
						name : self.name(),
						url  : self.url(),
						max  : parseInt(self.max()) | 0,
						ttl  : parseInt(self.ttl()) | 0,
						'new': true
					});

				}

				self.removeRow = function (row) {
					self.name(row.name);
					self.url(row.url);
					self.max(row.max);
					self.ttl(row.ttl);
					self.feeds.remove(row);
					self.saveJson();
				};

				self.alertError = function (row) {
					alert(row.error);
				};

				self.editRow = function (row) {

					if (self.edit().id == row.id) {
						self.edit([]);
						return;
					}

					self.edit(row);

				};

				self.saveRow = function (row) {

					var index = self.feeds.indexOf(row);
					self.feeds.replace(self.feeds()[index], { id: row.id, name: row.name, url: row.url, max: row.max });
					self.saveJson();
					self.edit([]);

				}

				self.saveJson = function () {

					jQuery.ajax({
						url     : '<?php echo esc_url( $ajaxurl ); ?>',
						method  : "post",
						data    : {
							action                          : 'virtualposts_feeds_save',
							feeds                           : jQuery('#feeds_data').val(),
							'virtualposts_settings_ui-nonce': '<?php echo esc_attr( wp_create_nonce( 'virtualposts-settings' ) ); ?>'
						},
						dataType: 'json',
						async   : false,
						success : function (data) {
							self.edit([]);
						}
					});

				}


				self.alphaChars = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
				self.uid = function (uniqIDLength) {
					if (!uniqIDLength) uniqIDLength = 10;
					var uniqueID = '', dateStamp = Date().toString().replace(/\s/g, '');
					for (var uniqIDCounter = 0; uniqIDCounter < uniqIDLength; uniqIDCounter++) {
						uniqueID += self.alphaChars[Math.round(Math.random() * 25).toString()];
						uniqueID += Math.round(Math.random() * 10);
						//uniqueID += dateStamp.charAt(Math.random() * (dateStamp.length - 1));
					}
					return uniqueID;
				}

				self.test = function (row) {

					jQuery('#ajax_' + row.id).show();
					jQuery.ajax({
						url     : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						method  : "post",
						data    : {
							action: 'virtualposts_feed',
							id    : row.id,
							nonce : '<?php echo esc_attr( wp_create_nonce( 'virtualposts_feed' ) ); ?>'
						},
						dataType: 'json',
						async   : false,
						success : function (data) {

							jQuery.ajax({
								url     : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
								method  : "post",
								data    : {
									action: 'virtualposts_feeds'
								},
								dataType: 'json',
								async   : false,
								success : function (data) {
									self.feeds(data);
								}
							});

						}
					});

				};

			}

			var add_new_feed = function () {
				feed_list.feeds.push({ id: feed_list.uid(), name: jQuery('#new_name').val(), url: jQuery('#new_url').val(), max: jQuery('#new_max').val() });
				feed_list.saveJson();
			}


		</script>

	<?php
	}

	function feeds_save() {

		if ( wp_verify_nonce( $_REQUEST['virtualposts_settings_ui-nonce'], 'virtualposts-settings' ) ) {
			$feeds = json_decode( stripslashes( $_REQUEST['feeds'] ), true );
			foreach ( $feeds as $key => $feed ) {
				unset( $feeds[$key]['new'] );
			}
			VirtualPostsSettings::update( 'feeds', $feeds );
		}

	}

	function cache_form() {

		$feeds = VirtualPostsSettings::get( 'feeds' );

		?>
		<h3>
			Current posts in cache
		</h3>

		<p>
			<button class="button-secondary" data-bind="click: load_cache">Show cached posts</button>
			<button class="button-secondary" data-bind="click: reload">Reload cache from feeds</button>
		</p>

		<div id="ajaxloader" style="display: none;">
			<p>
				<img src="<?php echo esc_url( WP_PLUGIN_URL . '/virtualposts/images/ajax-rss.gif' ); ?>" alt="Loading feeds..." /> Loading feed "<span data-bind="text: loadingName"></span>", please wait!
			</p>
		</div>

		<table class="widefat" style="max-width: 700px">
			<thead>
			<tr>
				<th>Headline</th>
				<th>Date</th>
				<th>Feed</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th>Headline</th>
				<th>Date</th>
				<th>Feed</th>
			</tr>
			</tfoot>
			<tbody>
			<tbody data-bind="foreach: posts">
			<tr>
				<td><a data-bind="text: title, attr: { href: '/virtualposts/' + link }" target="_blank"></a></td>
				<td data-bind="text: date"></td>
				<td data-bind="text: feed"></td>
			</tr>
			</tbody>
		</table>

		<script>

			var posts_model;

			jQuery(document).ready(function ($) {

				$(document).ajaxStart(function () {
					$("#ajaxloader").show();
				}).ajaxStop(function () {
							$("#ajaxloader").hide();
						});

				posts_model = new VirtualPostsPostsModel();
				ko.applyBindings(posts_model);
				posts_model.load_cache();

			});

			var VirtualPostsPostsModel = function () {

				var self = this;
				self.feeds = ko.observableArray(<?php echo json_encode( $feeds ); ?>);
				self.posts = ko.observableArray([]);
				self.loadingName = ko.observable('');

				self.reload = function () {

					jQuery.ajax({
						url    : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						method : "post",
						data   : {
							action: 'virtualposts_clear_cache'
						},
						async  : false,
						success: function (data) {
						}
					});

					self.posts = ko.observableArray([]);
					for (var i = 0; i < self.feeds().length; i++) {
						var id = self.feeds()[i].id;
						self.load_feed(self.feeds()[i]);
					}

				}

				self.load_feed = function (feed) {

					self.loadingName(feed.name);

					jQuery.ajax({
						url     : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						method  : "post",
						data    : {
							action: 'virtualposts_feed',
							id    : feed.id,
							nonce : '<?php echo esc_attr( wp_create_nonce( 'virtualposts_feed' ) ); ?>'
						},
						dataType: 'json',
						async   : false,
						success : function (data) {
							for (var d = 0; d < data.length; d++) {
								self.posts.push(data[d]);
							}
						}
					});

				}

				self.load_cache = function () {

					jQuery.ajax({
						url     : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						method  : "post",
						data    : {
							action: 'virtualposts_cache'
						},
						dataType: 'json',
						async   : false,
						success : function (data) {
							for (var d = 0; d < data.length; d++) {
								self.posts.push(data[d]);
							}
						}
					});

				}

			}

		</script>

	<?php
	}

	function cache_save() {
		//$posts          = VirtualPostsSettings::get( 'posts' );
		//VirtualPostsSettings::update( 'posts', $posts );
	}

}

$_virtualposts_settings_ui = new VirtualPostsSettingsUI();
