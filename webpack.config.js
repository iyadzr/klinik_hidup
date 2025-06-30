const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    // Use relative paths in development to avoid hardcoding localhost URLs
    .setPublicPath(Encore.isProduction() ? '/build' : '/build')
    // Configure for Docker environment - use relative paths
    .setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

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
    .enableVersioning(Encore.isProduction())

    // enables Vue.js support
    .enableVueLoader(() => {}, {
        version: 3,
        runtimeCompilerBuild: true
    })

    // enables Sass/SCSS support and silences deprecation warnings coming from dependencies
    .enableSassLoader((loaderOptions) => {
        // Pass flags directly to Dart-Sass
        loaderOptions.sassOptions = {
            // Do *not* print warnings that originate in files under node_modules
            quietDeps: true,
            // (optional) hide our own warnings too; comment out if you still want them
            // quiet: true,
        };
    })

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    .configureDevServerOptions(options => {
        options.historyApiFallback = true;
        options.hot = true;
        options.host = '0.0.0.0';
        options.port = 8080;
        options.allowedHosts = 'all';
        // Force webpack to write files to disk so they can be served by nginx
        options.devMiddleware = {
            writeToDisk: true,
        };
        // Disable dev server URLs in entrypoints.json
        options.devMiddleware.publicPath = '/build/';
    })
    
    // Configure webpack to use relative paths in entrypoints.json
    .addAliases({
        // This helps ensure consistent path resolution
    })
;

// Get the base webpack config
const config = Encore.getWebpackConfig();

// Override the dev server configuration to prevent absolute URLs in entrypoints.json
if (!Encore.isProduction()) {
    // Force relative paths in development to work with any host/port
    config.output.publicPath = '/build/';
    
    // Prevent webpack from using absolute URLs in entrypoints.json
    if (config.devServer) {
        // Keep the server configuration but force relative paths
        config.devServer.devMiddleware = {
            ...config.devServer.devMiddleware,
            publicPath: '/build/',
        };
        // Disable public option which can cause absolute URLs
        delete config.devServer.public;
    }
}

module.exports = config;
