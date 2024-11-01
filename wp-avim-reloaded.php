<?php
/*
Plugin Name: WP-AVIM-Reloaded
Plugin URI: http://itscaro.me/read/wp-avim-reloaded/
Description: Bộ gõ tiếng Việt cho WordPress. A Quick AVIM Integration into your WordPress powered blog. Based on the idea of <a href="http://onetruebrace.com/2008/12/24/wp-avim-1-cham-1wp-avim-1-cham-1/">WP-AVIM by Quang Anh Do</a>. New version uses the new object-oriented AVIM-Reloaded to avoid javascript function collision. AVIM was written by <a href="http://sourceforge.net/projects/rhos">Hieu Tran Dang</a> and AVIM-Reloaded was modified by Minh Quan TRAN.
Version: 1.5.0
Author: Minh-Quan Tran (itscaro)
Author URI: http://itscaro.me/donate/
*/

/**
 *  Copyright (C) 2009 Minh Quan TRAN contact@itscaro.me
 *	Website: http://itscaro.me
 *
 *	This file is part of WP-AVIM-Reloaded (this software).
 *
 *	Redistribution and use of WP-AVIM-Reloaded, with or without modification,
 *  are permitted provided that the following conditions are met:
 *		1. Redistributions of this software must retain the above copyright
 *         notice, this list of conditions and the following disclaimer.
 *		2. You must not claim that you or any other third party is the author
 *		   of this software in any way.
 *
 *  WP-AVIM-Reloaded is also distributed under the terms of the GNU
 *  General Public License as published by the Free Software Foundation,
 *  version 3 of the License, or any later version if there is at
 *  the time you redistribute this software.
 *
 *  WP-AVIM-Reloaded is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with WP-AVIM-Reloaded.  If not, see <http://www.gnu.org/licenses/>.
 */

class WP_AVIMR {
	public $options;

	public $version			= '1.4.6';
	public $name			= 'WP-AVIM-Reloaded';
	public $plugin_file		= 'wp-avim-reloaded/wp-avim-reloaded.php';
	public $options_name	= 'wp-avim-reloaded';
	public $default_options = array(
		'dbversion'			=> '1.3.1',
		'state' 			=> '1',
		'method'			=> '0',
		'methods'			=> array(1,1,0),
		'advanced' 			=> '1',
		'position'			=> 'bottom',
		'spell'				=> '1',
		'accent'			=> '1',
		'excludeID'			=> "['email','mail','password','passwd','pass','recaptcha_response_field']",
		'controlHidden'		=> '0',
		'controlInComment'	=> '1'
								);
}

$wp_avimr = new WP_AVIMR;

//Initialize options values
$wp_avimr->options = get_option($wp_avimr->options_name);
if (empty($wp_avimr->options) || !is_array($wp_avimr->options)) {
	update_option($wp_avimr->options_name, $wp_avimr->default_options);
	$wp_avimr->options = get_option($wp_avimr->options_name);
}

/**** CSS ****/
function wp_avimr_css() {
	echo
<<<CSS
<style>
	#message { background-color: #fffbcc; margin-left: 17px; margin-top: 17px; }
	#wp-avimr {	float:left;	margin-top:10px; padding:2%; }
	#wp-avimr ul li { display: inline; }
	#wp-avimr small, #AVIMRControl small { font-size: 0.8em; }
	#AVIMRControl, #AVIMRControlCustom { clear:both; display:block; overflow:hidden; margin: 0; padding:0; }
	#AVIMRControlCustom input { margin: 3px 2px 0 0; padding:0; width: 12px; height:12px; }
	#AVIMRControlCustom > span > span { margin: 5px 7px 0 0; padding:0; float: left; }
</style>
CSS;
}
/**** /CSS ****/

/**** ACTIONS ****/
if( is_admin() ) {
	add_action('admin_menu', 'wp_avimr');
	add_action('admin_footer', 'wp_avimr_update');
	add_action('admin_menu', 'wp_avimr_add_meta_box', 50);
	add_action('admin_footer', 'wp_avimr_script');
} else {
	add_action('wp_head', 'wp_avimr_css');
	add_action('comment_form', 'wp_avimr_control_comment', 1);
	add_action('wp_footer', 'wp_avimr_script',50);
}

/**** WP-ADMIN SETTINGS ****/
function wp_avimr() {
	global $wp_avimr,$hook_suffix;

	add_options_page($wp_avimr->name, $wp_avimr->name, 8, $wp_avimr->plugin_file, 'wp_avimr_options');
	add_filter('plugin_action_links', 'wp_avimr_plugin_action_link', 10, 4);
	function wp_avimr_plugin_action_link($action_links, $plugin_file, $plugin_data, $context) {
		global $wp_avimr;

		if ($plugin_data['Name'] == $wp_avimr->name) {
			$str = '<a href="' . wp_nonce_url('options-general.php?page=' . $plugin_file) . '" title="' . __('Cài đặt') . '" class="edit">' . __('Cài đặt') . '</a>';
			array_unshift($action_links, $str);
		}
		return $action_links;
	}
}

function wp_avimr_update() {
	global $wp_avimr;

	if ($_POST['wp_avimr_action'] != 'Y') {
		if(version_compare($wp_avimr->options['dbversion'], $wp_avimr->default_options['dbversion'], '<')) {
			echo '<div class="updated fade" id="message"><p>Bạn vừa cập nhật phiên bản mới của '.$wp_avimr->name.', <strong>hãy cập nhật cấu hình <a href="options-general.php?page='.$wp_avimr->plugin_file.'">tại đây</a>.</p></div>';
		}
	}
}

function wp_avimr_options() {
	global $wp_avimr;

	if ($_POST['wp_avimr_action'] == 'Y') {
		update_option($wp_avimr->options_name, '');
		//Process exclude IDs
		$tmp = explode(',',str_replace(' ','',$_POST['excludeID']));
		$_POST['excludeID'] = '[';
		foreach ($tmp as $v) {
			if(!empty($v))
				$_POST['excludeID'] .= "'".$v."',";
		}
		$_POST['excludeID'] = substr($_POST['excludeID'],0,-1);
		$_POST['excludeID'] .= ']';

		//Process posted values, set '' to default value
		if(!function_exists('is_not_empty')) {
			function is_not_empty ($element) {
				return !empty($element);
			}
		}
		$tmp = array_keys($wp_avimr->default_options);
		foreach($tmp as $a) {
			if(is_array($wp_avimr->default_options[$a])) {
				$tmp_ = $wp_avimr->default_options[$a];
				foreach($tmp_ as $k=>$v) {
					$wp_avimr->options[$a][$k] = (empty($_POST[$a][$k])) ? 0 : $_POST[$a][$k];
				}
			} else {
				$wp_avimr->options[$a] = (empty($_POST[$a])) ? 0 : $_POST[$a];
			}
		}

		update_option($wp_avimr->options_name, $wp_avimr->options);
		echo '<div class="updated fade" id="message"><p>Những thay đổi <strong>đã được lưu lại</strong>.</p></div>';
	} else {
		if(version_compare($wp_avimr->options['dbversion'], $wp_avimr->default_options['dbversion'], '<')) {
			echo '<div class="updated fade" id="message"><p>Nếu bạn thấy thông báo này mà không có lựa chọn nào cần thay đổi, bạn chỉ cần ấn "Lưu thay đổi".</p></div>';
		}
	}

	// Method by default Selector
	$i[$wp_avimr->options['method']] = 'selected';
	$j = array('Auto','Telex','VNI','VIQR','VIQR*');
	$method  = '<select name="method">';
	for($k=0;$k<5;$k++) {
		$method .= "<option value=\"$k\" $i[$k]>$j[$k]</option>";
	}
	$method .= '</select>';

	// Available Method Selector
	$j = array('Telex','VNI','VIQR');
	for($k=0;$k<3;$k++) {
		$methods .= "<input type=\"checkbox\" name=\"methods[$k]\" value=\"1\" ".(($wp_avimr->options['methods'][$k]) ? 'checked' : '')."/> $j[$k] ";
	}

	// Position Selector
	$i[$wp_avimr->options['position']] = 'selected';
	$j = array('top','bottom','custom');
	$position  = '<select name="position">';
	for($k=0;$k<3;$k++) {
		$position .= "<option value=\"$j[$k]\" {$i[$j[$k]]}>$j[$k]</option>";
	}
	$position .= '</select>';

	echo '<div class="wrap" id="wp-avimr">';
	echo '<h2>Cấu hình của WP-AVIM-Reloaded</h2><br/>';
	/* Links */
	echo '<p style="height:12px;">';
	echo '<span style="float:left;">';
	echo '<a class="button-primary" href="http://itscaro.me/read/wp-avim-reloaded/">Hỗ trợ</a>&nbsp;';
	echo '</span>';
	echo '<span style="float:right;">';
	echo '</span>';
	echo '</p><hr/>';
	/* /Links */
	echo '<form action="" method="post" class="themeform">';
	echo '<input type="hidden" id="wp_avimr_action" name="wp_avimr_action" value="Y" />';
	echo '<input type="hidden" id="dbversion" name="dbversion" value="'.$wp_avimr->default_options['dbversion'].'" />';
	echo '<table>';
	echo '<tr><td colspan="2"><h3>Cài đặt mặc định</h3></td></tr>';
	echo '<tr><td colspan="2"><small><i>Các thay đổi ở đây chỉ có hiệu lực với lần đầu tiên vào trang web của bạn, hoặc những người không sử dụng cookie.</i></small></td></tr>';
	echo '<tr><td>Bật bộ gõ</td><td><input type="checkbox" name="state" value="1" '; echo ($wp_avimr->options['state']) ? 'checked/></td></tr>' : '/></td></tr>';
	echo '<tr><td>Kiểu gõ mặc định</td><td>'.$method.'</td></tr>';
	echo '<tr><td>Kiểm tra chính tả</td><td><input type="checkbox" name="spell" value="1" '; echo ($wp_avimr->options['spell']) ? 'checked/></td></tr>' : '/></td></tr>';
	echo '<tr><td>Bỏ dấu kiểu cũ</td><td><input type="checkbox" name="accent" value="1" '; echo ($wp_avimr->options['accent']) ? 'checked/></td></tr>' : '/></td></tr>';
	echo '<tr><td>Không sử dụng bộ gõ cho các ô có ID sau</td><td><input type="text" id="text" size="50" name="excludeID" value="'.(str_replace(array("[","'","]"), '', $wp_avimr->options['excludeID'])).'"/><small><i> (Ngăn các các ID bởi dấu ",")</i></small></td></tr>';
	echo '<tr><td colspan="2"><h3>Hiển thị</h3></td></tr>';
	echo '<tr><td>Vị trí</td><td>'.$position.'</td></tr>';
	echo '<tr><td>Kiểu gõ hiện trong bảng điều khiển</td><td>'.$methods.'</td></tr>';
	echo '<tr><td>Hiện lựa chọn cho kiểm tra chính tả và kiểu bỏ dấu</td><td><input type="checkbox" name="advanced" value="1" '; echo ($wp_avimr->options['advanced']) ? 'checked/></td></tr>' : '/></td></tr>';
	echo '<tr><td>Sử dụng bảng điều khiển nhúng dưới khung gửi phản hồi</td><td><input type="checkbox" name="controlInComment" value="1" '; echo ($wp_avimr->options['controlInComment']) ? 'checked/></td></tr>' : '/></td></tr>';
	echo '<tr><td>Ẩn bảng điều khiển nổi</td><td><input type="checkbox" name="controlHidden" value="1" '.(($wp_avimr->options['controlHidden']) ? 'checked' : '').'/></td></tr>';
	echo '<tr><td colspan="2" align="center"><p class="submit"><input type="submit" value="Lưu thay đổi" /></p></td></tr>';
	echo '</table>';
	echo '</form>';
	echo '</div>';
}
/**** /WP-ADMIN SETTINGS ****/

/**** LAYOUT ****/
function wp_avimr_add_meta_box() {
	global $wp_version;

	if (isset($wp_version) && version_compare($wp_version, '2.5', '>=')) {
		add_meta_box('wp_avim', 'Bộ gõ AVIM-Reloaded', 'wp_avimr_control', 'post', 'side', 'high');
		add_meta_box('wp_avim', 'Bộ gõ AVIM-Reloaded', 'wp_avimr_control', 'page', 'side', 'high');
	}
}

function wp_avimr_script() {
	global $wp_avimr, $wp_query, $pagenow;

	//If we use custom control, the control is then hidden
	if (is_admin()) :
			$controlHidden = (in_array($pagenow, array('post.php','page.php','post-new.php','page-new.php'))) ? $wp_avimr->options['controlHidden'] : 0;
	elseif (is_singular() && comments_open($wp_query->queried_object->ID)) :
			$controlHidden = ($wp_avimr->options['controlInComment']) ? $wp_avimr->options['controlHidden'] : 0;
	else :
			$controlHidden = $wp_avimr->options['controlHidden'];
	endif;

	$url_prefix = (defined('WP_CONTENT_URL') && '' != WP_CONTENT_URL) ? WP_CONTENT_URL : get_bloginfo('wpurl').'/wp-content';
	$url_js 	= $url_prefix."/plugins/wp-avim-reloaded/avimr.js";
	$url_css 	= $url_prefix."/plugins/wp-avim-reloaded/avimr_{$wp_avimr->options['position']}.css";

	$methods = '';
	if($wp_avimr->options['methods'][0]) {
		$methods .= '+ \'<input id="avimr_telex" type="radio" name="AVIMMethod" onclick="AVIMObj.setMethod(1);" />TELEX\'';
	}
	if($wp_avimr->options['methods'][1]) {
		$methods .= '+ \'<input id="avimr_vni" type="radio" name="AVIMMethod" onclick="AVIMObj.setMethod(2);" />VNI\'';
	}
	if($wp_avimr->options['methods'][2]) {
		$methods .=
					'+ \'<input id="avimr_viqr" type="radio" name="AVIMMethod" onclick="AVIMObj.setMethod(3);" />VIQR\'
					 + \'<input id="avimr_viqr2" type="radio" name="AVIMMethod" onclick="AVIMObj.setMethod(4);" />VIQR*\'';
	}
	$advanced = '';
	if($wp_avimr->options['advanced']) {
		$advanced .=
					'+ \'<input type="checkbox" id="avimr_ckspell" onclick="AVIMObj.setSpell(this);" />Chính tả\'
					 + \'<input type="checkbox" id="avimr_daucu" onclick="AVIMObj.setDauCu(this);" />Kiểu cũ\'';
	}

	echo
<<<WP_AVIM_RELOADED
	<!-- WP-AVIM-Reloaded {$wp_avimr->version} -->
	<script type="text/javascript">
	var AVIMGlobalConfig = {
		method: {$wp_avimr->options['method']}, //Default input method: 0=AUTO, 1=TELEX, 2=VNI, 3=VIQR, 4=VIQR*
		onOff: {$wp_avimr->options['state']}, //Default state: 0=Off, 1=On
		ckSpell: {$wp_avimr->options['spell']}, //Default Spell Check: 0=Off, 1=On
		oldAccent: {$wp_avimr->options['accent']}, //0: New way (oa`, oe`, uy`), 1: The good old day (o`a, o`e, u`y)
		useCookie: 1, //Cookies: 0=Off, 1=On
		exclude: {$wp_avimr->options['excludeID']}, //IDs of the fields you DON'T want to let users type Vietnamese in
		useControl: 1, //Use built-in control panel: 0=Off, 1=On. If you turn this on, you will have a floating control panel
		controlCSS: "{$url_css}", //URL to avimr.css
		controlHiddable: 1, //Allow the control to hide?
		controlHidden: {$controlHidden} //Hide the control panel by default
	};
	//Set to true the methods which you want to be included in the AUTO method
	var AVIMAutoConfig = {
		telex: true, vni: true, viqr: false, viqrStar: false
	};

	var controlHTML = '<span id="AVIMRControl"><p class="AVIMRControl">'
					+ '<input id="avimr_auto" type="radio" name="AVIMMethod" onclick="AVIMObj.setMethod(0);" />Tự động'
					{$methods}
					+ '<input id="avimr_off" type="radio" name="AVIMMethod" onclick="AVIMObj.setMethod(-1);" />Tắt'
					+ '<span id="separator"></span>'
					{$advanced};

	</script>
	<script type="text/javascript" src="{$url_js}"></script>
	<noscript>Bật JavaScript để có thể gõ tiếng Việt có dấu</noscript>
	<!-- /WP-AVIM-Reloaded  {$wp_avimr->version} -->
WP_AVIM_RELOADED;
}

function wp_avimr_control() {
	global $wp_avimr;
?>
<div id="AVIMRControlCustom" style="height:40px;">
	<span style="float:left;padding-right:10px;">
		<span><label><input id="avimr_auto" onclick="AVIMObj.setMethod(0);" type="radio" name="AVIMMethod">Tự động</label></span><br/>
		<?php if($wp_avimr->options['methods'][0]) { ?>
			<span><label><input id="avimr_telex" onclick="AVIMObj.setMethod(1);" type="radio" name="AVIMMethod">TELEX</label></span>
		<?php } ?>
	</span>
	<span style="float:left;padding-right:10px;">
		<span><label><input id="avimr_off" onclick="AVIMObj.setMethod(-1);" type="radio" name="AVIMMethod">Tắt</label></span><br/>
		<?php if($wp_avimr->options['methods'][1]) { ?>
			<span><label><input id="avimr_vni" onclick="AVIMObj.setMethod(2);" type="radio" name="AVIMMethod">VNI</label></span>
		<?php } ?>
	</span>
	<?php if($wp_avimr->options['methods'][2]) { ?>
	<span style="float:left;padding-right:10px;">
		<span><label><input id="avimr_viqr" onclick="AVIMObj.setMethod(3);" type="radio" name="AVIMMethod">VIQR</label></span><br/>
		<span><label><input id="avimr_viqr2" onclick="AVIMObj.setMethod(4);" type="radio" name="AVIMMethod">VIQR*</label></span>
	</span>
	<?php } if($wp_avimr->options['advanced']) { ?>
	<span style="float:right;">
		<span><label><input id="avimr_ckspell" onclick="AVIMObj.setSpell(this);" type="checkbox" name="AVIMMethod">Kiểm tra chính tả</label></span><br/>
		<span><label><input id="avimr_daucu" onclick="AVIMObj.setDauCu(this);" type="checkbox" name="AVIMMethod">Bỏ dấu kiểu cũ</label></span>
	</span>
	<?php } ?>
</div>
<?php
}

function wp_avimr_control_comment() {
	global $wp_avimr;

	if($wp_avimr->options['controlInComment'])
	{
?>
<div id="AVIMRControlCustom">
	<span>
		<span><label><input id="avimr_auto" onclick="AVIMObj.setMethod(0);" type="radio" name="AVIMMethod">Tự động</label></span>
        <?php if($wp_avimr->options['methods'][0]) { ?>
		<span><label><input id="avimr_telex" onclick="AVIMObj.setMethod(1);" type="radio" name="AVIMMethod">TELEX</label></span>
        <?php } if($wp_avimr->options['methods'][1]) { ?>
		<span><label><input id="avimr_vni" onclick="AVIMObj.setMethod(2);" type="radio" name="AVIMMethod">VNI</label></span>
        <?php } if($wp_avimr->options['methods'][2]) { ?>
		<span><label><input id="avimr_viqr" onclick="AVIMObj.setMethod(3);" type="radio" name="AVIMMethod">VIQR</label></span>
		<span><label><input id="avimr_viqr2" onclick="AVIMObj.setMethod(4);" type="radio" name="AVIMMethod">VIQR*</label></span>
		<?php } ?>
		<span><label><input id="avimr_off" onclick="AVIMObj.setMethod(-1);" type="radio" name="AVIMMethod">Tắt</label></span>
	</span>
	<span style="float:right;">
		<?php if($wp_avimr->options['advanced']) { ?>
		<span><label><input id="avimr_ckspell" onclick="AVIMObj.setSpell(this);" type="checkbox" name="AVIMMethod">Kiểm tra chính tả</label></span>
		<span><label><input id="avimr_daucu" onclick="AVIMObj.setDauCu(this);" type="checkbox" name="AVIMMethod">Bỏ dấu kiểu cũ</label></span>
		<?php } ?>
		<p style="float:right;"><small>Bộ gõ AVIM-Reloaded</small></p>
	</span>
</div>
<?php
	}
}
?>