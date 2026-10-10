// Release build: compiles SCSS, refreshes vendor copies, generates the POT and packs dist/vlthemes-toolkit.zip.
// No bundler — nothing here is bundled, the plugin ships its JS/CSS as-is.
//   node build.mjs
import { copyFileSync, mkdirSync, mkdtempSync, rmSync } from 'fs';
import { tmpdir } from 'os';
import { dirname, join, resolve } from 'path';
import { fileURLToPath } from 'url';
import { execFileSync } from 'child_process';
import AdmZip from 'adm-zip';
import { compileAll } from './assets/scss/build.mjs';

const root = dirname(fileURLToPath(import.meta.url));

// SCSS → assets/css
if (!compileAll()) {
	process.exit(1);
}

// Grid Builder editor UI (@wordpress/scripts) → includes/GridBuilder/assets/build
execFileSync('npm', ['run', 'grid:build'], { cwd: root, stdio: 'inherit' });

// Vendor libraries copied from node_modules
const libs = [
	// Sharer
	{
		from: resolve(root, 'node_modules/sharer.js/sharer.min.js'),
		to: resolve(root, 'assets/vendors/js/sharer.js'),
	},
];

libs.forEach(({ from, to }) => {
	copyFileSync(from, to);
	console.log(`Copied: ${from} → ${to}`);
});

console.log('\n📦 Creating plugin ZIP archive...');

const zip = new AdmZip();
const outputPath = resolve(root, 'dist/vlthemes-toolkit.zip');

// Add directories
// SCSS sources stay out of the package — only the compiled assets/css ships
zip.addLocalFolder(resolve(root, 'assets'), 'vlthemes-toolkit/assets', (file) => !file.includes('/scss/'));
// Dev-only preview of the admin dashboard and the Grid Builder editor sources stay out of the package
zip.addLocalFolder(resolve(root, 'includes'), 'vlthemes-toolkit/includes', (file) => !file.endsWith('dashboard-demo.html') && !file.includes('GridBuilder/assets/src/'));
// languages/ exists only in the package: the POT is generated fresh from the sources on every build
const potDir = mkdtempSync(join(tmpdir(), 'vlt-toolkit-pot-'));
const potFile = join(potDir, 'toolkit.pot');
try {
	execFileSync(
		process.env.PHP_BIN || '/Applications/XAMPP/xamppfiles/bin/php',
		[process.env.WP_CLI_BIN || '/usr/local/bin/wp', 'i18n', 'make-pot', root, potFile, '--domain=toolkit', '--exclude=node_modules,dist,assets/vendors'],
		{ stdio: 'inherit' }
	);
	zip.addLocalFile(potFile, 'vlthemes-toolkit/languages');
} finally {
	rmSync(potDir, { recursive: true, force: true });
}

// Add main plugin file
zip.addLocalFile(resolve(root, 'vlthemes-toolkit.php'), 'vlthemes-toolkit');
zip.addLocalFile(resolve(root, 'README.md'), 'vlthemes-toolkit');

// Write the ZIP file
mkdirSync(resolve(root, 'dist'), { recursive: true });
zip.writeZip(outputPath);

console.log(`✓ ZIP created successfully: ${outputPath}`);
