// SCSS build: every scss/<name>.scss compiles to assets/css/<name>.css (partials in abstracts/ are only imported).
// File names say who loads them: admin-* (WP admin), elementor-* (Elementor integration), feature-* (Features modules).
//   node scss/build.mjs          — compile once
//   node scss/build.mjs --watch  — recompile on every .scss change
import { readdirSync, watch, writeFileSync } from 'fs';
import { basename, dirname, relative, resolve } from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import * as sass from 'sass';

export const scssDir = dirname(fileURLToPath(import.meta.url));
const root = resolve(scssDir, '..');
const outDir = resolve(root, 'assets/css');

// Entry points: top-level .scss files that are not partials
export const entries = () => readdirSync(scssDir).filter((file) => file.endsWith('.scss') && !file.startsWith('_'));

export function compileAll() {
	let ok = true;

	for (const file of entries()) {
		const out = relative(root, resolve(outDir, basename(file, '.scss') + '.css'));

		try {
			const { css } = sass.compile(resolve(scssDir, file), { style: 'expanded' });
			const banner = `/* Generated from scss/${file} — edit the source, not this file */\n`;

			// @charset must stay the very first thing in the file
			const output = css.startsWith('@charset')
				? css.replace(/^(@charset[^;]*;\n)/, `$1${banner}`)
				: banner + css;

			writeFileSync(resolve(root, out), output + '\n');
			console.log(`✓ scss/${file} → ${out}`);
		} catch (err) {
			ok = false;
			console.error(`✗ scss/${file}\n${err.message}`);
		}
	}

	return ok;
}

// Run directly (not when imported by vite.config.js)
if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
	const ok = compileAll();

	if (process.argv.includes('--watch')) {
		let timer;

		console.log(`\nWatching ${relative(root, scssDir)}/ for changes…`);
		watch(scssDir, { recursive: true }, (event, file) => {
			if (!file || !file.endsWith('.scss')) {
				return;
			}

			clearTimeout(timer);
			timer = setTimeout(compileAll, 50);
		});
	} else if (!ok) {
		process.exit(1);
	}
}
