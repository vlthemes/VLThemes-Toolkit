<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
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
	 * Shareable networks supported by Sharer.js
	 * Format: 'slug' => ['label' => 'Display Name', 'attrs' => ['attr1', 'attr2']]
	 *
	 */
	public const SHAREABLE_NETWORKS = [
		'twitter' => [
			'label' => 'Twitter',
			'attrs' => [ 'via', 'hashtags' ],
		],
		'x' => [
			'label' => 'X',
			'attrs' => [ 'via', 'hashtags' ],
		],
		'bluesky' => [
			'label' => 'Bluesky',
			'attrs' => [],
		],
		'threads' => [
			'label' => 'Threads',
			'attrs' => [],
		],
		'facebook' => [
			'label' => 'Facebook',
			'attrs' => [ 'hashtag' ],
		],
		'linkedin' => [
			'label' => 'LinkedIn',
			'attrs' => [],
		],
		'email' => [
			'label' => 'E-Mail',
			'attrs' => [ 'to', 'subject' ],
		],
		'whatsapp' => [
			'label' => 'WhatsApp',
			'attrs' => [ 'to', 'web', 'description' ],
		],
		'telegram' => [
			'label' => 'Telegram',
			'attrs' => [],
		],
		'viber' => [
			'label' => 'Viber',
			'attrs' => [],
		],
		'pinterest' => [
			'label' => 'Pinterest',
			'attrs' => [ 'image', 'description' ],
		],
		'tumblr' => [
			'label' => 'Tumblr',
			'attrs' => [ 'caption', 'tags' ],
		],
		'hackernews' => [
			'label' => 'Hacker News',
			'attrs' => [],
		],
		'reddit' => [
			'label' => 'Reddit',
			'attrs' => [],
		],
		'vk' => [
			'label' => 'VK',
			'attrs' => [ 'image', 'caption' ],
		],
		'buffer' => [
			'label' => 'Buffer',
			'attrs' => [ 'via', 'picture' ],
		],
		'xing' => [
			'label' => 'Xing',
			'attrs' => [],
		],
		'line' => [
			'label' => 'Line',
			'attrs' => [],
		],
		'instapaper' => [
			'label' => 'Instapaper',
			'attrs' => [ 'description' ],
		],
		'pocket' => [
			'label' => 'Pocket',
			'attrs' => [],
		],
		'flipboard' => [
			'label' => 'Flipboard',
			'attrs' => [],
		],
		'weibo' => [
			'label' => 'Weibo',
			'attrs' => [ 'image', 'appkey', 'ralateuid' ],
		],
		'blogger' => [
			'label' => 'Blogger',
			'attrs' => [ 'description' ],
		],
		'baidu' => [
			'label' => 'Baidu',
			'attrs' => [],
		],
		'okru' => [
			'label' => 'Ok.ru',
			'attrs' => [],
		],
		'evernote' => [
			'label' => 'Evernote',
			'attrs' => [],
		],
		'skype' => [
			'label' => 'Skype',
			'attrs' => [],
		],
		'trello' => [
			'label' => 'Trello',
			'attrs' => [ 'description' ],
		],
		'diaspora' => [
			'label' => 'Diaspora',
			'attrs' => [ 'description' ],
		],
	];

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
		wp_enqueue_style( 'socicons' );
		wp_enqueue_script( 'sharer' );
	}

	/**
	 * Get social icons list
	 *
	 * Returns all available social icons from Socicons font.
	 * Format: 'socicon-{network}' => 'Display Name'
	 *
	 * @return array array of social icons
	 */
	public static function get_social_icons() {
		$social_icons = [
			'socicon-internet'        => esc_html__( 'Internet', 'toolkit' ),
			'socicon-moddb'           => esc_html__( 'Moddb', 'toolkit' ),
			'socicon-indiedb'         => esc_html__( 'Indiedb', 'toolkit' ),
			'socicon-traxsource'      => esc_html__( 'Traxsource', 'toolkit' ),
			'socicon-gamefor'         => esc_html__( 'Gamefor', 'toolkit' ),
			'socicon-pixiv'           => esc_html__( 'Pixiv', 'toolkit' ),
			'socicon-myanimelist'     => esc_html__( 'Myanimelist', 'toolkit' ),
			'socicon-blackberry'      => esc_html__( 'Blackberry', 'toolkit' ),
			'socicon-wickr'           => esc_html__( 'Wickr', 'toolkit' ),
			'socicon-spip'            => esc_html__( 'Spip', 'toolkit' ),
			'socicon-napster'         => esc_html__( 'Napster', 'toolkit' ),
			'socicon-beatport'        => esc_html__( 'Beatport', 'toolkit' ),
			'socicon-hackerone'       => esc_html__( 'Hackerone', 'toolkit' ),
			'socicon-hackernews'      => esc_html__( 'Hackernews', 'toolkit' ),
			'socicon-smashwords'      => esc_html__( 'Smashwords', 'toolkit' ),
			'socicon-kobo'            => esc_html__( 'Kobo', 'toolkit' ),
			'socicon-bookbub'         => esc_html__( 'Bookbub', 'toolkit' ),
			'socicon-mailru'          => esc_html__( 'Mailru', 'toolkit' ),
			'socicon-gitlab'          => esc_html__( 'Gitlab', 'toolkit' ),
			'socicon-instructables'   => esc_html__( 'Instructables', 'toolkit' ),
			'socicon-portfolio'       => esc_html__( 'Portfolio', 'toolkit' ),
			'socicon-codered'         => esc_html__( 'Codered', 'toolkit' ),
			'socicon-origin'          => esc_html__( 'Origin', 'toolkit' ),
			'socicon-nextdoor'        => esc_html__( 'Nextdoor', 'toolkit' ),
			'socicon-udemy'           => esc_html__( 'Udemy', 'toolkit' ),
			'socicon-livemaster'      => esc_html__( 'Livemaster', 'toolkit' ),
			'socicon-crunchbase'      => esc_html__( 'Crunchbase', 'toolkit' ),
			'socicon-homefy'          => esc_html__( 'Homefy', 'toolkit' ),
			'socicon-calendly'        => esc_html__( 'Calendly', 'toolkit' ),
			'socicon-realtor'         => esc_html__( 'Realtor', 'toolkit' ),
			'socicon-tidal'           => esc_html__( 'Tidal', 'toolkit' ),
			'socicon-qobuz'           => esc_html__( 'Qobuz', 'toolkit' ),
			'socicon-natgeo'          => esc_html__( 'Natgeo', 'toolkit' ),
			'socicon-mastodon'        => esc_html__( 'Mastodon', 'toolkit' ),
			'socicon-unsplash'        => esc_html__( 'Unsplash', 'toolkit' ),
			'socicon-homeadvisor'     => esc_html__( 'Homeadvisor', 'toolkit' ),
			'socicon-angieslist'      => esc_html__( 'Angieslist', 'toolkit' ),
			'socicon-codepen'         => esc_html__( 'Codepen', 'toolkit' ),
			'socicon-slack'           => esc_html__( 'Slack', 'toolkit' ),
			'socicon-openaigym'       => esc_html__( 'Openaigym', 'toolkit' ),
			'socicon-logmein'         => esc_html__( 'Logmein', 'toolkit' ),
			'socicon-fiverr'          => esc_html__( 'Fiverr', 'toolkit' ),
			'socicon-gotomeeting'     => esc_html__( 'Gotomeeting', 'toolkit' ),
			'socicon-aliexpress'      => esc_html__( 'Aliexpress', 'toolkit' ),
			'socicon-guru'            => esc_html__( 'Guru', 'toolkit' ),
			'socicon-appstore'        => esc_html__( 'Appstore', 'toolkit' ),
			'socicon-homes'           => esc_html__( 'Homes', 'toolkit' ),
			'socicon-zoom'            => esc_html__( 'Zoom', 'toolkit' ),
			'socicon-alibaba'         => esc_html__( 'Alibaba', 'toolkit' ),
			'socicon-craigslist'      => esc_html__( 'Craigslist', 'toolkit' ),
			'socicon-wix'             => esc_html__( 'Wix', 'toolkit' ),
			'socicon-redfin'          => esc_html__( 'Redfin', 'toolkit' ),
			'socicon-googlecalendar'  => esc_html__( 'Googlecalendar', 'toolkit' ),
			'socicon-shopify'         => esc_html__( 'Shopify', 'toolkit' ),
			'socicon-freelancer'      => esc_html__( 'Freelancer', 'toolkit' ),
			'socicon-seedrs'          => esc_html__( 'Seedrs', 'toolkit' ),
			'socicon-bing'            => esc_html__( 'Bing', 'toolkit' ),
			'socicon-doodle'          => esc_html__( 'Doodle', 'toolkit' ),
			'socicon-bonanza'         => esc_html__( 'Bonanza', 'toolkit' ),
			'socicon-squarespace'     => esc_html__( 'Squarespace', 'toolkit' ),
			'socicon-toptal'          => esc_html__( 'Toptal', 'toolkit' ),
			'socicon-gust'            => esc_html__( 'Gust', 'toolkit' ),
			'socicon-ask'             => esc_html__( 'Ask', 'toolkit' ),
			'socicon-trulia'          => esc_html__( 'Trulia', 'toolkit' ),
			'socicon-loomly'          => esc_html__( 'Loomly', 'toolkit' ),
			'socicon-ghost'           => esc_html__( 'Ghost', 'toolkit' ),
			'socicon-upwork'          => esc_html__( 'Upwork', 'toolkit' ),
			'socicon-fundable'        => esc_html__( 'Fundable', 'toolkit' ),
			'socicon-booking'         => esc_html__( 'Booking', 'toolkit' ),
			'socicon-googlemaps'      => esc_html__( 'Googlemaps', 'toolkit' ),
			'socicon-zillow'          => esc_html__( 'Zillow', 'toolkit' ),
			'socicon-niconico'        => esc_html__( 'Niconico', 'toolkit' ),
			'socicon-toneden'         => esc_html__( 'Toneden', 'toolkit' ),
			'socicon-augment'         => esc_html__( 'Augment', 'toolkit' ),
			'socicon-bitbucket'       => esc_html__( 'Bitbucket', 'toolkit' ),
			'socicon-fyuse'           => esc_html__( 'Fyuse', 'toolkit' ),
			'socicon-yt-gaming'       => esc_html__( 'Yt-gaming', 'toolkit' ),
			'socicon-sketchfab'       => esc_html__( 'Sketchfab', 'toolkit' ),
			'socicon-mobcrush'        => esc_html__( 'Mobcrush', 'toolkit' ),
			'socicon-microsoft'       => esc_html__( 'Microsoft', 'toolkit' ),
			'socicon-pandora'         => esc_html__( 'Pandora', 'toolkit' ),
			'socicon-messenger'       => esc_html__( 'Messenger', 'toolkit' ),
			'socicon-gamewisp'        => esc_html__( 'Gamewisp', 'toolkit' ),
			'socicon-bloglovin'       => esc_html__( 'Bloglovin', 'toolkit' ),
			'socicon-tunein'          => esc_html__( 'Tunein', 'toolkit' ),
			'socicon-gamejolt'        => esc_html__( 'Gamejolt', 'toolkit' ),
			'socicon-trello'          => esc_html__( 'Trello', 'toolkit' ),
			'socicon-spreadshirt'     => esc_html__( 'Spreadshirt', 'toolkit' ),
			'socicon-500px'           => esc_html__( '500px', 'toolkit' ),
			'socicon-8tracks'         => esc_html__( '8tracks', 'toolkit' ),
			'socicon-airbnb'          => esc_html__( 'Airbnb', 'toolkit' ),
			'socicon-alliance'        => esc_html__( 'Alliance', 'toolkit' ),
			'socicon-amazon'          => esc_html__( 'Amazon', 'toolkit' ),
			'socicon-amplement'       => esc_html__( 'Amplement', 'toolkit' ),
			'socicon-android'         => esc_html__( 'Android', 'toolkit' ),
			'socicon-angellist'       => esc_html__( 'Angellist', 'toolkit' ),
			'socicon-apple'           => esc_html__( 'Apple', 'toolkit' ),
			'socicon-appnet'          => esc_html__( 'Appnet', 'toolkit' ),
			'socicon-baidu'           => esc_html__( 'Baidu', 'toolkit' ),
			'socicon-bandcamp'        => esc_html__( 'Bandcamp', 'toolkit' ),
			'socicon-battlenet'       => esc_html__( 'Battlenet', 'toolkit' ),
			'socicon-mixer'           => esc_html__( 'Mixer', 'toolkit' ),
			'socicon-bebee'           => esc_html__( 'Bebee', 'toolkit' ),
			'socicon-bebo'            => esc_html__( 'Bebo', 'toolkit' ),
			'socicon-behance'         => esc_html__( 'Behance', 'toolkit' ),
			'socicon-blizzard'        => esc_html__( 'Blizzard', 'toolkit' ),
			'socicon-blogger'         => esc_html__( 'Blogger', 'toolkit' ),
			'socicon-buffer'          => esc_html__( 'Buffer', 'toolkit' ),
			'socicon-chrome'          => esc_html__( 'Chrome', 'toolkit' ),
			'socicon-coderwall'       => esc_html__( 'Coderwall', 'toolkit' ),
			'socicon-curse'           => esc_html__( 'Curse', 'toolkit' ),
			'socicon-dailymotion'     => esc_html__( 'Dailymotion', 'toolkit' ),
			'socicon-deezer'          => esc_html__( 'Deezer', 'toolkit' ),
			'socicon-delicious'       => esc_html__( 'Delicious', 'toolkit' ),
			'socicon-deviantart'      => esc_html__( 'Deviantart', 'toolkit' ),
			'socicon-diablo'          => esc_html__( 'Diablo', 'toolkit' ),
			'socicon-digg'            => esc_html__( 'Digg', 'toolkit' ),
			'socicon-discord'         => esc_html__( 'Discord', 'toolkit' ),
			'socicon-disqus'          => esc_html__( 'Disqus', 'toolkit' ),
			'socicon-douban'          => esc_html__( 'Douban', 'toolkit' ),
			'socicon-draugiem'        => esc_html__( 'Draugiem', 'toolkit' ),
			'socicon-dribbble'        => esc_html__( 'Dribbble', 'toolkit' ),
			'socicon-drupal'          => esc_html__( 'Drupal', 'toolkit' ),
			'socicon-ebay'            => esc_html__( 'Ebay', 'toolkit' ),
			'socicon-ello'            => esc_html__( 'Ello', 'toolkit' ),
			'socicon-endomodo'        => esc_html__( 'Endomodo', 'toolkit' ),
			'socicon-envato'          => esc_html__( 'Envato', 'toolkit' ),
			'socicon-etsy'            => esc_html__( 'Etsy', 'toolkit' ),
			'socicon-facebook'        => esc_html__( 'Facebook', 'toolkit' ),
			'socicon-feedburner'      => esc_html__( 'Feedburner', 'toolkit' ),
			'socicon-filmweb'         => esc_html__( 'Filmweb', 'toolkit' ),
			'socicon-firefox'         => esc_html__( 'Firefox', 'toolkit' ),
			'socicon-flattr'          => esc_html__( 'Flattr', 'toolkit' ),
			'socicon-flickr'          => esc_html__( 'Flickr', 'toolkit' ),
			'socicon-formulr'         => esc_html__( 'Formulr', 'toolkit' ),
			'socicon-forrst'          => esc_html__( 'Forrst', 'toolkit' ),
			'socicon-foursquare'      => esc_html__( 'Foursquare', 'toolkit' ),
			'socicon-friendfeed'      => esc_html__( 'Friendfeed', 'toolkit' ),
			'socicon-github'          => esc_html__( 'Github', 'toolkit' ),
			'socicon-goodreads'       => esc_html__( 'Goodreads', 'toolkit' ),
			'socicon-google'          => esc_html__( 'Google', 'toolkit' ),
			'socicon-googlescholar'   => esc_html__( 'Googlescholar', 'toolkit' ),
			'socicon-googlegroups'    => esc_html__( 'Googlegroups', 'toolkit' ),
			'socicon-googlephotos'    => esc_html__( 'Googlephotos', 'toolkit' ),
			'socicon-googleplus'      => esc_html__( 'Googleplus', 'toolkit' ),
			'socicon-grooveshark'     => esc_html__( 'Grooveshark', 'toolkit' ),
			'socicon-hackerrank'      => esc_html__( 'Hackerrank', 'toolkit' ),
			'socicon-hearthstone'     => esc_html__( 'Hearthstone', 'toolkit' ),
			'socicon-hellocoton'      => esc_html__( 'Hellocoton', 'toolkit' ),
			'socicon-heroes'          => esc_html__( 'Heroes', 'toolkit' ),
			'socicon-smashcast'       => esc_html__( 'Smashcast', 'toolkit' ),
			'socicon-horde'           => esc_html__( 'Horde', 'toolkit' ),
			'socicon-houzz'           => esc_html__( 'Houzz', 'toolkit' ),
			'socicon-icq'             => esc_html__( 'Icq', 'toolkit' ),
			'socicon-identica'        => esc_html__( 'Identica', 'toolkit' ),
			'socicon-imdb'            => esc_html__( 'Imdb', 'toolkit' ),
			'socicon-instagram'       => esc_html__( 'Instagram', 'toolkit' ),
			'socicon-issuu'           => esc_html__( 'Issuu', 'toolkit' ),
			'socicon-istock'          => esc_html__( 'Istock', 'toolkit' ),
			'socicon-itunes'          => esc_html__( 'Itunes', 'toolkit' ),
			'socicon-keybase'         => esc_html__( 'Keybase', 'toolkit' ),
			'socicon-lanyrd'          => esc_html__( 'Lanyrd', 'toolkit' ),
			'socicon-lastfm'          => esc_html__( 'Lastfm', 'toolkit' ),
			'socicon-line'            => esc_html__( 'Line', 'toolkit' ),
			'socicon-linkedin'        => esc_html__( 'Linkedin', 'toolkit' ),
			'socicon-livejournal'     => esc_html__( 'Livejournal', 'toolkit' ),
			'socicon-lyft'            => esc_html__( 'Lyft', 'toolkit' ),
			'socicon-macos'           => esc_html__( 'Macos', 'toolkit' ),
			'socicon-email'           => esc_html__( 'E-Mail', 'toolkit' ),
			'socicon-medium'          => esc_html__( 'Medium', 'toolkit' ),
			'socicon-meetup'          => esc_html__( 'Meetup', 'toolkit' ),
			'socicon-mixcloud'        => esc_html__( 'Mixcloud', 'toolkit' ),
			'socicon-modelmayhem'     => esc_html__( 'Modelmayhem', 'toolkit' ),
			'socicon-mumble'          => esc_html__( 'Mumble', 'toolkit' ),
			'socicon-myspace'         => esc_html__( 'Myspace', 'toolkit' ),
			'socicon-newsvine'        => esc_html__( 'Newsvine', 'toolkit' ),
			'socicon-nintendo'        => esc_html__( 'Nintendo', 'toolkit' ),
			'socicon-npm'             => esc_html__( 'Npm', 'toolkit' ),
			'socicon-okru'            => esc_html__( 'Ok.ru', 'toolkit' ),
			'socicon-openid'          => esc_html__( 'Openid', 'toolkit' ),
			'socicon-opera'           => esc_html__( 'Opera', 'toolkit' ),
			'socicon-outlook'         => esc_html__( 'Outlook', 'toolkit' ),
			'socicon-overwatch'       => esc_html__( 'Overwatch', 'toolkit' ),
			'socicon-patreon'         => esc_html__( 'Patreon', 'toolkit' ),
			'socicon-paypal'          => esc_html__( 'Paypal', 'toolkit' ),
			'socicon-periscope'       => esc_html__( 'Periscope', 'toolkit' ),
			'socicon-persona'         => esc_html__( 'Persona', 'toolkit' ),
			'socicon-pinterest'       => esc_html__( 'Pinterest', 'toolkit' ),
			'socicon-play'            => esc_html__( 'Play', 'toolkit' ),
			'socicon-player'          => esc_html__( 'Player', 'toolkit' ),
			'socicon-playstation'     => esc_html__( 'Playstation', 'toolkit' ),
			'socicon-pocket'          => esc_html__( 'Pocket', 'toolkit' ),
			'socicon-qq'              => esc_html__( 'Qq', 'toolkit' ),
			'socicon-quora'           => esc_html__( 'Quora', 'toolkit' ),
			'socicon-raidcall'        => esc_html__( 'Raidcall', 'toolkit' ),
			'socicon-ravelry'         => esc_html__( 'Ravelry', 'toolkit' ),
			'socicon-reddit'          => esc_html__( 'Reddit', 'toolkit' ),
			'socicon-renren'          => esc_html__( 'Renren', 'toolkit' ),
			'socicon-researchgate'    => esc_html__( 'Researchgate', 'toolkit' ),
			'socicon-residentadvisor' => esc_html__( 'Residentadvisor', 'toolkit' ),
			'socicon-reverbnation'    => esc_html__( 'Reverbnation', 'toolkit' ),
			'socicon-rss'             => esc_html__( 'Rss', 'toolkit' ),
			'socicon-sharethis'       => esc_html__( 'Sharethis', 'toolkit' ),
			'socicon-skype'           => esc_html__( 'Skype', 'toolkit' ),
			'socicon-slideshare'      => esc_html__( 'Slideshare', 'toolkit' ),
			'socicon-smugmug'         => esc_html__( 'Smugmug', 'toolkit' ),
			'socicon-snapchat'        => esc_html__( 'Snapchat', 'toolkit' ),
			'socicon-songkick'        => esc_html__( 'Songkick', 'toolkit' ),
			'socicon-soundcloud'      => esc_html__( 'Soundcloud', 'toolkit' ),
			'socicon-spotify'         => esc_html__( 'Spotify', 'toolkit' ),
			'socicon-stackexchange'   => esc_html__( 'Stackexchange', 'toolkit' ),
			'socicon-stackoverflow'   => esc_html__( 'Stackoverflow', 'toolkit' ),
			'socicon-starcraft'       => esc_html__( 'Starcraft', 'toolkit' ),
			'socicon-stayfriends'     => esc_html__( 'Stayfriends', 'toolkit' ),
			'socicon-steam'           => esc_html__( 'Steam', 'toolkit' ),
			'socicon-storehouse'      => esc_html__( 'Storehouse', 'toolkit' ),
			'socicon-strava'          => esc_html__( 'Strava', 'toolkit' ),
			'socicon-streamjar'       => esc_html__( 'Streamjar', 'toolkit' ),
			'socicon-stumbleupon'     => esc_html__( 'Stumbleupon', 'toolkit' ),
			'socicon-swarm'           => esc_html__( 'Swarm', 'toolkit' ),
			'socicon-teamspeak'       => esc_html__( 'Teamspeak', 'toolkit' ),
			'socicon-teamviewer'      => esc_html__( 'Teamviewer', 'toolkit' ),
			'socicon-technorati'      => esc_html__( 'Technorati', 'toolkit' ),
			'socicon-telegram'        => esc_html__( 'Telegram', 'toolkit' ),
			'socicon-tripadvisor'     => esc_html__( 'Tripadvisor', 'toolkit' ),
			'socicon-tripit'          => esc_html__( 'Tripit', 'toolkit' ),
			'socicon-triplej'         => esc_html__( 'Triplej', 'toolkit' ),
			'socicon-tiktok'          => esc_html__( 'TikTok', 'toolkit' ),
			'socicon-threads'         => esc_html__( 'Threads', 'toolkit' ),
			'socicon-tumblr'          => esc_html__( 'Tumblr', 'toolkit' ),
			'socicon-twitch'          => esc_html__( 'Twitch', 'toolkit' ),
			'socicon-twitter'         => esc_html__( 'Twitter', 'toolkit' ),
			'socicon-uber'            => esc_html__( 'Uber', 'toolkit' ),
			'socicon-ventrilo'        => esc_html__( 'Ventrilo', 'toolkit' ),
			'socicon-viadeo'          => esc_html__( 'Viadeo', 'toolkit' ),
			'socicon-viber'           => esc_html__( 'Viber', 'toolkit' ),
			'socicon-viewbug'         => esc_html__( 'Viewbug', 'toolkit' ),
			'socicon-vimeo'           => esc_html__( 'Vimeo', 'toolkit' ),
			'socicon-vine'            => esc_html__( 'Vine', 'toolkit' ),
			'socicon-vk'              => esc_html__( 'VK', 'toolkit' ),
			'socicon-warcraft'        => esc_html__( 'Warcraft', 'toolkit' ),
			'socicon-wechat'          => esc_html__( 'Wechat', 'toolkit' ),
			'socicon-weibo'           => esc_html__( 'Weibo', 'toolkit' ),
			'socicon-whatsapp'        => esc_html__( 'Whatsapp', 'toolkit' ),
			'socicon-wikipedia'       => esc_html__( 'Wikipedia', 'toolkit' ),
			'socicon-windows'         => esc_html__( 'Windows', 'toolkit' ),
			'socicon-wordpress'       => esc_html__( 'Wordpress', 'toolkit' ),
			'socicon-wykop'           => esc_html__( 'Wykop', 'toolkit' ),
			'socicon-xbox'            => esc_html__( 'Xbox', 'toolkit' ),
			'socicon-xing'            => esc_html__( 'Xing', 'toolkit' ),
			'socicon-yahoo'           => esc_html__( 'Yahoo', 'toolkit' ),
			'socicon-yammer'          => esc_html__( 'Yammer', 'toolkit' ),
			'socicon-yandex'          => esc_html__( 'Yandex', 'toolkit' ),
			'socicon-yelp'            => esc_html__( 'Yelp', 'toolkit' ),
			'socicon-younow'          => esc_html__( 'Younow', 'toolkit' ),
			'socicon-youtube'         => esc_html__( 'Youtube', 'toolkit' ),
			'socicon-zapier'          => esc_html__( 'Zapier', 'toolkit' ),
			'socicon-zerply'          => esc_html__( 'Zerply', 'toolkit' ),
			'socicon-zomato'          => esc_html__( 'Zomato', 'toolkit' ),
			'socicon-zynga'           => esc_html__( 'Zynga', 'toolkit' ),
		];

		return apply_filters( 'vlt_toolkit_social_icons', $social_icons );
	}

	/**
	 * Build sharer data attributes
	 *
	 * Filters attributes based on network-specific allowed attributes from SHAREABLE_NETWORKS constant.
	 * Common attributes (title, url, width, height, link, blank) are always allowed.
	 *
	 * @param string $slug  Social network slug (e.g. 'facebook', 'twitter').
	 * @param array  $attrs attributes array
	 *
	 * @return array filtered data attributes
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
			if ( null === $val || '' === $val ) {
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
	 * @param null|int $post_id post ID (uses current post if not provided)
	 *
	 * @return array post sharing data
	 */
	public static function get_post_share_data( $post_id = null ) {
		$post_id = $post_id ?: get_the_ID();

		$data                = [];
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
		$data['hashtags'] = !empty( $data['tags'] ) ? str_replace( ' ', '', $data['tags'] ) : '';

		return apply_filters( 'vlt_toolkit_post_share_data', array_filter( $data ), $post_id );
	}

	/**
	 * Add social contact methods to user profile
	 *
	 * @param array $contactmethods existing contact methods
	 *
	 * @return array modified contact methods
	 */
	public function add_contact_methods( $contactmethods ) {
		$contactmethods['facebook']  = esc_html__( 'Facebook URL', 'toolkit' );
		$contactmethods['instagram'] = esc_html__( 'Instagram URL', 'toolkit' );
		$contactmethods['twitter']   = esc_html__( 'Twitter URL', 'toolkit' );
		$contactmethods['linkedin']  = esc_html__( 'LinkedIn URL', 'toolkit' );
		$contactmethods['youtube']   = esc_html__( 'YouTube URL', 'toolkit' );
		$contactmethods['pinterest'] = esc_html__( 'Pinterest URL', 'toolkit' );
		$contactmethods['tiktok']    = esc_html__( 'TikTok URL', 'toolkit' );
		$contactmethods['threads']   = esc_html__( 'Threads URL', 'toolkit' );

		return apply_filters( 'vlt_toolkit_user_contact_methods', $contactmethods );
	}

	/**
	 * Get post share buttons HTML
	 *
	 * @param null|int $post_id post ID
	 * @param string   $style   Button style (e.g. 'style-1', 'style-2').
	 *
	 * @return string share buttons HTML
	 */
	public static function get_post_share_buttons( $post_id = null, $style = 'style-1' ) {
		$post_id = $post_id ?: get_the_ID();

		// Get share data
		$share_data = self::get_post_share_data( $post_id );

		// Default social networks to display
		$enabled_socials = apply_filters(
			'vlt_toolkit_post_share_socials',
			[
				'facebook',
				'twitter',
				'pinterest',
				'telegram',
			],
		);

		// If no socials are configured, return empty
		if ( empty( $enabled_socials ) ) {
			return '';
		}

		$output = '';

		// Loop through enabled socials and generate buttons
		foreach ( $enabled_socials as $slug ) {
			// Check if this network is supported by Sharer.js
			if ( !isset( self::SHAREABLE_NETWORKS[ $slug ] ) ) {
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
				esc_attr( $icon_class ),
			);
		}

		return apply_filters( 'vlt_toolkit_post_share_buttons', $output, $post_id, $style );
	}
}
