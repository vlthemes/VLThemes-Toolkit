// vite.config.js
import { defineConfig } from 'vite';
import { resolve } from 'path';
import { copyFileSync } from 'fs';
import { compileAll, scssDir } from './assets/scss/build.mjs';
import AdmZip from 'adm-zip';

export default defineConfig({
	css: {
		preprocessorOptions: {
			scss: {
				api: 'modern-compiler',
			},
		},
	},
	plugins: [
		{
			name: 'compile-scss',
			configureServer(server) {
				// Recompile every entry when any file in scss/ changes
				server.watcher.add(scssDir);
				server.watcher.on('change', (file) => {
					if (file.endsWith('.scss')) {
						compileAll();
					}
				});
			},
			buildStart() {
				compileAll();
			},
		},
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
					// jQuery matchHeight
					{
						from: resolve(__dirname, 'node_modules/jquery-match-height/dist/jquery.matchHeight-min.js'),
						to: resolve(__dirname, './assets/vendors/js/jquery.matchHeight.js'),
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
				const outputPath = resolve(__dirname, 'dist/vlthemes-toolkit.zip');

				// Add directories
				// SCSS sources stay out of the package — only the compiled assets/css ships
				zip.addLocalFolder(resolve(__dirname, 'assets'), 'vlthemes-toolkit/assets', (file) => !file.includes('/scss/'));
				// Dev-only preview of the admin dashboard stays out of the package
				zip.addLocalFolder(resolve(__dirname, 'includes'), 'vlthemes-toolkit/includes', (file) => !file.endsWith('dashboard-demo.html'));
				zip.addLocalFolder(resolve(__dirname, 'languages'), 'vlthemes-toolkit/languages');

				// Add main plugin file
				zip.addLocalFile(resolve(__dirname, 'vlthemes-toolkit.php'), 'vlthemes-toolkit');
				zip.addLocalFile(resolve(__dirname, 'README.md'), 'vlthemes-toolkit');

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
