var Encore = require("@symfony/webpack-encore");
var CopyWebpackPlugin = require("copy-webpack-plugin");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
	Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
	// directory where compiled assets will be stored
	.setOutputPath("build/")
	// public path used by the web server to access the output path
	.setPublicPath(".")
	// only needed for CDN's or sub-directory deploy
	.setManifestKeyPrefix("build/")

	/*
	 * ENTRY CONFIG
	 *
	 * Add 1 entry for each "page" of your app
	 * (including one that's included on every page - e.g. "app")
	 *
	 * Each entry will result in one JavaScript file (e.g. app.js)
	 * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
	 */
	.addEntry("app", "./assets/app.js")
	.addEntry("pdf", "./assets/components/pdf/pdf.scss")

	// Color templates
	.addEntry("default", "./assets/color-templates/default.scss")
	.addEntry("blue", "./assets/color-templates/blue.scss")
	.addEntry("green", "./assets/color-templates/green.scss")
	.addEntry("red", "./assets/color-templates/red.scss")
	.addEntry("yellow", "./assets/color-templates/yellow.scss")
	.addEntry("lightblue", "./assets/color-templates/lightblue.scss")

	// When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
	//.splitEntryChunks()

	// will require an extra script tag for runtime.js
	// but, you probably want this, unless you're building a single-page app
	.disableSingleRuntimeChunk()

	/*
	 * FEATURE CONFIG
	 *
	 * Enable & configure other features below. For a full
	 * list of features, see:
	 * https://symfony.com/doc/current/frontend.html#adding-more-features
	 */
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableSourceMaps(!Encore.isProduction())
	// enables hashed filenames (e.g. app.abc123.css)
	//.enableVersioning(Encore.isProduction())

	// enables @babel/preset-env polyfills
	.configureBabelPresetEnv((config) => {
		config.useBuiltIns = "usage";
		config.corejs = 3;
	})

	// enables Sass/SCSS support
	.enableSassLoader();

// uncomment if you use TypeScript
//.enableTypeScriptLoader()

// uncomment to get integrity="..." attributes on your script & link tags
// requires WebpackEncoreBundle 1.4 or higher
//.enableIntegrityHashes(Encore.isProduction())

// uncomment if you're having problems with a jQuery plugin
// .autoProvidejQuery()

// uncomment if you use API Platform Admin (composer require api-admin)
//.enableReactPreset()

module.exports = Encore.getWebpackConfig();
