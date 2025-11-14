// vite.config.js
import { defineConfig } from 'vite';
import { resolve } from 'path';
import { copyFileSync } from 'fs';
import AdmZip from 'adm-zip';

export default defineConfig({
	build: {
		rollupOptions: {
			input: resolve(__dirname, 'assets/extensions/elementor/main.js'),
			output: {
				entryFileNames: 'elementor-bundle.js',
			},
		},
		outDir: './assets/extensions/elementor',
		emptyOutDir: false,
	},
	plugins: [
		{
			name: 'copy-libs',
			writeBundle() {
				const libs = [
					// GSAP Core
					{
						from: resolve(__dirname, 'node_modules/gsap/dist/gsap.min.js'),
						to: resolve(__dirname, './assets/vendors/js/gsap.js'),
					},
					// ScrollTrigger
					{
						from: resolve(__dirname, 'node_modules/gsap/dist/ScrollTrigger.min.js'),
						to: resolve(__dirname, './assets/vendors/js/gsap-scrolltrigger.js'),
					},
					// TextPlugin
					{
						from: resolve(__dirname, 'node_modules/gsap/dist/TextPlugin.min.js'),
						to: resolve(__dirname, './assets/vendors/js/gsap-textplugin.js'),
					},
					// Draggable
					{
						from: resolve(__dirname, 'node_modules/gsap/dist/Draggable.min.js'),
						to: resolve(__dirname, './assets/vendors/js/gsap-draggable.js'),
					},
					// ScrollToPlugin
					{
						from: resolve(__dirname, 'node_modules/gsap/dist/ScrollToPlugin.min.js'),
						to: resolve(__dirname, './assets/vendors/js/gsap-scrolltoplugin.js'),
					},
					// Observer
					{
						from: resolve(__dirname, 'node_modules/gsap/dist/Observer.min.js'),
						to: resolve(__dirname, './assets/vendors/js/gsap-observer.js'),
					},
					// AOS
					{
						from: resolve(__dirname, 'node_modules/aos/dist/aos.js'),
						to: resolve(__dirname, './assets/vendors/js/aos.js'),
					},
					// AOS CSS
					{
						from: resolve(__dirname, 'node_modules/aos/dist/aos.css'),
						to: resolve(__dirname, './assets/vendors/css/aos.css'),
					},
					// Jarallax
					{
						from: resolve(__dirname, 'node_modules/jarallax/dist/jarallax.min.js'),
						to: resolve(__dirname, './assets/vendors/js/jarallax.js'),
					},
					// Jarallax Video
					{
						from: resolve(__dirname, 'node_modules/jarallax/dist/jarallax-video.min.js'),
						to: resolve(__dirname, './assets/vendors/js/jarallax-video.js'),
					},
					// Jarallax CSS
					{
						from: resolve(__dirname, 'node_modules/jarallax/dist/jarallax.css'),
						to: resolve(__dirname, './assets/vendors/css/jarallax.css'),
					},
					// Sharer
					{
						from: resolve(__dirname, 'node_modules/sharer.js/sharer.min.js'),
						to: resolve(__dirname, './assets/vendors/js/sharer.js'),
					},
				];

				libs.forEach(({ from, to }) => {
					try {
						copyFileSync(from, to);
						console.log(`Copied: ${from} → ${to}`);
					} catch (err) {
						console.warn(`Failed to copy: ${from}`, err.message);
					}
				});
			},
		},
		{
			name: 'create-plugin-zip',
			apply: 'build',
			closeBundle() {
				console.log('\n📦 Creating plugin ZIP archive...');

				const zip = new AdmZip();
				const outputPath = resolve(__dirname, 'dist/vlthemes_helper_plugin.zip');

				// Add directories
				zip.addLocalFolder(resolve(__dirname, 'assets'), 'vlthemes_helper_plugin/assets');
				zip.addLocalFolder(resolve(__dirname, 'includes'), 'vlthemes_helper_plugin/includes');
				zip.addLocalFolder(resolve(__dirname, 'languages'), 'vlthemes_helper_plugin/languages');

				// Add main plugin file
				zip.addLocalFile(resolve(__dirname, 'vlthemes_helper_plugin.php'), 'vlthemes_helper_plugin');

				// Write the ZIP file
				zip.writeZip(outputPath);

				console.log(`✓ ZIP created successfully: ${outputPath}`);
			},
		},
	],

	server: {
		port: 3000,
		open: false,
	},
});