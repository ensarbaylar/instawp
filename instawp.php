<?php
/**
 * Plugin Name: InstaWp
 * Plugin URI: https://github.com/ensarbaylar/instawp
 * Description: You can create shortcodes via InstaWp to display images from Instagram.
 * Version: 0.1.0
 * Author: Ahmet Ensar BAYLAR
 * Author URI: http://www.ensarbaylar.com/
 * License: GPL3
 */

// Add Shortcode
add_shortcode( 'instawp', 'instawp_get_media' );
add_filter('widget_text', 'do_shortcode');

function instawp_get_media( $atts ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'hashtag' => '',
			'count' => '10',
		), $atts )
	);
	
	$media = instawp_searchHashTag($atts['hashtag'], $atts['count']);
	
	$result = '<div class="instawp-info-holder"><img src="'.plugins_url( 'img/icon-lg.png' , __FILE__ ).'" /><p>Post on Instagram with</p><p id="hashtag">#'.$atts['hashtag'].'</p></div>';
	$result .= '<div class="clearfix"></div>';
    $result .= '<div class="instawp-wrap">';
	$i = 0;
	foreach($media->data as $image){
		if ( $i % 4 == 0 ) {
			$result .= '<div class="clearfix"></div>';
		}
		$result .= '<div class="instagram-photo">';
        $result .= '<a href="http://instagram.com/'.$image->user->username.'"><img src="'.plugins_url( 'img/icon-sm.png' , __FILE__ ).'" class="ig-icon" />'.$image->user->username.'</a>';
        $result .= '<a href="'.$image->link.'" target="_blank"><img src="'.$image->images->standard_resolution->url.'" class="instawp-image" /></a>';
		$result .= '<p>#'.$atts['hashtag'].'</p>';
		$result .= '</div>';
		
		$i++;
	}
	if( isset($media->next_url ) && $i < $atts['count'] - 1){
		while($i < $atts['count']){
			$media = instawp_searchHashTag($atts['hashtag'], $atts['count'], $media->pagination->next_max_tag_id);
			foreach($media->data as $image){
				if( $i < $atts['count'] ){
					if ( $i % 4 == 0 ) {
						$result .= '<div class="clearfix"></div>';
					}
					$result .= '<div class="instagram-photo">';
					$result .= '<a href="http://instagram.com/'.$image->user->username.'"><img src="'.plugins_url( 'img/icon-sm.png' , __FILE__ ).'" class="ig-icon" />'.$image->user->username.'</a>';
					$result .= '<a href="'.$image->link.'" target="_blank"><img src="'.$image->images->standard_resolution->url.'" /></a>';
					$result .= '<p>#'.$atts['hashtag'].'</p>';
					$result .= '</div>';
				}
				$i++;
			}
		}
	}
	$result .= '</div><div class="clearfix"></div>';
	return $result;
}

// Instagram Api function to get the media results
function instawp_searchHashTag ( $name, $limit = 10, $max_id = 0) {
		
	$params = array('count' => $limit, 'max_tag_id' => $max_id);
	$add_params = '&' . http_build_query($params);

	
	$apiUrl = 'https://api.instagram.com/v1/tags/' . $name . '/media/recent?client_id=' . get_option('instawp-api-key') . $add_params;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiUrl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	
	$jsonData = curl_exec($ch);
	curl_close($ch);
	
	return json_decode($jsonData);
}

// Register Style sheet
function instawp_add_stylesheet() {
    // Respects SSL, Style.css is relative to the current file
    wp_register_style( 'prefix-style', plugins_url('style.css', __FILE__) );
    wp_enqueue_style( 'prefix-style' );
}

add_action( 'wp_enqueue_scripts', 'instawp_add_stylesheet' );

// Create Custom Plugin Settings Menu
add_action('admin_menu', 'instawp_create_menu');
function instawp_create_menu() {
	//create new top-level menu
	add_options_page('Instawp Settings', 'Instawp Settings', 'administrator', __FILE__, 'instawp_settings_page');
	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}

// Plugin Setting Menu
function instawp_settings_page() {
?>
<div class="wrap">
<h2>Instawp Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'instawp-api-settings' ); ?>
    <?php do_settings_sections( 'instawp-api-settings' ); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Api Key</th>
            <td><input type="text" name="instawp-api-key" value="<?php echo get_option('instawp-api-key')?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row">Api Secret</th>
            <td><input type="text" name="instawp-api-secret" value="<?php echo get_option('instawp-api-secret')?>" /></td>
        </tr>
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php 
}


function register_mysettings() {
	//register our settings
	register_setting( 'instawp-api-settings', 'instawp-api-key' );
	register_setting( 'instawp-api-settings', 'instawp-api-secret' );
}

?>
