<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

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
	const SHAREABLE_NETWORKS = array(
		'twitter'    => array(
			'label' => 'Twitter',
			'attrs' => array( 'via', 'hashtags' ),
		),
		'x'          => array(
			'label' => 'X',
			'attrs' => array( 'via', 'hashtags' ),
		),
		'bluesky'    => array(
			'label' => 'Bluesky',
			'attrs' => array(),
		),
		'threads'    => array(
			'label' => 'Threads',
			'attrs' => array(),
		),
		'facebook'   => array(
			'label' => 'Facebook',
			'attrs' => array( 'hashtag' ),
		),
		'linkedin'   => array(
			'label' => 'LinkedIn',
			'attrs' => array(),
		),
		'email'      => array(
			'label' => 'E-Mail',
			'attrs' => array( 'to', 'subject' ),
		),
		'whatsapp'   => array(
			'label' => 'WhatsApp',
			'attrs' => array( 'to', 'web', 'description' ),
		),
		'telegram'   => array(
			'label' => 'Telegram',
			'attrs' => array(),
		),
		'viber'      => array(
			'label' => 'Viber',
			'attrs' => array(),
		),
		'pinterest'  => array(
			'label' => 'Pinterest',
			'attrs' => array( 'image', 'description' ),
		),
		'tumblr'     => array(
			'label' => 'Tumblr',
			'attrs' => array( 'caption', 'tags' ),
		),
		'hackernews' => array(
			'label' => 'Hacker News',
			'attrs' => array(),
		),
		'reddit'     => array(
			'label' => 'Reddit',
			'attrs' => array(),
		),
		'vk'         => array(
			'label' => 'VK',
			'attrs' => array( 'image', 'caption' ),
		),
		'buffer'     => array(
			'label' => 'Buffer',
			'attrs' => array( 'via', 'picture' ),
		),
		'xing'       => array(
			'label' => 'Xing',
			'attrs' => array(),
		),
		'line'       => array(
			'label' => 'Line',
			'attrs' => array(),
		),
		'instapaper' => array(
			'label' => 'Instapaper',
			'attrs' => array( 'description' ),
		),
		'pocket'     => array(
			'label' => 'Pocket',
			'attrs' => array(),
		),
		'flipboard'  => array(
			'label' => 'Flipboard',
			'attrs' => array(),
		),
		'weibo'      => array(
			'label' => 'Weibo',
			'attrs' => array( 'image', 'appkey', 'ralateuid' ),
		),
		'blogger'    => array(
			'label' => 'Blogger',
			'attrs' => array( 'description' ),
		),
		'baidu'      => array(
			'label' => 'Baidu',
			'attrs' => array(),
		),
		'okru'       => array(
			'label' => 'Ok.ru',
			'attrs' => array(),
		),
		'evernote'   => array(
			'label' => 'Evernote',
			'attrs' => array(),
		),
		'skype'      => array(
			'label' => 'Skype',
			'attrs' => array(),
		),
		'trello'     => array(
			'label' => 'Trello',
			'attrs' => array( 'description' ),
		),
		'diaspora'   => array(
			'label' => 'Diaspora',
			'attrs' => array( 'description' ),
		),
	);

	/**
	 * Register module
	 */
	public function register() {
		// Enqueue Socicons font and Sharer.js library
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Add social contact methods to user profile
		add_filter( 'user_contactmethods', array( $this, 'add_contact_methods' ) );
	}

	/**
	 * Enqueue CSS and JS assets
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'socicons' );
		wp_enqueue_script( 'sharer' );
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
		$social_icons = array(
			'socicon-internet'        => esc_html__( 'Internet', 'vlthemes-toolkit' ),
			'socicon-moddb'           => esc_html__( 'Moddb', 'vlthemes-toolkit' ),
			'socicon-indiedb'         => esc_html__( 'Indiedb', 'vlthemes-toolkit' ),
			'socicon-traxsource'      => esc_html__( 'Traxsource', 'vlthemes-toolkit' ),
			'socicon-gamefor'         => esc_html__( 'Gamefor', 'vlthemes-toolkit' ),
			'socicon-pixiv'           => esc_html__( 'Pixiv', 'vlthemes-toolkit' ),
			'socicon-myanimelist'     => esc_html__( 'Myanimelist', 'vlthemes-toolkit' ),
			'socicon-blackberry'      => esc_html__( 'Blackberry', 'vlthemes-toolkit' ),
			'socicon-wickr'           => esc_html__( 'Wickr', 'vlthemes-toolkit' ),
			'socicon-spip'            => esc_html__( 'Spip', 'vlthemes-toolkit' ),
			'socicon-napster'         => esc_html__( 'Napster', 'vlthemes-toolkit' ),
			'socicon-beatport'        => esc_html__( 'Beatport', 'vlthemes-toolkit' ),
			'socicon-hackerone'       => esc_html__( 'Hackerone', 'vlthemes-toolkit' ),
			'socicon-hackernews'      => esc_html__( 'Hackernews', 'vlthemes-toolkit' ),
			'socicon-smashwords'      => esc_html__( 'Smashwords', 'vlthemes-toolkit' ),
			'socicon-kobo'            => esc_html__( 'Kobo', 'vlthemes-toolkit' ),
			'socicon-bookbub'         => esc_html__( 'Bookbub', 'vlthemes-toolkit' ),
			'socicon-mailru'          => esc_html__( 'Mailru', 'vlthemes-toolkit' ),
			'socicon-gitlab'          => esc_html__( 'Gitlab', 'vlthemes-toolkit' ),
			'socicon-instructables'   => esc_html__( 'Instructables', 'vlthemes-toolkit' ),
			'socicon-portfolio'       => esc_html__( 'Portfolio', 'vlthemes-toolkit' ),
			'socicon-codered'         => esc_html__( 'Codered', 'vlthemes-toolkit' ),
			'socicon-origin'          => esc_html__( 'Origin', 'vlthemes-toolkit' ),
			'socicon-nextdoor'        => esc_html__( 'Nextdoor', 'vlthemes-toolkit' ),
			'socicon-udemy'           => esc_html__( 'Udemy', 'vlthemes-toolkit' ),
			'socicon-livemaster'      => esc_html__( 'Livemaster', 'vlthemes-toolkit' ),
			'socicon-crunchbase'      => esc_html__( 'Crunchbase', 'vlthemes-toolkit' ),
			'socicon-homefy'          => esc_html__( 'Homefy', 'vlthemes-toolkit' ),
			'socicon-calendly'        => esc_html__( 'Calendly', 'vlthemes-toolkit' ),
			'socicon-realtor'         => esc_html__( 'Realtor', 'vlthemes-toolkit' ),
			'socicon-tidal'           => esc_html__( 'Tidal', 'vlthemes-toolkit' ),
			'socicon-qobuz'           => esc_html__( 'Qobuz', 'vlthemes-toolkit' ),
			'socicon-natgeo'          => esc_html__( 'Natgeo', 'vlthemes-toolkit' ),
			'socicon-mastodon'        => esc_html__( 'Mastodon', 'vlthemes-toolkit' ),
			'socicon-unsplash'        => esc_html__( 'Unsplash', 'vlthemes-toolkit' ),
			'socicon-homeadvisor'     => esc_html__( 'Homeadvisor', 'vlthemes-toolkit' ),
			'socicon-angieslist'      => esc_html__( 'Angieslist', 'vlthemes-toolkit' ),
			'socicon-codepen'         => esc_html__( 'Codepen', 'vlthemes-toolkit' ),
			'socicon-slack'           => esc_html__( 'Slack', 'vlthemes-toolkit' ),
			'socicon-openaigym'       => esc_html__( 'Openaigym', 'vlthemes-toolkit' ),
			'socicon-logmein'         => esc_html__( 'Logmein', 'vlthemes-toolkit' ),
			'socicon-fiverr'          => esc_html__( 'Fiverr', 'vlthemes-toolkit' ),
			'socicon-gotomeeting'     => esc_html__( 'Gotomeeting', 'vlthemes-toolkit' ),
			'socicon-aliexpress'      => esc_html__( 'Aliexpress', 'vlthemes-toolkit' ),
			'socicon-guru'            => esc_html__( 'Guru', 'vlthemes-toolkit' ),
			'socicon-appstore'        => esc_html__( 'Appstore', 'vlthemes-toolkit' ),
			'socicon-homes'           => esc_html__( 'Homes', 'vlthemes-toolkit' ),
			'socicon-zoom'            => esc_html__( 'Zoom', 'vlthemes-toolkit' ),
			'socicon-alibaba'         => esc_html__( 'Alibaba', 'vlthemes-toolkit' ),
			'socicon-craigslist'      => esc_html__( 'Craigslist', 'vlthemes-toolkit' ),
			'socicon-wix'             => esc_html__( 'Wix', 'vlthemes-toolkit' ),
			'socicon-redfin'          => esc_html__( 'Redfin', 'vlthemes-toolkit' ),
			'socicon-googlecalendar'  => esc_html__( 'Googlecalendar', 'vlthemes-toolkit' ),
			'socicon-shopify'         => esc_html__( 'Shopify', 'vlthemes-toolkit' ),
			'socicon-freelancer'      => esc_html__( 'Freelancer', 'vlthemes-toolkit' ),
			'socicon-seedrs'          => esc_html__( 'Seedrs', 'vlthemes-toolkit' ),
			'socicon-bing'            => esc_html__( 'Bing', 'vlthemes-toolkit' ),
			'socicon-doodle'          => esc_html__( 'Doodle', 'vlthemes-toolkit' ),
			'socicon-bonanza'         => esc_html__( 'Bonanza', 'vlthemes-toolkit' ),
			'socicon-squarespace'     => esc_html__( 'Squarespace', 'vlthemes-toolkit' ),
			'socicon-toptal'          => esc_html__( 'Toptal', 'vlthemes-toolkit' ),
			'socicon-gust'            => esc_html__( 'Gust', 'vlthemes-toolkit' ),
			'socicon-ask'             => esc_html__( 'Ask', 'vlthemes-toolkit' ),
			'socicon-trulia'          => esc_html__( 'Trulia', 'vlthemes-toolkit' ),
			'socicon-loomly'          => esc_html__( 'Loomly', 'vlthemes-toolkit' ),
			'socicon-ghost'           => esc_html__( 'Ghost', 'vlthemes-toolkit' ),
			'socicon-upwork'          => esc_html__( 'Upwork', 'vlthemes-toolkit' ),
			'socicon-fundable'        => esc_html__( 'Fundable', 'vlthemes-toolkit' ),
			'socicon-booking'         => esc_html__( 'Booking', 'vlthemes-toolkit' ),
			'socicon-googlemaps'      => esc_html__( 'Googlemaps', 'vlthemes-toolkit' ),
			'socicon-zillow'          => esc_html__( 'Zillow', 'vlthemes-toolkit' ),
			'socicon-niconico'        => esc_html__( 'Niconico', 'vlthemes-toolkit' ),
			'socicon-toneden'         => esc_html__( 'Toneden', 'vlthemes-toolkit' ),
			'socicon-augment'         => esc_html__( 'Augment', 'vlthemes-toolkit' ),
			'socicon-bitbucket'       => esc_html__( 'Bitbucket', 'vlthemes-toolkit' ),
			'socicon-fyuse'           => esc_html__( 'Fyuse', 'vlthemes-toolkit' ),
			'socicon-yt-gaming'       => esc_html__( 'Yt-gaming', 'vlthemes-toolkit' ),
			'socicon-sketchfab'       => esc_html__( 'Sketchfab', 'vlthemes-toolkit' ),
			'socicon-mobcrush'        => esc_html__( 'Mobcrush', 'vlthemes-toolkit' ),
			'socicon-microsoft'       => esc_html__( 'Microsoft', 'vlthemes-toolkit' ),
			'socicon-pandora'         => esc_html__( 'Pandora', 'vlthemes-toolkit' ),
			'socicon-messenger'       => esc_html__( 'Messenger', 'vlthemes-toolkit' ),
			'socicon-gamewisp'        => esc_html__( 'Gamewisp', 'vlthemes-toolkit' ),
			'socicon-bloglovin'       => esc_html__( 'Bloglovin', 'vlthemes-toolkit' ),
			'socicon-tunein'          => esc_html__( 'Tunein', 'vlthemes-toolkit' ),
			'socicon-gamejolt'        => esc_html__( 'Gamejolt', 'vlthemes-toolkit' ),
			'socicon-trello'          => esc_html__( 'Trello', 'vlthemes-toolkit' ),
			'socicon-spreadshirt'     => esc_html__( 'Spreadshirt', 'vlthemes-toolkit' ),
			'socicon-500px'           => esc_html__( '500px', 'vlthemes-toolkit' ),
			'socicon-8tracks'         => esc_html__( '8tracks', 'vlthemes-toolkit' ),
			'socicon-airbnb'          => esc_html__( 'Airbnb', 'vlthemes-toolkit' ),
			'socicon-alliance'        => esc_html__( 'Alliance', 'vlthemes-toolkit' ),
			'socicon-amazon'          => esc_html__( 'Amazon', 'vlthemes-toolkit' ),
			'socicon-amplement'       => esc_html__( 'Amplement', 'vlthemes-toolkit' ),
			'socicon-android'         => esc_html__( 'Android', 'vlthemes-toolkit' ),
			'socicon-angellist'       => esc_html__( 'Angellist', 'vlthemes-toolkit' ),
			'socicon-apple'           => esc_html__( 'Apple', 'vlthemes-toolkit' ),
			'socicon-appnet'          => esc_html__( 'Appnet', 'vlthemes-toolkit' ),
			'socicon-baidu'           => esc_html__( 'Baidu', 'vlthemes-toolkit' ),
			'socicon-bandcamp'        => esc_html__( 'Bandcamp', 'vlthemes-toolkit' ),
			'socicon-battlenet'       => esc_html__( 'Battlenet', 'vlthemes-toolkit' ),
			'socicon-mixer'           => esc_html__( 'Mixer', 'vlthemes-toolkit' ),
			'socicon-bebee'           => esc_html__( 'Bebee', 'vlthemes-toolkit' ),
			'socicon-bebo'            => esc_html__( 'Bebo', 'vlthemes-toolkit' ),
			'socicon-behance'         => esc_html__( 'Behance', 'vlthemes-toolkit' ),
			'socicon-blizzard'        => esc_html__( 'Blizzard', 'vlthemes-toolkit' ),
			'socicon-blogger'         => esc_html__( 'Blogger', 'vlthemes-toolkit' ),
			'socicon-buffer'          => esc_html__( 'Buffer', 'vlthemes-toolkit' ),
			'socicon-chrome'          => esc_html__( 'Chrome', 'vlthemes-toolkit' ),
			'socicon-coderwall'       => esc_html__( 'Coderwall', 'vlthemes-toolkit' ),
			'socicon-curse'           => esc_html__( 'Curse', 'vlthemes-toolkit' ),
			'socicon-dailymotion'     => esc_html__( 'Dailymotion', 'vlthemes-toolkit' ),
			'socicon-deezer'          => esc_html__( 'Deezer', 'vlthemes-toolkit' ),
			'socicon-delicious'       => esc_html__( 'Delicious', 'vlthemes-toolkit' ),
			'socicon-deviantart'      => esc_html__( 'Deviantart', 'vlthemes-toolkit' ),
			'socicon-diablo'          => esc_html__( 'Diablo', 'vlthemes-toolkit' ),
			'socicon-digg'            => esc_html__( 'Digg', 'vlthemes-toolkit' ),
			'socicon-discord'         => esc_html__( 'Discord', 'vlthemes-toolkit' ),
			'socicon-disqus'          => esc_html__( 'Disqus', 'vlthemes-toolkit' ),
			'socicon-douban'          => esc_html__( 'Douban', 'vlthemes-toolkit' ),
			'socicon-draugiem'        => esc_html__( 'Draugiem', 'vlthemes-toolkit' ),
			'socicon-dribbble'        => esc_html__( 'Dribbble', 'vlthemes-toolkit' ),
			'socicon-drupal'          => esc_html__( 'Drupal', 'vlthemes-toolkit' ),
			'socicon-ebay'            => esc_html__( 'Ebay', 'vlthemes-toolkit' ),
			'socicon-ello'            => esc_html__( 'Ello', 'vlthemes-toolkit' ),
			'socicon-endomodo'        => esc_html__( 'Endomodo', 'vlthemes-toolkit' ),
			'socicon-envato'          => esc_html__( 'Envato', 'vlthemes-toolkit' ),
			'socicon-etsy'            => esc_html__( 'Etsy', 'vlthemes-toolkit' ),
			'socicon-facebook'        => esc_html__( 'Facebook', 'vlthemes-toolkit' ),
			'socicon-feedburner'      => esc_html__( 'Feedburner', 'vlthemes-toolkit' ),
			'socicon-filmweb'         => esc_html__( 'Filmweb', 'vlthemes-toolkit' ),
			'socicon-firefox'         => esc_html__( 'Firefox', 'vlthemes-toolkit' ),
			'socicon-flattr'          => esc_html__( 'Flattr', 'vlthemes-toolkit' ),
			'socicon-flickr'          => esc_html__( 'Flickr', 'vlthemes-toolkit' ),
			'socicon-formulr'         => esc_html__( 'Formulr', 'vlthemes-toolkit' ),
			'socicon-forrst'          => esc_html__( 'Forrst', 'vlthemes-toolkit' ),
			'socicon-foursquare'      => esc_html__( 'Foursquare', 'vlthemes-toolkit' ),
			'socicon-friendfeed'      => esc_html__( 'Friendfeed', 'vlthemes-toolkit' ),
			'socicon-github'          => esc_html__( 'Github', 'vlthemes-toolkit' ),
			'socicon-goodreads'       => esc_html__( 'Goodreads', 'vlthemes-toolkit' ),
			'socicon-google'          => esc_html__( 'Google', 'vlthemes-toolkit' ),
			'socicon-googlescholar'   => esc_html__( 'Googlescholar', 'vlthemes-toolkit' ),
			'socicon-googlegroups'    => esc_html__( 'Googlegroups', 'vlthemes-toolkit' ),
			'socicon-googlephotos'    => esc_html__( 'Googlephotos', 'vlthemes-toolkit' ),
			'socicon-googleplus'      => esc_html__( 'Googleplus', 'vlthemes-toolkit' ),
			'socicon-grooveshark'     => esc_html__( 'Grooveshark', 'vlthemes-toolkit' ),
			'socicon-hackerrank'      => esc_html__( 'Hackerrank', 'vlthemes-toolkit' ),
			'socicon-hearthstone'     => esc_html__( 'Hearthstone', 'vlthemes-toolkit' ),
			'socicon-hellocoton'      => esc_html__( 'Hellocoton', 'vlthemes-toolkit' ),
			'socicon-heroes'          => esc_html__( 'Heroes', 'vlthemes-toolkit' ),
			'socicon-smashcast'       => esc_html__( 'Smashcast', 'vlthemes-toolkit' ),
			'socicon-horde'           => esc_html__( 'Horde', 'vlthemes-toolkit' ),
			'socicon-houzz'           => esc_html__( 'Houzz', 'vlthemes-toolkit' ),
			'socicon-icq'             => esc_html__( 'Icq', 'vlthemes-toolkit' ),
			'socicon-identica'        => esc_html__( 'Identica', 'vlthemes-toolkit' ),
			'socicon-imdb'            => esc_html__( 'Imdb', 'vlthemes-toolkit' ),
			'socicon-instagram'       => esc_html__( 'Instagram', 'vlthemes-toolkit' ),
			'socicon-issuu'           => esc_html__( 'Issuu', 'vlthemes-toolkit' ),
			'socicon-istock'          => esc_html__( 'Istock', 'vlthemes-toolkit' ),
			'socicon-itunes'          => esc_html__( 'Itunes', 'vlthemes-toolkit' ),
			'socicon-keybase'         => esc_html__( 'Keybase', 'vlthemes-toolkit' ),
			'socicon-lanyrd'          => esc_html__( 'Lanyrd', 'vlthemes-toolkit' ),
			'socicon-lastfm'          => esc_html__( 'Lastfm', 'vlthemes-toolkit' ),
			'socicon-line'            => esc_html__( 'Line', 'vlthemes-toolkit' ),
			'socicon-linkedin'        => esc_html__( 'Linkedin', 'vlthemes-toolkit' ),
			'socicon-livejournal'     => esc_html__( 'Livejournal', 'vlthemes-toolkit' ),
			'socicon-lyft'            => esc_html__( 'Lyft', 'vlthemes-toolkit' ),
			'socicon-macos'           => esc_html__( 'Macos', 'vlthemes-toolkit' ),
			'socicon-email'           => esc_html__( 'E-Mail', 'vlthemes-toolkit' ),
			'socicon-medium'          => esc_html__( 'Medium', 'vlthemes-toolkit' ),
			'socicon-meetup'          => esc_html__( 'Meetup', 'vlthemes-toolkit' ),
			'socicon-mixcloud'        => esc_html__( 'Mixcloud', 'vlthemes-toolkit' ),
			'socicon-modelmayhem'     => esc_html__( 'Modelmayhem', 'vlthemes-toolkit' ),
			'socicon-mumble'          => esc_html__( 'Mumble', 'vlthemes-toolkit' ),
			'socicon-myspace'         => esc_html__( 'Myspace', 'vlthemes-toolkit' ),
			'socicon-newsvine'        => esc_html__( 'Newsvine', 'vlthemes-toolkit' ),
			'socicon-nintendo'        => esc_html__( 'Nintendo', 'vlthemes-toolkit' ),
			'socicon-npm'             => esc_html__( 'Npm', 'vlthemes-toolkit' ),
			'socicon-okru'            => esc_html__( 'Ok.ru', 'vlthemes-toolkit' ),
			'socicon-openid'          => esc_html__( 'Openid', 'vlthemes-toolkit' ),
			'socicon-opera'           => esc_html__( 'Opera', 'vlthemes-toolkit' ),
			'socicon-outlook'         => esc_html__( 'Outlook', 'vlthemes-toolkit' ),
			'socicon-overwatch'       => esc_html__( 'Overwatch', 'vlthemes-toolkit' ),
			'socicon-patreon'         => esc_html__( 'Patreon', 'vlthemes-toolkit' ),
			'socicon-paypal'          => esc_html__( 'Paypal', 'vlthemes-toolkit' ),
			'socicon-periscope'       => esc_html__( 'Periscope', 'vlthemes-toolkit' ),
			'socicon-persona'         => esc_html__( 'Persona', 'vlthemes-toolkit' ),
			'socicon-pinterest'       => esc_html__( 'Pinterest', 'vlthemes-toolkit' ),
			'socicon-play'            => esc_html__( 'Play', 'vlthemes-toolkit' ),
			'socicon-player'          => esc_html__( 'Player', 'vlthemes-toolkit' ),
			'socicon-playstation'     => esc_html__( 'Playstation', 'vlthemes-toolkit' ),
			'socicon-pocket'          => esc_html__( 'Pocket', 'vlthemes-toolkit' ),
			'socicon-qq'              => esc_html__( 'Qq', 'vlthemes-toolkit' ),
			'socicon-quora'           => esc_html__( 'Quora', 'vlthemes-toolkit' ),
			'socicon-raidcall'        => esc_html__( 'Raidcall', 'vlthemes-toolkit' ),
			'socicon-ravelry'         => esc_html__( 'Ravelry', 'vlthemes-toolkit' ),
			'socicon-reddit'          => esc_html__( 'Reddit', 'vlthemes-toolkit' ),
			'socicon-renren'          => esc_html__( 'Renren', 'vlthemes-toolkit' ),
			'socicon-researchgate'    => esc_html__( 'Researchgate', 'vlthemes-toolkit' ),
			'socicon-residentadvisor' => esc_html__( 'Residentadvisor', 'vlthemes-toolkit' ),
			'socicon-reverbnation'    => esc_html__( 'Reverbnation', 'vlthemes-toolkit' ),
			'socicon-rss'             => esc_html__( 'Rss', 'vlthemes-toolkit' ),
			'socicon-sharethis'       => esc_html__( 'Sharethis', 'vlthemes-toolkit' ),
			'socicon-skype'           => esc_html__( 'Skype', 'vlthemes-toolkit' ),
			'socicon-slideshare'      => esc_html__( 'Slideshare', 'vlthemes-toolkit' ),
			'socicon-smugmug'         => esc_html__( 'Smugmug', 'vlthemes-toolkit' ),
			'socicon-snapchat'        => esc_html__( 'Snapchat', 'vlthemes-toolkit' ),
			'socicon-songkick'        => esc_html__( 'Songkick', 'vlthemes-toolkit' ),
			'socicon-soundcloud'      => esc_html__( 'Soundcloud', 'vlthemes-toolkit' ),
			'socicon-spotify'         => esc_html__( 'Spotify', 'vlthemes-toolkit' ),
			'socicon-stackexchange'   => esc_html__( 'Stackexchange', 'vlthemes-toolkit' ),
			'socicon-stackoverflow'   => esc_html__( 'Stackoverflow', 'vlthemes-toolkit' ),
			'socicon-starcraft'       => esc_html__( 'Starcraft', 'vlthemes-toolkit' ),
			'socicon-stayfriends'     => esc_html__( 'Stayfriends', 'vlthemes-toolkit' ),
			'socicon-steam'           => esc_html__( 'Steam', 'vlthemes-toolkit' ),
			'socicon-storehouse'      => esc_html__( 'Storehouse', 'vlthemes-toolkit' ),
			'socicon-strava'          => esc_html__( 'Strava', 'vlthemes-toolkit' ),
			'socicon-streamjar'       => esc_html__( 'Streamjar', 'vlthemes-toolkit' ),
			'socicon-stumbleupon'     => esc_html__( 'Stumbleupon', 'vlthemes-toolkit' ),
			'socicon-swarm'           => esc_html__( 'Swarm', 'vlthemes-toolkit' ),
			'socicon-teamspeak'       => esc_html__( 'Teamspeak', 'vlthemes-toolkit' ),
			'socicon-teamviewer'      => esc_html__( 'Teamviewer', 'vlthemes-toolkit' ),
			'socicon-technorati'      => esc_html__( 'Technorati', 'vlthemes-toolkit' ),
			'socicon-telegram'        => esc_html__( 'Telegram', 'vlthemes-toolkit' ),
			'socicon-tripadvisor'     => esc_html__( 'Tripadvisor', 'vlthemes-toolkit' ),
			'socicon-tripit'          => esc_html__( 'Tripit', 'vlthemes-toolkit' ),
			'socicon-triplej'         => esc_html__( 'Triplej', 'vlthemes-toolkit' ),
			'socicon-tiktok'          => esc_html__( 'TikTok', 'vlthemes-toolkit' ),
			'socicon-threads'         => esc_html__( 'Threads', 'vlthemes-toolkit' ),
			'socicon-tumblr'          => esc_html__( 'Tumblr', 'vlthemes-toolkit' ),
			'socicon-twitch'          => esc_html__( 'Twitch', 'vlthemes-toolkit' ),
			'socicon-twitter'         => esc_html__( 'Twitter', 'vlthemes-toolkit' ),
			'socicon-uber'            => esc_html__( 'Uber', 'vlthemes-toolkit' ),
			'socicon-ventrilo'        => esc_html__( 'Ventrilo', 'vlthemes-toolkit' ),
			'socicon-viadeo'          => esc_html__( 'Viadeo', 'vlthemes-toolkit' ),
			'socicon-viber'           => esc_html__( 'Viber', 'vlthemes-toolkit' ),
			'socicon-viewbug'         => esc_html__( 'Viewbug', 'vlthemes-toolkit' ),
			'socicon-vimeo'           => esc_html__( 'Vimeo', 'vlthemes-toolkit' ),
			'socicon-vine'            => esc_html__( 'Vine', 'vlthemes-toolkit' ),
			'socicon-vk'              => esc_html__( 'VK', 'vlthemes-toolkit' ),
			'socicon-warcraft'        => esc_html__( 'Warcraft', 'vlthemes-toolkit' ),
			'socicon-wechat'          => esc_html__( 'Wechat', 'vlthemes-toolkit' ),
			'socicon-weibo'           => esc_html__( 'Weibo', 'vlthemes-toolkit' ),
			'socicon-whatsapp'        => esc_html__( 'Whatsapp', 'vlthemes-toolkit' ),
			'socicon-wikipedia'       => esc_html__( 'Wikipedia', 'vlthemes-toolkit' ),
			'socicon-windows'         => esc_html__( 'Windows', 'vlthemes-toolkit' ),
			'socicon-wordpress'       => esc_html__( 'Wordpress', 'vlthemes-toolkit' ),
			'socicon-wykop'           => esc_html__( 'Wykop', 'vlthemes-toolkit' ),
			'socicon-xbox'            => esc_html__( 'Xbox', 'vlthemes-toolkit' ),
			'socicon-xing'            => esc_html__( 'Xing', 'vlthemes-toolkit' ),
			'socicon-yahoo'           => esc_html__( 'Yahoo', 'vlthemes-toolkit' ),
			'socicon-yammer'          => esc_html__( 'Yammer', 'vlthemes-toolkit' ),
			'socicon-yandex'          => esc_html__( 'Yandex', 'vlthemes-toolkit' ),
			'socicon-yelp'            => esc_html__( 'Yelp', 'vlthemes-toolkit' ),
			'socicon-younow'          => esc_html__( 'Younow', 'vlthemes-toolkit' ),
			'socicon-youtube'         => esc_html__( 'Youtube', 'vlthemes-toolkit' ),
			'socicon-zapier'          => esc_html__( 'Zapier', 'vlthemes-toolkit' ),
			'socicon-zerply'          => esc_html__( 'Zerply', 'vlthemes-toolkit' ),
			'socicon-zomato'          => esc_html__( 'Zomato', 'vlthemes-toolkit' ),
			'socicon-zynga'           => esc_html__( 'Zynga', 'vlthemes-toolkit' ),
		);

		return apply_filters( 'vlt_toolkit_social_icons', $social_icons );
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
		$common_attrs = array( 'title', 'url', 'width', 'height', 'link', 'blank' );

		// Get network-specific attributes from constant
		$network_attrs = self::SHAREABLE_NETWORKS[ $slug ]['attrs'] ?? array();

		// Merge common and network-specific attributes
		$allowed_attrs = array_merge( $common_attrs, $network_attrs );

		// Build output array
		$out = array( 'sharer' => $slug );

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

		$data                = array();
		$data['url']         = get_permalink( $post_id );
		$data['title']       = get_the_title( $post_id );
		$data['image']       = get_the_post_thumbnail_url( $post_id, 'full' );
		$data['description'] = get_the_excerpt( $post_id );

		$data['to']      = get_bloginfo( 'admin_email' );
		$data['subject'] = get_bloginfo( 'name' ) . ' | ' . get_bloginfo( 'description' );

		// Get post tags
		$post_tags = get_the_tags( $post_id );
		if ( $post_tags ) {
			$tag_names    = wp_list_pluck( $post_tags, 'name' );
			$data['tags'] = implode( ', ', $tag_names );
			$data['tag']  = reset( $tag_names );
		}

		$data['via']      = get_bloginfo( 'name' );
		$data['caption']  = get_the_excerpt( $post_id );
		$data['hashtags'] = ! empty( $data['tags'] ) ? str_replace( ' ', '', $data['tags'] ) : '';

		return apply_filters( 'vlt_toolkit_post_share_data', array_filter( $data ), $post_id );
	}

	/**
	 * Add social contact methods to user profile
	 *
	 * @param array $contactmethods Existing contact methods.
	 * @return array Modified contact methods.
	 */
	public function add_contact_methods( $contactmethods ) {
		$contactmethods['facebook']  = esc_html__( 'Facebook URL', 'vlthemes-toolkit' );
		$contactmethods['instagram'] = esc_html__( 'Instagram URL', 'vlthemes-toolkit' );
		$contactmethods['twitter']   = esc_html__( 'Twitter URL', 'vlthemes-toolkit' );
		$contactmethods['linkedin']  = esc_html__( 'LinkedIn URL', 'vlthemes-toolkit' );
		$contactmethods['youtube']   = esc_html__( 'YouTube URL', 'vlthemes-toolkit' );
		$contactmethods['pinterest'] = esc_html__( 'Pinterest URL', 'vlthemes-toolkit' );
		$contactmethods['tiktok']    = esc_html__( 'TikTok URL', 'vlthemes-toolkit' );
		$contactmethods['threads']   = esc_html__( 'Threads URL', 'vlthemes-toolkit' );

		return apply_filters( 'vlt_toolkit_user_contact_methods', $contactmethods );
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
		$enabled_socials = apply_filters(
			'vlt_toolkit_post_share_socials',
			array(
				'facebook',
				'twitter',
				'pinterest',
				'telegram',
			)
		);

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
			$output    .= sprintf(
				'<a href="javascript:;" class="vlt-social-icon vlt-social-icon--%s %s"%s><i class="%s"></i></a>',
				esc_attr( $style ),
				esc_attr( $slug ),
				$attrs_string,
				esc_attr( $icon_class )
			);
		}

		return apply_filters( 'vlt_toolkit_post_share_buttons', $output, $post_id, $style );
	}
}
