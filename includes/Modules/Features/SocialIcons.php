<?php
/**
 * Social Icons Module
 *
 * @package VLT Helper
 */

namespace VLT\Helper\Modules\Features;

use VLT\Helper\Modules\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social Icons Module
 *
 * Provides social icons functionality with Socicons font integration
 * Includes sharing capabilities and icon management
 */
class SocialIcons extends BaseModule {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'social_icons';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Shareable networks supported by Sharer.js
	 * Format: 'slug' => ['label' => 'Display Name', 'attrs' => ['attr1', 'attr2']]
	 *
	 * @var array
	 */
	const SHAREABLE_NETWORKS = [
		'twitter'      => [ 'label' => 'Twitter', 'attrs' => [ 'via', 'hashtags' ] ],
		'x'            => [ 'label' => 'X', 'attrs' => [ 'via', 'hashtags' ] ],
		'bluesky'      => [ 'label' => 'Bluesky', 'attrs' => [] ],
		'threads'      => [ 'label' => 'Threads', 'attrs' => [] ],
		'facebook'     => [ 'label' => 'Facebook', 'attrs' => [ 'hashtag' ] ],
		'linkedin'     => [ 'label' => 'LinkedIn', 'attrs' => [] ],
		'email'        => [ 'label' => 'E-Mail', 'attrs' => [ 'to', 'subject' ] ],
		'whatsapp'     => [ 'label' => 'WhatsApp', 'attrs' => [ 'to', 'web', 'description' ] ],
		'telegram'     => [ 'label' => 'Telegram', 'attrs' => [] ],
		'viber'        => [ 'label' => 'Viber', 'attrs' => [] ],
		'pinterest'    => [ 'label' => 'Pinterest', 'attrs' => [ 'image', 'description' ] ],
		'tumblr'       => [ 'label' => 'Tumblr', 'attrs' => [ 'caption', 'tags' ] ],
		'hackernews'   => [ 'label' => 'Hacker News', 'attrs' => [] ],
		'reddit'       => [ 'label' => 'Reddit', 'attrs' => [] ],
		'vk'           => [ 'label' => 'VK', 'attrs' => [ 'image', 'caption' ] ],
		'buffer'       => [ 'label' => 'Buffer', 'attrs' => [ 'via', 'picture' ] ],
		'xing'         => [ 'label' => 'Xing', 'attrs' => [] ],
		'line'         => [ 'label' => 'Line', 'attrs' => [] ],
		'instapaper'   => [ 'label' => 'Instapaper', 'attrs' => [ 'description' ] ],
		'pocket'       => [ 'label' => 'Pocket', 'attrs' => [] ],
		'flipboard'    => [ 'label' => 'Flipboard', 'attrs' => [] ],
		'weibo'        => [ 'label' => 'Weibo', 'attrs' => [ 'image', 'appkey', 'ralateuid' ] ],
		'blogger'      => [ 'label' => 'Blogger', 'attrs' => [ 'description' ] ],
		'baidu'        => [ 'label' => 'Baidu', 'attrs' => [] ],
		'okru'         => [ 'label' => 'Ok.ru', 'attrs' => [] ],
		'evernote'     => [ 'label' => 'Evernote', 'attrs' => [] ],
		'skype'        => [ 'label' => 'Skype', 'attrs' => [] ],
		'trello'       => [ 'label' => 'Trello', 'attrs' => [ 'description' ] ],
		'diaspora'     => [ 'label' => 'Diaspora', 'attrs' => [ 'description' ] ],
	];

	/**
	 * Register module
	 */
	public function register() {
		// Enqueue Socicons font and Sharer.js library
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Add social contact methods to user profile
		add_filter( 'user_contactmethods', [ $this, 'add_contact_methods' ] );
	}

	/**
	 * Enqueue CSS and JS assets
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'vlt-font-socicons' );
		wp_enqueue_script( 'vlt-sharer' );
	}

	/**
	 * Get social icons list
	 *
	 * Returns all available social icons from Socicons font.
	 * Format: 'socicon-{network}' => 'Display Name'
	 *
	 * @return array Array of social icons.
	 */
	public static function get_social_icons() {
		$social_icons = [
			'socicon-internet'       => esc_html__( 'Internet', 'vlt-helper' ),
			'socicon-moddb'          => esc_html__( 'Moddb', 'vlt-helper' ),
			'socicon-indiedb'        => esc_html__( 'Indiedb', 'vlt-helper' ),
			'socicon-traxsource'     => esc_html__( 'Traxsource', 'vlt-helper' ),
			'socicon-gamefor'        => esc_html__( 'Gamefor', 'vlt-helper' ),
			'socicon-pixiv'          => esc_html__( 'Pixiv', 'vlt-helper' ),
			'socicon-myanimelist'    => esc_html__( 'Myanimelist', 'vlt-helper' ),
			'socicon-blackberry'     => esc_html__( 'Blackberry', 'vlt-helper' ),
			'socicon-wickr'          => esc_html__( 'Wickr', 'vlt-helper' ),
			'socicon-spip'           => esc_html__( 'Spip', 'vlt-helper' ),
			'socicon-napster'        => esc_html__( 'Napster', 'vlt-helper' ),
			'socicon-beatport'       => esc_html__( 'Beatport', 'vlt-helper' ),
			'socicon-hackerone'      => esc_html__( 'Hackerone', 'vlt-helper' ),
			'socicon-hackernews'     => esc_html__( 'Hackernews', 'vlt-helper' ),
			'socicon-smashwords'     => esc_html__( 'Smashwords', 'vlt-helper' ),
			'socicon-kobo'           => esc_html__( 'Kobo', 'vlt-helper' ),
			'socicon-bookbub'        => esc_html__( 'Bookbub', 'vlt-helper' ),
			'socicon-mailru'         => esc_html__( 'Mailru', 'vlt-helper' ),
			'socicon-gitlab'         => esc_html__( 'Gitlab', 'vlt-helper' ),
			'socicon-instructables'  => esc_html__( 'Instructables', 'vlt-helper' ),
			'socicon-portfolio'      => esc_html__( 'Portfolio', 'vlt-helper' ),
			'socicon-codered'        => esc_html__( 'Codered', 'vlt-helper' ),
			'socicon-origin'         => esc_html__( 'Origin', 'vlt-helper' ),
			'socicon-nextdoor'       => esc_html__( 'Nextdoor', 'vlt-helper' ),
			'socicon-udemy'          => esc_html__( 'Udemy', 'vlt-helper' ),
			'socicon-livemaster'     => esc_html__( 'Livemaster', 'vlt-helper' ),
			'socicon-crunchbase'     => esc_html__( 'Crunchbase', 'vlt-helper' ),
			'socicon-homefy'         => esc_html__( 'Homefy', 'vlt-helper' ),
			'socicon-calendly'       => esc_html__( 'Calendly', 'vlt-helper' ),
			'socicon-realtor'        => esc_html__( 'Realtor', 'vlt-helper' ),
			'socicon-tidal'          => esc_html__( 'Tidal', 'vlt-helper' ),
			'socicon-qobuz'          => esc_html__( 'Qobuz', 'vlt-helper' ),
			'socicon-natgeo'         => esc_html__( 'Natgeo', 'vlt-helper' ),
			'socicon-mastodon'       => esc_html__( 'Mastodon', 'vlt-helper' ),
			'socicon-unsplash'       => esc_html__( 'Unsplash', 'vlt-helper' ),
			'socicon-homeadvisor'    => esc_html__( 'Homeadvisor', 'vlt-helper' ),
			'socicon-angieslist'     => esc_html__( 'Angieslist', 'vlt-helper' ),
			'socicon-codepen'        => esc_html__( 'Codepen', 'vlt-helper' ),
			'socicon-slack'          => esc_html__( 'Slack', 'vlt-helper' ),
			'socicon-openaigym'      => esc_html__( 'Openaigym', 'vlt-helper' ),
			'socicon-logmein'        => esc_html__( 'Logmein', 'vlt-helper' ),
			'socicon-fiverr'         => esc_html__( 'Fiverr', 'vlt-helper' ),
			'socicon-gotomeeting'    => esc_html__( 'Gotomeeting', 'vlt-helper' ),
			'socicon-aliexpress'     => esc_html__( 'Aliexpress', 'vlt-helper' ),
			'socicon-guru'           => esc_html__( 'Guru', 'vlt-helper' ),
			'socicon-appstore'       => esc_html__( 'Appstore', 'vlt-helper' ),
			'socicon-homes'          => esc_html__( 'Homes', 'vlt-helper' ),
			'socicon-zoom'           => esc_html__( 'Zoom', 'vlt-helper' ),
			'socicon-alibaba'        => esc_html__( 'Alibaba', 'vlt-helper' ),
			'socicon-craigslist'     => esc_html__( 'Craigslist', 'vlt-helper' ),
			'socicon-wix'            => esc_html__( 'Wix', 'vlt-helper' ),
			'socicon-redfin'         => esc_html__( 'Redfin', 'vlt-helper' ),
			'socicon-googlecalendar' => esc_html__( 'Googlecalendar', 'vlt-helper' ),
			'socicon-shopify'        => esc_html__( 'Shopify', 'vlt-helper' ),
			'socicon-freelancer'     => esc_html__( 'Freelancer', 'vlt-helper' ),
			'socicon-seedrs'         => esc_html__( 'Seedrs', 'vlt-helper' ),
			'socicon-bing'           => esc_html__( 'Bing', 'vlt-helper' ),
			'socicon-doodle'         => esc_html__( 'Doodle', 'vlt-helper' ),
			'socicon-bonanza'        => esc_html__( 'Bonanza', 'vlt-helper' ),
			'socicon-squarespace'    => esc_html__( 'Squarespace', 'vlt-helper' ),
			'socicon-toptal'         => esc_html__( 'Toptal', 'vlt-helper' ),
			'socicon-gust'           => esc_html__( 'Gust', 'vlt-helper' ),
			'socicon-ask'            => esc_html__( 'Ask', 'vlt-helper' ),
			'socicon-trulia'         => esc_html__( 'Trulia', 'vlt-helper' ),
			'socicon-loomly'         => esc_html__( 'Loomly', 'vlt-helper' ),
			'socicon-ghost'          => esc_html__( 'Ghost', 'vlt-helper' ),
			'socicon-upwork'         => esc_html__( 'Upwork', 'vlt-helper' ),
			'socicon-fundable'       => esc_html__( 'Fundable', 'vlt-helper' ),
			'socicon-booking'        => esc_html__( 'Booking', 'vlt-helper' ),
			'socicon-googlemaps'     => esc_html__( 'Googlemaps', 'vlt-helper' ),
			'socicon-zillow'         => esc_html__( 'Zillow', 'vlt-helper' ),
			'socicon-niconico'       => esc_html__( 'Niconico', 'vlt-helper' ),
			'socicon-toneden'        => esc_html__( 'Toneden', 'vlt-helper' ),
			'socicon-augment'        => esc_html__( 'Augment', 'vlt-helper' ),
			'socicon-bitbucket'      => esc_html__( 'Bitbucket', 'vlt-helper' ),
			'socicon-fyuse'          => esc_html__( 'Fyuse', 'vlt-helper' ),
			'socicon-yt-gaming'      => esc_html__( 'Yt-gaming', 'vlt-helper' ),
			'socicon-sketchfab'      => esc_html__( 'Sketchfab', 'vlt-helper' ),
			'socicon-mobcrush'       => esc_html__( 'Mobcrush', 'vlt-helper' ),
			'socicon-microsoft'      => esc_html__( 'Microsoft', 'vlt-helper' ),
			'socicon-pandora'        => esc_html__( 'Pandora', 'vlt-helper' ),
			'socicon-messenger'      => esc_html__( 'Messenger', 'vlt-helper' ),
			'socicon-gamewisp'       => esc_html__( 'Gamewisp', 'vlt-helper' ),
			'socicon-bloglovin'      => esc_html__( 'Bloglovin', 'vlt-helper' ),
			'socicon-tunein'         => esc_html__( 'Tunein', 'vlt-helper' ),
			'socicon-gamejolt'       => esc_html__( 'Gamejolt', 'vlt-helper' ),
			'socicon-trello'         => esc_html__( 'Trello', 'vlt-helper' ),
			'socicon-spreadshirt'    => esc_html__( 'Spreadshirt', 'vlt-helper' ),
			'socicon-500px'          => esc_html__( '500px', 'vlt-helper' ),
			'socicon-8tracks'        => esc_html__( '8tracks', 'vlt-helper' ),
			'socicon-airbnb'         => esc_html__( 'Airbnb', 'vlt-helper' ),
			'socicon-alliance'       => esc_html__( 'Alliance', 'vlt-helper' ),
			'socicon-amazon'         => esc_html__( 'Amazon', 'vlt-helper' ),
			'socicon-amplement'      => esc_html__( 'Amplement', 'vlt-helper' ),
			'socicon-android'        => esc_html__( 'Android', 'vlt-helper' ),
			'socicon-angellist'      => esc_html__( 'Angellist', 'vlt-helper' ),
			'socicon-apple'          => esc_html__( 'Apple', 'vlt-helper' ),
			'socicon-appnet'         => esc_html__( 'Appnet', 'vlt-helper' ),
			'socicon-baidu'          => esc_html__( 'Baidu', 'vlt-helper' ),
			'socicon-bandcamp'       => esc_html__( 'Bandcamp', 'vlt-helper' ),
			'socicon-battlenet'      => esc_html__( 'Battlenet', 'vlt-helper' ),
			'socicon-mixer'          => esc_html__( 'Mixer', 'vlt-helper' ),
			'socicon-bebee'          => esc_html__( 'Bebee', 'vlt-helper' ),
			'socicon-bebo'           => esc_html__( 'Bebo', 'vlt-helper' ),
			'socicon-behance'        => esc_html__( 'Behance', 'vlt-helper' ),
			'socicon-blizzard'       => esc_html__( 'Blizzard', 'vlt-helper' ),
			'socicon-blogger'        => esc_html__( 'Blogger', 'vlt-helper' ),
			'socicon-buffer'         => esc_html__( 'Buffer', 'vlt-helper' ),
			'socicon-chrome'         => esc_html__( 'Chrome', 'vlt-helper' ),
			'socicon-coderwall'      => esc_html__( 'Coderwall', 'vlt-helper' ),
			'socicon-curse'          => esc_html__( 'Curse', 'vlt-helper' ),
			'socicon-dailymotion'    => esc_html__( 'Dailymotion', 'vlt-helper' ),
			'socicon-deezer'         => esc_html__( 'Deezer', 'vlt-helper' ),
			'socicon-delicious'      => esc_html__( 'Delicious', 'vlt-helper' ),
			'socicon-deviantart'     => esc_html__( 'Deviantart', 'vlt-helper' ),
			'socicon-diablo'         => esc_html__( 'Diablo', 'vlt-helper' ),
			'socicon-digg'           => esc_html__( 'Digg', 'vlt-helper' ),
			'socicon-discord'        => esc_html__( 'Discord', 'vlt-helper' ),
			'socicon-disqus'         => esc_html__( 'Disqus', 'vlt-helper' ),
			'socicon-douban'         => esc_html__( 'Douban', 'vlt-helper' ),
			'socicon-draugiem'       => esc_html__( 'Draugiem', 'vlt-helper' ),
			'socicon-dribbble'       => esc_html__( 'Dribbble', 'vlt-helper' ),
			'socicon-drupal'         => esc_html__( 'Drupal', 'vlt-helper' ),
			'socicon-ebay'           => esc_html__( 'Ebay', 'vlt-helper' ),
			'socicon-ello'           => esc_html__( 'Ello', 'vlt-helper' ),
			'socicon-endomodo'       => esc_html__( 'Endomodo', 'vlt-helper' ),
			'socicon-envato'         => esc_html__( 'Envato', 'vlt-helper' ),
			'socicon-etsy'           => esc_html__( 'Etsy', 'vlt-helper' ),
			'socicon-facebook'       => esc_html__( 'Facebook', 'vlt-helper' ),
			'socicon-feedburner'     => esc_html__( 'Feedburner', 'vlt-helper' ),
			'socicon-filmweb'        => esc_html__( 'Filmweb', 'vlt-helper' ),
			'socicon-firefox'        => esc_html__( 'Firefox', 'vlt-helper' ),
			'socicon-flattr'         => esc_html__( 'Flattr', 'vlt-helper' ),
			'socicon-flickr'         => esc_html__( 'Flickr', 'vlt-helper' ),
			'socicon-formulr'        => esc_html__( 'Formulr', 'vlt-helper' ),
			'socicon-forrst'         => esc_html__( 'Forrst', 'vlt-helper' ),
			'socicon-foursquare'     => esc_html__( 'Foursquare', 'vlt-helper' ),
			'socicon-friendfeed'     => esc_html__( 'Friendfeed', 'vlt-helper' ),
			'socicon-github'         => esc_html__( 'Github', 'vlt-helper' ),
			'socicon-goodreads'      => esc_html__( 'Goodreads', 'vlt-helper' ),
			'socicon-google'         => esc_html__( 'Google', 'vlt-helper' ),
			'socicon-googlescholar'  => esc_html__( 'Googlescholar', 'vlt-helper' ),
			'socicon-googlegroups'   => esc_html__( 'Googlegroups', 'vlt-helper' ),
			'socicon-googlephotos'   => esc_html__( 'Googlephotos', 'vlt-helper' ),
			'socicon-googleplus'     => esc_html__( 'Googleplus', 'vlt-helper' ),
			'socicon-grooveshark'    => esc_html__( 'Grooveshark', 'vlt-helper' ),
			'socicon-hackerrank'     => esc_html__( 'Hackerrank', 'vlt-helper' ),
			'socicon-hearthstone'    => esc_html__( 'Hearthstone', 'vlt-helper' ),
			'socicon-hellocoton'     => esc_html__( 'Hellocoton', 'vlt-helper' ),
			'socicon-heroes'         => esc_html__( 'Heroes', 'vlt-helper' ),
			'socicon-smashcast'      => esc_html__( 'Smashcast', 'vlt-helper' ),
			'socicon-horde'          => esc_html__( 'Horde', 'vlt-helper' ),
			'socicon-houzz'          => esc_html__( 'Houzz', 'vlt-helper' ),
			'socicon-icq'            => esc_html__( 'Icq', 'vlt-helper' ),
			'socicon-identica'       => esc_html__( 'Identica', 'vlt-helper' ),
			'socicon-imdb'           => esc_html__( 'Imdb', 'vlt-helper' ),
			'socicon-instagram'      => esc_html__( 'Instagram', 'vlt-helper' ),
			'socicon-issuu'          => esc_html__( 'Issuu', 'vlt-helper' ),
			'socicon-istock'         => esc_html__( 'Istock', 'vlt-helper' ),
			'socicon-itunes'         => esc_html__( 'Itunes', 'vlt-helper' ),
			'socicon-keybase'        => esc_html__( 'Keybase', 'vlt-helper' ),
			'socicon-lanyrd'         => esc_html__( 'Lanyrd', 'vlt-helper' ),
			'socicon-lastfm'         => esc_html__( 'Lastfm', 'vlt-helper' ),
			'socicon-line'           => esc_html__( 'Line', 'vlt-helper' ),
			'socicon-linkedin'       => esc_html__( 'Linkedin', 'vlt-helper' ),
			'socicon-livejournal'    => esc_html__( 'Livejournal', 'vlt-helper' ),
			'socicon-lyft'           => esc_html__( 'Lyft', 'vlt-helper' ),
			'socicon-macos'          => esc_html__( 'Macos', 'vlt-helper' ),
			'socicon-email'          => esc_html__( 'E-Mail', 'vlt-helper' ),
			'socicon-medium'         => esc_html__( 'Medium', 'vlt-helper' ),
			'socicon-meetup'         => esc_html__( 'Meetup', 'vlt-helper' ),
			'socicon-mixcloud'       => esc_html__( 'Mixcloud', 'vlt-helper' ),
			'socicon-modelmayhem'    => esc_html__( 'Modelmayhem', 'vlt-helper' ),
			'socicon-mumble'         => esc_html__( 'Mumble', 'vlt-helper' ),
			'socicon-myspace'        => esc_html__( 'Myspace', 'vlt-helper' ),
			'socicon-newsvine'       => esc_html__( 'Newsvine', 'vlt-helper' ),
			'socicon-nintendo'       => esc_html__( 'Nintendo', 'vlt-helper' ),
			'socicon-npm'            => esc_html__( 'Npm', 'vlt-helper' ),
			'socicon-okru'           => esc_html__( 'Ok.ru', 'vlt-helper' ),
			'socicon-openid'         => esc_html__( 'Openid', 'vlt-helper' ),
			'socicon-opera'          => esc_html__( 'Opera', 'vlt-helper' ),
			'socicon-outlook'        => esc_html__( 'Outlook', 'vlt-helper' ),
			'socicon-overwatch'      => esc_html__( 'Overwatch', 'vlt-helper' ),
			'socicon-patreon'        => esc_html__( 'Patreon', 'vlt-helper' ),
			'socicon-paypal'         => esc_html__( 'Paypal', 'vlt-helper' ),
			'socicon-periscope'      => esc_html__( 'Periscope', 'vlt-helper' ),
			'socicon-persona'        => esc_html__( 'Persona', 'vlt-helper' ),
			'socicon-pinterest'      => esc_html__( 'Pinterest', 'vlt-helper' ),
			'socicon-play'           => esc_html__( 'Play', 'vlt-helper' ),
			'socicon-player'         => esc_html__( 'Player', 'vlt-helper' ),
			'socicon-playstation'    => esc_html__( 'Playstation', 'vlt-helper' ),
			'socicon-pocket'         => esc_html__( 'Pocket', 'vlt-helper' ),
			'socicon-qq'             => esc_html__( 'Qq', 'vlt-helper' ),
			'socicon-quora'          => esc_html__( 'Quora', 'vlt-helper' ),
			'socicon-raidcall'       => esc_html__( 'Raidcall', 'vlt-helper' ),
			'socicon-ravelry'        => esc_html__( 'Ravelry', 'vlt-helper' ),
			'socicon-reddit'         => esc_html__( 'Reddit', 'vlt-helper' ),
			'socicon-renren'         => esc_html__( 'Renren', 'vlt-helper' ),
			'socicon-researchgate'   => esc_html__( 'Researchgate', 'vlt-helper' ),
			'socicon-residentadvisor' => esc_html__( 'Residentadvisor', 'vlt-helper' ),
			'socicon-reverbnation'   => esc_html__( 'Reverbnation', 'vlt-helper' ),
			'socicon-rss'            => esc_html__( 'Rss', 'vlt-helper' ),
			'socicon-sharethis'      => esc_html__( 'Sharethis', 'vlt-helper' ),
			'socicon-skype'          => esc_html__( 'Skype', 'vlt-helper' ),
			'socicon-slideshare'     => esc_html__( 'Slideshare', 'vlt-helper' ),
			'socicon-smugmug'        => esc_html__( 'Smugmug', 'vlt-helper' ),
			'socicon-snapchat'       => esc_html__( 'Snapchat', 'vlt-helper' ),
			'socicon-songkick'       => esc_html__( 'Songkick', 'vlt-helper' ),
			'socicon-soundcloud'     => esc_html__( 'Soundcloud', 'vlt-helper' ),
			'socicon-spotify'        => esc_html__( 'Spotify', 'vlt-helper' ),
			'socicon-stackexchange'  => esc_html__( 'Stackexchange', 'vlt-helper' ),
			'socicon-stackoverflow'  => esc_html__( 'Stackoverflow', 'vlt-helper' ),
			'socicon-starcraft'      => esc_html__( 'Starcraft', 'vlt-helper' ),
			'socicon-stayfriends'    => esc_html__( 'Stayfriends', 'vlt-helper' ),
			'socicon-steam'          => esc_html__( 'Steam', 'vlt-helper' ),
			'socicon-storehouse'     => esc_html__( 'Storehouse', 'vlt-helper' ),
			'socicon-strava'         => esc_html__( 'Strava', 'vlt-helper' ),
			'socicon-streamjar'      => esc_html__( 'Streamjar', 'vlt-helper' ),
			'socicon-stumbleupon'    => esc_html__( 'Stumbleupon', 'vlt-helper' ),
			'socicon-swarm'          => esc_html__( 'Swarm', 'vlt-helper' ),
			'socicon-teamspeak'      => esc_html__( 'Teamspeak', 'vlt-helper' ),
			'socicon-teamviewer'     => esc_html__( 'Teamviewer', 'vlt-helper' ),
			'socicon-technorati'     => esc_html__( 'Technorati', 'vlt-helper' ),
			'socicon-telegram'       => esc_html__( 'Telegram', 'vlt-helper' ),
			'socicon-tripadvisor'    => esc_html__( 'Tripadvisor', 'vlt-helper' ),
			'socicon-tripit'         => esc_html__( 'Tripit', 'vlt-helper' ),
			'socicon-triplej'        => esc_html__( 'Triplej', 'vlt-helper' ),
			'socicon-tiktok'         => esc_html__( 'TikTok', 'vlt-helper' ),
			'socicon-threads'        => esc_html__( 'Threads', 'vlt-helper' ),
			'socicon-tumblr'         => esc_html__( 'Tumblr', 'vlt-helper' ),
			'socicon-twitch'         => esc_html__( 'Twitch', 'vlt-helper' ),
			'socicon-twitter'        => esc_html__( 'Twitter', 'vlt-helper' ),
			'socicon-uber'           => esc_html__( 'Uber', 'vlt-helper' ),
			'socicon-ventrilo'       => esc_html__( 'Ventrilo', 'vlt-helper' ),
			'socicon-viadeo'         => esc_html__( 'Viadeo', 'vlt-helper' ),
			'socicon-viber'          => esc_html__( 'Viber', 'vlt-helper' ),
			'socicon-viewbug'        => esc_html__( 'Viewbug', 'vlt-helper' ),
			'socicon-vimeo'          => esc_html__( 'Vimeo', 'vlt-helper' ),
			'socicon-vine'           => esc_html__( 'Vine', 'vlt-helper' ),
			'socicon-vk'             => esc_html__( 'VK', 'vlt-helper' ),
			'socicon-warcraft'       => esc_html__( 'Warcraft', 'vlt-helper' ),
			'socicon-wechat'         => esc_html__( 'Wechat', 'vlt-helper' ),
			'socicon-weibo'          => esc_html__( 'Weibo', 'vlt-helper' ),
			'socicon-whatsapp'       => esc_html__( 'Whatsapp', 'vlt-helper' ),
			'socicon-wikipedia'      => esc_html__( 'Wikipedia', 'vlt-helper' ),
			'socicon-windows'        => esc_html__( 'Windows', 'vlt-helper' ),
			'socicon-wordpress'      => esc_html__( 'Wordpress', 'vlt-helper' ),
			'socicon-wykop'          => esc_html__( 'Wykop', 'vlt-helper' ),
			'socicon-xbox'           => esc_html__( 'Xbox', 'vlt-helper' ),
			'socicon-xing'           => esc_html__( 'Xing', 'vlt-helper' ),
			'socicon-yahoo'          => esc_html__( 'Yahoo', 'vlt-helper' ),
			'socicon-yammer'         => esc_html__( 'Yammer', 'vlt-helper' ),
			'socicon-yandex'         => esc_html__( 'Yandex', 'vlt-helper' ),
			'socicon-yelp'           => esc_html__( 'Yelp', 'vlt-helper' ),
			'socicon-younow'         => esc_html__( 'Younow', 'vlt-helper' ),
			'socicon-youtube'        => esc_html__( 'Youtube', 'vlt-helper' ),
			'socicon-zapier'         => esc_html__( 'Zapier', 'vlt-helper' ),
			'socicon-zerply'         => esc_html__( 'Zerply', 'vlt-helper' ),
			'socicon-zomato'         => esc_html__( 'Zomato', 'vlt-helper' ),
			'socicon-zynga'          => esc_html__( 'Zynga', 'vlt-helper' ),
		];

		return apply_filters( 'vlt_helper_social_icons', $social_icons );
	}

	/**
	 * Build sharer data attributes
	 *
	 * Filters attributes based on network-specific allowed attributes from SHAREABLE_NETWORKS constant.
	 * Common attributes (title, url, width, height, link, blank) are always allowed.
	 *
	 * @param string $slug  Social network slug (e.g. 'facebook', 'twitter').
	 * @param array  $attrs Attributes array.
	 * @return array Filtered data attributes.
	 */
	public static function build_sharer_data_attrs( $slug, $attrs ) {
		// Remove 'socicon-' prefix if present
		$slug = str_replace( 'socicon-', '', $slug );

		// Common attributes allowed for all networks
		$common_attrs = [ 'title', 'url', 'width', 'height', 'link', 'blank' ];

		// Get network-specific attributes from constant
		$network_attrs = self::SHAREABLE_NETWORKS[ $slug ]['attrs'] ?? [];

		// Merge common and network-specific attributes
		$allowed_attrs = array_merge( $common_attrs, $network_attrs );

		// Build output array
		$out = [ 'sharer' => $slug ];

		foreach ( $attrs as $key => $val ) {
			$key = strtolower( $key );

			// Skip empty values
			if ( $val === null || $val === '' ) {
				continue;
			}

			// Add only allowed attributes
			if ( in_array( $key, $allowed_attrs, true ) ) {
				$out[ $key ] = $val;
			}
		}

		return array_filter( $out );
	}

	/**
	 * Get post sharing data
	 *
	 * @param int|null $post_id Post ID (uses current post if not provided).
	 * @return array Post sharing data.
	 */
	public static function get_post_share_data( $post_id = null ) {
		$post_id = $post_id ?: get_the_ID();

		$data = [];
		$data['url']         = get_permalink( $post_id );
		$data['title']       = get_the_title( $post_id );
		$data['image']       = get_the_post_thumbnail_url( $post_id, 'full' );
		$data['description'] = get_the_excerpt( $post_id );

		$data['to']      = get_bloginfo( 'admin_email' );
		$data['subject'] = get_bloginfo( 'name' ) . ' | ' . get_bloginfo( 'description' );

		// Get post tags
		$post_tags = get_the_tags( $post_id );
		if ( $post_tags ) {
			$tag_names = wp_list_pluck( $post_tags, 'name' );
			$data['tags'] = implode( ', ', $tag_names );
			$data['tag']  = reset( $tag_names );
		}

		$data['via']      = get_bloginfo( 'name' );
		$data['caption']  = get_the_excerpt( $post_id );
		$data['hashtags'] = ! empty( $data['tags'] ) ? str_replace( ' ', '', $data['tags'] ) : '';

		return apply_filters( 'vlt_helper_post_share_data', array_filter( $data ), $post_id );
	}

	/**
	 * Add social contact methods to user profile
	 *
	 * @param array $contactmethods Existing contact methods.
	 * @return array Modified contact methods.
	 */
	public function add_contact_methods( $contactmethods ) {
		$contactmethods['facebook']  = esc_html__( 'Facebook URL', 'vlt-helper' );
		$contactmethods['instagram'] = esc_html__( 'Instagram URL', 'vlt-helper' );
		$contactmethods['twitter']   = esc_html__( 'Twitter URL', 'vlt-helper' );
		$contactmethods['linkedin']  = esc_html__( 'LinkedIn URL', 'vlt-helper' );
		$contactmethods['youtube']   = esc_html__( 'YouTube URL', 'vlt-helper' );
		$contactmethods['pinterest'] = esc_html__( 'Pinterest URL', 'vlt-helper' );
		$contactmethods['tiktok']    = esc_html__( 'TikTok URL', 'vlt-helper' );
		$contactmethods['threads']   = esc_html__( 'Threads URL', 'vlt-helper' );

		return apply_filters( 'vlt_helper_user_contact_methods', $contactmethods );
	}

	/**
	 * Get post share buttons HTML
	 *
	 * @param int|null $post_id Post ID.
	 * @param string   $style   Button style (e.g. 'style-1', 'style-2').
	 * @return string Share buttons HTML.
	 */
	public static function get_post_share_buttons( $post_id = null, $style = 'style-1' ) {
		$post_id = $post_id ?: get_the_ID();

		// Get share data
		$share_data = self::get_post_share_data( $post_id );

		// Default social networks to display
		$enabled_socials = apply_filters( 'vlt_helper_post_share_socials', [
			'facebook',
			'twitter',
			'pinterest',
			'telegram',
		] );

		// If no socials are configured, return empty
		if ( empty( $enabled_socials ) ) {
			return '';
		}

		$output = '';

		// Loop through enabled socials and generate buttons
		foreach ( $enabled_socials as $slug ) {
			// Check if this network is supported by Sharer.js
			if ( ! isset( self::SHAREABLE_NETWORKS[ $slug ] ) ) {
				continue;
			}

			// Build data attributes for sharer
			$data_attrs = self::build_sharer_data_attrs( $slug, $share_data );

			// Build data attributes string
			$attrs_string = '';
			foreach ( $data_attrs as $key => $value ) {
				$attrs_string .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}

			// Generate button HTML
			$icon_class = 'socicon-' . $slug;
			$output .= sprintf(
				'<a href="javascript:;" class="vlt-social-icon vlt-social-icon--%s %s"%s><i class="%s"></i></a>',
				esc_attr( $style ),
				esc_attr( $slug ),
				$attrs_string,
				esc_attr( $icon_class )
			);
		}

		return apply_filters( 'vlt_helper_post_share_buttons', $output, $post_id, $style );
	}
}
