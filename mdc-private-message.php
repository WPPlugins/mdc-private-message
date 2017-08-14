<?php
/**
	Plugin Name: MDC Private Message
	Plugin URI: http://medhabi.com/items/mdc-private-message/
	Description: MDC Private Message is probably the best plugin to create an easy messaging system in WordPress. It helps you create an internal private messaging systems where registered users can send private messages among themselves.
	Author: Nazmul Ahsan
	Version: 1.0.0
	Author URI: http://nazmulahsan.me
	Stable tag: 1.0.0
	Text Domain: MedhabiDotCom
*/

define('MDC_PRIVATE_MESSAGE_PRO', false);

class MDC_Private_Message{

	public function __construct(){
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'mdc_add_action_links') );

		add_action( 'after_setup_theme', array($this, 'mdc_create_table') );

		add_action( 'wp_enqueue_scripts', array($this, 'mdc_enqueue_scripts') );
		add_action( 'wp_head', array($this, 'mdc_admin_ajax_variable') );
		add_action( 'wp_head', array($this, 'mdc_custom_scripts') );
		add_action( 'wp_ajax_mdc_send_msg', array($this, 'mdc_msg_send_ajax') );
		add_action( 'wp_ajax_mdc_delete_msg_rcvr', array($this, 'mdc_msg_delete_rcvr_ajax') );
		add_action( 'wp_ajax_mdc_delete_msg_sndr', array($this, 'mdc_msg_delete_sndr_ajax') );
		
		add_action( 'admin_enqueue_scripts', array($this, 'mdc_enqueue_scripts') );
		add_action( 'admin_head', array($this, 'mdc_admin_ajax_variable') );
		add_action( 'admin_head', array($this, 'mdc_custom_scripts') );
		add_action( 'admin_ajax_mdc_send_msg', array($this, 'mdc_msg_send_ajax') );
		add_action( 'admin_ajax_mdc_delete_msg_rcvr', array($this, 'mdc_msg_delete_rcvr_ajax') );
		add_action( 'admin_ajax_mdc_delete_msg_sndr', array($this, 'mdc_msg_delete_sndr_ajax') );

		add_action( 'admin_menu', array($this, 'mdc_option_page') );
	}

	public function mdc_add_action_links ( $links ) {
		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=mdc-msg-settings' ) . '"><img src="'.plugins_url( 'images/icon-red.png', __FILE__).'" /> Settings</a>',
		);
		return array_merge( $links, $mylinks );
	}

	public function mdc_create_table(){
		global $wpdb;
		$msg_tbl = $wpdb->prefix."mdc_private_message";

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $msg_tbl (
			msg_id mediumint(7) NOT NULL AUTO_INCREMENT,
			sender mediumint(5) NOT NULL,
			receiver mediumint(5) NOT NULL,
			subject varchar(255) NOT NULL,
			message text NOT NULL,
			time_sent datetime NOT NULL,
			time_seen datetime NOT NULL,
			is_read tinyint(1) NOT NULL,
			is_trash_sndr tinyint(1) NOT NULL,
			is_trash_rcvr tinyint(1) NOT NULL,
			UNIQUE KEY msg_id (msg_id)
			) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function mdc_message_new(){
		$argu = array(
			'exclude'	=>	array(get_current_user_id())
		);
		$users = get_users($argu);
		$new = '<div class="mdc_msg_form_div">';
		$new .= '<form action="" method="POST" id="mdc_msg_form">';
		$new .= '<input type="hidden" name="mdc_msg_sndr" id="mdc_msg_sndr" value="'.get_current_user_id().'">';
		if(!isset($_GET['user'])){
			$new .= '<p><label for="mdc_msg_rcvr">Receiver</label><span><select name="mdc_msg_rcvr" id="mdc_msg_rcvr">';
			foreach ($users as $user) {
				$new .= '<option value="'.$user->ID.'">'.$user->display_name.'</option>';
			}
			$new .= '</select></span></p>';
		}
		else{
			$new .= '<input type="hidden" name="mdc_msg_rcvr" id="mdc_msg_rcvr" value="'.$_GET['user'].'">';
			$new .= '<p><label for="mdc_msg_rcvr">Receiver</label><span>'.get_userdata($_GET['user'])->display_name.'</span></p>';
		}
		$new .= '<p><label for="mdc_msg_subj">Subject</label><span><input type="text" name="mdc_msg_subj" id="mdc_msg_subj" required /></span></p>';		
		$new .= '<p><label for="mdcmsgbody">Message</label><span><textarea name="mdcmsgbody" id="mdcmsgbody" class="mdcmsgbody" rows="10" required></textarea></span></p>';
		$new .= '<p><input type="submit" value="Send Message" id="mdc_msg_send" class="button button-primary" /></p>';		
		$new .= '</form>';
		$new .= '<div class="msg_success hidden">Message has been sent!</div>';
		$new .= '<div class="msg_error hidden">Something went wrong! Message was not sent.</div>';
		$new .= '</div>';

		return $new;
	}

	public function mdc_message_inbox(){
		global $wpdb;

		$this->new_msg_page = "admin.php?page=mdc-new-msg&";
		$view_inbox  = '?page='.$_GET['page'].'&view=';
		
		$msg_tbl = $wpdb->prefix."mdc_private_message";
		$current_user = get_current_user_id();
		if(isset($_GET['view'])){
			$msg_id = $_GET['view'];
			$messages = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * FROM $msg_tbl WHERE receiver = %d AND msg_id = %d",
								$current_user,
								$msg_id
								)
							);
			$message = $messages[0];
			$inbox = '<div class="mdc_msg_form_div">';
			if(count($messages) > 0){
				$inbox .= '<p><label>Sender</label><span>'.get_userdata($message->sender)->display_name.'</span></p>';
				$inbox .= '<p><label>Subject</label><span>'.$message->subject.'</span></p>';
				$inbox .= '<p><label>Time Sent</label><span>'.$message->time_sent.'</span></p>';
				$inbox .= '<hr />';
				$inbox .= '<p><span>'.nl2br($message->message).'</span></p>';
			}
			else{
				$inbox .= '<p>Unauthorized!</p>';
			}
			if(is_admin()){

			}
			$inbox .= '<hr /><a href="'.$this->new_msg_page.'user='.$message->sender.'">Reply to this message</a></div>';

			if($message->is_read != 1){
				$wpdb->update(
					$msg_tbl,
					array('time_seen' => date('Y-m-d H:i:s'), 'is_read' => 1),
					array('msg_id' => $msg_id, 'receiver' => $current_user)
				);
			}
		}
		else{
			$messages = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * FROM $msg_tbl WHERE receiver = %d AND is_trash_rcvr != %d ORDER BY time_sent DESC",
								$current_user,
								1
								)
							);
			$inbox = '<table class="mdc_msg_table">
						<thead>
							<tr>
								<th>Sender</th>
								<th>Subject</th>
								<th>Time</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>';
			foreach ($messages as $message) {
				$is_read = $message->is_read;
				if($is_read == 0){
					$cls = "msg_unseen";
				}
				else{
					$cls = "msg_seen";
				}
				$inbox .= '<tr msg_id="'.$message->msg_id.'" class="'.$cls.'">
							<td>'.get_userdata($message->sender)->display_name.'</td>
							<td>'.$message->subject.'</td>
							<td>'.$message->time_sent.'</td>
							<td>
								<a href="'.$view_inbox.$message->msg_id.'"><img title="Read this message" src="'.plugins_url('images/view.png', __FILE__).'" class="msg_action msg_view" /></a>
								<a href="'.$this->new_msg_page.'user='.$message->sender.'"><img title="Reply to this message" src="'.plugins_url('images/reply.png', __FILE__).'" class="msg_action msg_reply" /></a>
								<img title="Delete this message" src="'.plugins_url('images/delete.png', __FILE__).'" class="msg_action msg_delete_rcvr" />
							</td>
						</tr>';
			}
			$inbox .= '</tbody>
					</table>';
		}
		return $inbox;
	}

	public function mdc_message_outbox(){
		global $wpdb;

		$this->new_msg_page = "admin.php?page=mdc-new-msg&";
		$view_outbox  = '?page='.$_GET['page'].'&view=';
		
		$msg_tbl = $wpdb->prefix."mdc_private_message";
		$current_user = get_current_user_id();
		if(isset($_GET['view'])){
			$msg_id = $_GET['view'];
			$messages = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * FROM $msg_tbl WHERE sender = %d AND msg_id = %d",
								$current_user,
								$msg_id
								)
							);
			
			$message = $messages[0];
			$time_seen = ($message->is_read != 1) ? 'Unseen' : $message->time_seen;
			$outbox = '<div class="mdc_msg_form_div">';
			if(count($messages) > 0){
				$outbox .= '<p><label>To</label><span>'.get_userdata($message->receiver)->display_name.'</span></p>';
				$outbox .= '<p><label>Subject</label><span>'.$message->subject.'</span></p>';
				$outbox .= '<p><label>Time Sent</label><span>'.$message->time_sent.'</span></p>';
				if(get_option('mdc_show_delivery_time') == 1):
				$outbox .= '<p><label>Time Seen</label><span>'.$time_seen.'</span></p>';
				endif;
				$outbox .= '<hr />';
				$outbox .= '<p><span>'.nl2br($message->message).'</span></p>';
			}
			else{
				$outbox .= '<p>Unauthorized!</p>';
			}
			$outbox .=  '<hr /><a href="'.$this->new_msg_page.'user='.$message->receiver.'">Send \''.get_userdata($message->receiver)->display_name.'\' a new message</a></div>';
		}
		else{
			$messages = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * FROM $msg_tbl WHERE sender = %d AND is_trash_sndr != %d ORDER BY time_sent DESC",
								$current_user,
								1
							)
						);
			$outbox  = '<table class="mdc_msg_table">
							<thead>
								<tr>
									<th>Receiver</th>
									<th>Subject</th>
									<th>Time</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>';
			foreach ($messages as $message) {
				$outbox .= '<tr msg_id="'.$message->msg_id.'">
								<td>'.get_userdata($message->receiver)->display_name.'</td>
								<td>'.$message->subject.'</td>
								<td>'.$message->time_sent.'</td>
								<td>
									<a href="'.$view_outbox.$message->msg_id.'"><img title="Read this message" src="'.plugins_url('images/view.png', __FILE__).'" class="msg_action msg_view" /></a>
									<a href="'.$this->new_msg_page.'user='.$message->receiver.'"><img title="New message to this user" src="'.plugins_url('images/reply.png', __FILE__).'" class="msg_action msg_reply" /></a>
									<img title="Delete this message" src="'.plugins_url('images/delete.png', __FILE__).'" class="msg_action msg_delete_sndr" />
								</td>
							</tr>';
			}
			$outbox .= '</tbody>
				</table>';
		}
		return $outbox;
	}

	public function mdc_enqueue_scripts(){
		wp_enqueue_style('mdc-private-msg', plugins_url('css/style.css', __FILE__));
		wp_enqueue_style('mdc-private-msg-admin', plugins_url('css/admin.css', __FILE__));
		wp_enqueue_script('jquery');
		wp_enqueue_script('mdc-private-msg', plugins_url('js/script.js', __FILE__), true);
		wp_enqueue_script('mdc-private-msg-admin', plugins_url('js/admin.js', __FILE__), true);
		
		wp_enqueue_style('mdc-datatable', plugins_url('dataTables/jquery.dataTables.css', __FILE__));
		wp_enqueue_script('mdc-datatable', plugins_url('dataTables/jquery.dataTables.js', __FILE__), true);
	}

	public function mdc_custom_scripts(){
		echo "<style>".get_option('mdc_msg_custom_css')."</style>";
	}

	public function mdc_admin_ajax_variable(){ ?>
		<script type="text/javascript"> //<![CDATA[
			ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
		//]]> </script>
	<?php }

	public function mdc_msg_send_ajax(){
		$from		= $_POST['from'];
		$to			= $_POST['to'];
		$subject	= $_POST['subject'];
		$message	= $_POST['message'];
		global $wpdb;
		$msg_tbl = $wpdb->prefix."mdc_private_message";
		$inserted = $wpdb->insert(
			$msg_tbl,
			array(
				'sender'	=>	$from,
				'receiver'	=>	$to,
				'subject'	=>	$subject,
				'message'	=>	stripslashes($message),
				'time_sent'	=>	date('Y-m-d H:i:s'),
				'is_read'	=>	0,
			)
		);
		if($inserted){
			$status = "Sent";
		}
		else{
			$status = "Failed";
		}

		$ret = array(
			'status'	=>	$status,
			'from'		=>	$from,
			'to'		=>	$to
		);

		echo json_encode($ret);
		die();
	}

	public function mdc_msg_delete_rcvr_ajax(){
		$msg_id = $_POST['msg_id'];
		global $wpdb;
		$msg_tbl = $wpdb->prefix."mdc_private_message";
		$wpdb->update(
			$msg_tbl,
			array('is_trash_rcvr' => 1),
			array('msg_id' => $msg_id)
		);
		echo "Deleted";
		die();
	}

	public function mdc_msg_delete_sndr_ajax(){
		$msg_id = $_POST['msg_id'];
		global $wpdb;
		$msg_tbl = $wpdb->prefix."mdc_private_message";
		$wpdb->update(
			$msg_tbl,
			array('is_trash_sndr' => 1),
			array('msg_id' => $msg_id)
		);
		echo "Deleted";
		die();
	}

	public function echo_mdc_message_new(){ ?>
		<div class="wrap">
			<h2><img src="<?php echo plugins_url( 'images/icon-red.png', __FILE__); ?>"> MDC Private Message</h2>
			<div style="clear: left"></div>
			<div class="postbox-container" style="width: 100%">
				<div id="poststuff" class="metabox-holder">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="mdc_ts_opt_page" class="postbox ">
							<div title="Click to toggle" class="handlediv"><br></div>
							<h3 class="hndle"><span>Compose New Message</span></h3>
							<div class="inside">
							<?php if(current_user_can('administrator')): ?>
								<div class="option_page_right">
								<?php $this->option_page_right_adv();?>
								</div>
							<?php endif; ?>
								<div class="option_page_left">
								<?php echo $this->mdc_message_new();?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	<?php 
	}

	public function echo_mdc_message_inbox(){ ?>
		<div class="wrap">
			<h2><img src="<?php echo plugins_url( 'images/icon-red.png', __FILE__); ?>"> MDC Private Message</h2>
			<div style="clear: left"></div>
			<div class="postbox-container" style="width: 100%">
				<div id="poststuff" class="metabox-holder">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="mdc_ts_opt_page" class="postbox ">
							<div title="Click to toggle" class="handlediv"><br></div>
							<h3 class="hndle"><span>Inbox</span></h3>
							<div class="inside">
							<?php if(current_user_can('administrator')): ?>
								<div class="option_page_right">
								<?php $this->option_page_right_adv();?>
								</div>
							<?php endif; ?>
								<div class="option_page_left">
								<?php echo $this->mdc_message_inbox();?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	<?php 
	}

	public function echo_mdc_message_outbox(){ ?>
		<div class="wrap">
			<h2><img src="<?php echo plugins_url( 'images/icon-red.png', __FILE__); ?>"> MDC Private Message</h2>
			<div style="clear: left"></div>
			<div class="postbox-container" style="width: 100%">
				<div id="poststuff" class="metabox-holder">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="mdc_ts_opt_page" class="postbox ">
							<div title="Click to toggle" class="handlediv"><br></div>
							<h3 class="hndle"><span>Outbox</span></h3>
							<div class="inside">
							<?php if(current_user_can('administrator')): ?>
								<div class="option_page_right">
								<?php $this->option_page_right_adv();?>
								</div>
							<?php endif; ?>
								<div class="option_page_left">
								<?php echo $this->mdc_message_outbox();?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	<?php 
	}

	public function echo_mdc_message_settings(){ ?>
		<div class="wrap">
			<h2><img src="<?php echo plugins_url( 'images/icon-red.png', __FILE__); ?>"> MDC Private Message</h2>
			<div style="clear: left"></div>
			<div class="postbox-container" style="width: 100%">
				<div id="poststuff" class="metabox-holder">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="mdc_ts_opt_page" class="postbox ">
							<div title="Click to toggle" class="handlediv"><br></div>
							<h3 class="hndle"><span>Settings</span></h3>
							<div class="inside">
								<div class="option_page_right">
								<?php $this->option_page_right_adv();?>
								</div>
								<div class="option_page_left">
									<?php
									if(isset($_POST['mdc_update'])){
										update_option('mdc_show_delivery_time', $_POST['mdc_show_delivery_time']);
										update_option('mdc_msg_custom_css', $_POST['mdc_msg_custom_css']);
									?>
									<div class="updated settings-error mdc_settings_saved" id="setting-error-settings_updated"> 
										<p><strong>Settings saved.</strong></p>
									</div>
									<?php } ?>
									<form action="" method="post">
										<input type="hidden" name="mdc_update" />
										<table class="form-table">
											<tbody>
												<tr valign="top" class="enable_frontend">
													<th scope="row"><label for="mdc_msg_enable_frontend">Private Message in Frontend</label></th>
													<td><input type="checkbox" value="1" id="mdc_msg_enable_frontend" name="mdc_msg_enable_frontend" <?php if(get_option('mdc_msg_enable_frontend') == 1){echo "checked";}?> /><span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><a href="http://medhabi.com/product/mdc-private-message-pro/" target="_blank"><img src="<?php echo plugins_url('images/pro.png', __FILE__);?>"><small>Upgdare</small></a><br /><span class="hidden mdc_help">(Enable private message in front-end with shortcode.)</span></td>
												</tr>
												<tr valign="top" class="enable_frontend_option<?php if(get_option('mdc_msg_enable_frontend') != 1){echo " hidden";}?>">
													<th scope="row"><label for="mdc_msg_new_msg_page">Page for New Message</label></th>
													<td>
														<?php
														$args = array(
															'name'	=> 'mdc_msg_new_msg_page',
															'id'	=> 'mdc_msg_new_msg_page',
															'selected'	=>	get_option('mdc_msg_new_msg_page')
														);
														wp_dropdown_pages( $args ); ?>
														<span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><br /><span class="hidden mdc_help">(Create a page with shortcode <code>[mdc_message_new]</code> and choose this page for the dropdown.)</span>
													</td>
												</tr>
												<tr valign="top" class="enable_frontend_option<?php if(get_option('mdc_msg_enable_frontend') != 1){echo " hidden";}?>">
													<th scope="row"><label for="mdc_msg_inbox_page">Page for Inbox</label></th>
													<td>
														<?php
														$args = array(
															'name'	=> 'mdc_msg_inbox_page',
															'id'	=> 'mdc_msg_inbox_page',
															'selected'	=>	get_option('mdc_msg_inbox_page')
														);
														wp_dropdown_pages( $args ); ?>
														<span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><br /><span class="hidden mdc_help">(Create a page with shortcode <code>[mdc_message_inbox]</code> and choose this page for the dropdown.)</span>
													</td>
												</tr>
												<tr valign="top" class="enable_frontend_option<?php if(get_option('mdc_msg_enable_frontend') != 1){echo " hidden";}?>">
													<th scope="row"><label for="mdc_msg_outbox_page">Page for Outbox</label></th>
													<td>
														<?php
														$args = array(
															'name'	=> 'mdc_msg_outbox_page',
															'id'	=> 'mdc_msg_outbox_page',
															'selected'	=>	get_option('mdc_msg_outbox_page')
														);
														wp_dropdown_pages( $args ); ?>
														<span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><br /><span class="hidden mdc_help">(Create a page with shortcode <code>[mdc_message_outbox]</code> and choose this page for the dropdown.)</span>
													</td>
												</tr>
												<tr valign="top">
													<th scope="row"><label for="mdc_msg_enable_rich_editor">Enable Rich Editor</label></th>
													<td><input type="checkbox" value="1" id="mdc_msg_enable_rich_editor" name="mdc_msg_enable_rich_editor" <?php if(get_option('mdc_msg_enable_rich_editor') == 1){echo "checked";}?> /><span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><a href="http://medhabi.com/product/mdc-private-message-pro/" target="_blank"><img src="<?php echo plugins_url('images/pro.png', __FILE__);?>"><small>Upgdare</small></a><br /><span class="hidden mdc_help">(Enable rich text editor for new message.)</span></td>
												</tr>
												<tr valign="top" class="enable_rich_option">
													<th scope="row"><label for="mdc_msg_enable_media">Enable Media Button</label></th>
													<td><input type="checkbox" value="1" id="mdc_msg_enable_media" name="mdc_msg_enable_media" <?php if(get_option('mdc_msg_enable_media') == 1){echo "checked";}?> /><span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><a href="http://medhabi.com/product/mdc-private-message-pro/" target="_blank"><img src="<?php echo plugins_url('images/pro.png', __FILE__);?>"><small>Upgdare</small></a><br /><span class="hidden mdc_help">(Enable media uploader button and allow users to upload files.)</span></td>
												</tr>
												<tr valign="top" class="enable_rich_option">
													<th scope="row"><label for="mdc_msg_enable_tinymce">Enable TinyMCE</label></th>
													<td><input type="checkbox" value="1" id="mdc_msg_enable_tinymce" name="mdc_msg_enable_tinymce" <?php if(get_option('mdc_msg_enable_tinymce') == 1){echo "checked";}?> /><span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><a href="http://medhabi.com/product/mdc-private-message-pro/" target="_blank"><img src="<?php echo plugins_url('images/pro.png', __FILE__);?>"><small>Upgdare</small></a><br /><span class="hidden mdc_help">(If you want to show Sticky Bar, check this.)</span></td>
												</tr>
												<tr valign="top">
													<th scope="row"><label for="mdc_show_delivery_time">Show delivery time</label></th>
													<td><input type="checkbox" value="1" id="mdc_show_delivery_time" name="mdc_show_delivery_time" <?php if(get_option('mdc_show_delivery_time') == 1){echo "checked";}?> /><span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><br /><span class="hidden mdc_help">(Enable to show the delivery time of a sent message.)</span></td>
												</tr>
												<tr valign="top">
													<th scope="row"><label for="mdc_msg_custom_css">Custom CSS</label></th>
													<td>
														<textarea type="text" id="editor" class="css" name="mdc_msg_custom_css" style="height: 200px; width: 340px;"><?php if(get_option('mdc_msg_custom_css')){ echo get_option('mdc_msg_custom_css');}?></textarea>
														<span class="mdc_help_icon dashicons dashicons-editor-help" title="Help?"></span><br /><span class="hidden mdc_help">(If you want to add your own CSS.)</span>
													</td>
												</tr>
											</tbody>
										</table>
										<p class="submit">
											<input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit">
										</p>
										<div class="clear"></div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	<?php 
	}

	public function new_message_count(){
		global $wpdb;
		$msg_tbl = $wpdb->prefix."mdc_private_message";
		$current_user = get_current_user_id();
		$count = $wpdb->get_results(
					$wpdb->prepare( 
						"SELECT COUNT(*) AS total FROM $msg_tbl WHERE receiver = %d AND is_trash_rcvr != %d AND is_read = %d ORDER BY time_sent DESC", 
						$current_user,
						1,
						0
						)
					);
		$new_msg = ($count[0]->total > 0) ? " <span class='mdc_unseen_counter'>".$count[0]->total."</span>" : "";
		return $new_msg;
	}

	public function mdc_option_page(){

		$msging_cap = 'read';
		add_menu_page('Private Message', 'Messages'.$this->new_message_count(), $msging_cap, 'mdc-new-msg', array($this, 'echo_mdc_message_new'), plugins_url('/images/icon.png', __FILE__), '4.33');
		add_submenu_page('mdc-new-msg', 'New Message', 'New Message', $msging_cap, 'mdc-new-msg', array($this, 'echo_mdc_message_new'));
		add_submenu_page('mdc-new-msg', 'Inbox', 'Inbox'.$this->new_message_count(), $msging_cap, 'mdc-inbox', array($this, 'echo_mdc_message_inbox'));
		add_submenu_page('mdc-new-msg', 'Outbox', 'Outbox', $msging_cap, 'mdc-outbox', array($this, 'echo_mdc_message_outbox'));
		add_submenu_page('mdc-new-msg', 'Private Message Settings', 'Settings', 'administrator', 'mdc-msg-settings', array($this, 'echo_mdc_message_settings'));
	}

	public function option_page_right_adv(){
		$ads =	array(
					array(
						'title'	=>	'SEND MESSAGES FROM FRONT-END',
						'name'	=>	'MDC Private Message',
						'image'	=>	'icon-red.png',
						'link'	=>	'http://medhabi.com/items/mdc-private-message-pro/',
						'button' =>	'Get Now'
					),
					array(
						'title'	=>	'WANT TO DOWNLOAD YOUTUBE VIDEOS FROM POSTS AND PAGES?',
						'name'	=>	'MDC YouTube Downloader Pro',
						'image'	=>	'mark-read.png',
						'link'	=>	'http://medhabi.com/items/mdc-youtube-downloader-pro/',
						'button' =>	'Get Now'
					),
					array(
						'title'	=>	'WANT TO INSERT DOWNLOADBLE YOUTUBE VIDEOS INTO POSTS AND PAGES?',
						'name'	=>	'MDC YouTube Downloader Pro',
						'image'	=>	'mark-read.png',
						'link'	=>	'http://medhabi.com/items/mdc-youtube-downloader-pro/',
						'button' =>	'Get Now'
					),
					array(
						'title'	=>	'WORRIED OF STEALING PASSWORD? USE VIRTUAL KEYBOARD!',
						'name'	=>	'MDC Virtual Keyboard Pro',
						'image'	=>	'reply.png',
						'link'	=>	'http://medhabi.com/items/mdc-virtual-keyboard-pro/',
						'button' =>	'Get Now'
					)
			);
		$rand = (MDC_PRIVATE_MESSAGE_PRO) ? rand(1, (count($ads) - 1)) : rand(0, (count($ads) - 1));
		?>
		<div class="mdc_ts_dl_pro">
			<h3 class="mdc_ts_dl_pro_ttl"><?php echo $ads[$rand]['title']; ?></h3>
			<div class="pro_logo">
				<!-- <a href="<?php echo $ads[$rand]['link']; ?>" target="_blank"><img src="<?php echo plugins_url('images/'.$ads[$rand]['image'], __FILE__);?>"></a> -->
			</div>
			<h3 class="upgrade_today"><?php echo $ads[$rand]['name']; ?></h3>
			<div class="get_pro_div">
				<a href="<?php echo $ads[$rand]['link']; ?>" target="_blank"><button class="get_pro_btn"><?php echo $ads[$rand]['button']; ?></button></a>
				<hr />
				<a href="http://www.medhabi.com/" target="_blank"><img alt="MedhabiDotCom - One Stop Tech Solution" class="mdc_logo" src="http://www.medhabi.com/wp-content/uploads/2014/12/medhabidotcom.png">
				<i>www.medhabi.com</i></a>
			</div>
		</div>
	<?php
	}
}
$mdc_private_message = new MDC_Private_Message;