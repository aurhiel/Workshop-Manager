var Encore = require('@symfony/webpack-encore');
var GoogleFontsPlugin = require("@beyonk/google-fonts-webpack-plugin");
// var FontelloFontsPlugin = require("fontello-webpack-plugin");
var CopyWebpackPlugin = require("copy-webpack-plugin");

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')

    // the public path you will use in Symfony's asset() function - e.g. asset('build/some_file.js')
    .setManifestKeyPrefix('build/')

    // Vendors
    .createSharedEntry('entries', './shared_entries.js') // new way to add vendor on Encore^0.24.0
    // .createSharedEntry('vendors', ['jquery', 'bootstrap']) // old way (Encore^0.20.0)

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    //
    .cleanupOutputBeforeBuild()

    // Copy static files like fonts, images, ...
    .addPlugin(new CopyWebpackPlugin([
      { from: './assets/static' }
    ]))

    //
    .enableSourceMaps(!Encore.isProduction())

    // show OS notifications when builds finish/fail
    // .enableBuildNotifications()

    // uncomment to create hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // will create public/build/main.js and public/build/main.css
    .addEntry('main', './assets/js/main.js')


    // Google Fonts
    .addPlugin(new GoogleFontsPlugin({
        fonts: [
            // { family: "Quicksand", variants: [ "400", "500", "700" ] },
            { family: "Cabin", variants: [
              "400", "500", "600", "700",
              // "400italic", "500italic", "600italic", "700italic"
            ] },
        ],
        "path": "fonts/google/",
        "filename": "google-fonts.css"
    }))

    // 2018-05-25 : Fontello is dead ... RIP (down in fact)
    // .addPlugin(new FontelloFontsPlugin({
    //   config: require("./assets/fontello.config.json"),
    //   name: 'fontello',
    //   output: {
    //     font : 'fonts/fontello/[name].[ext]',
    //   }
    // }))

    // Dashboard entry
    .addEntry('dashboard', [
      './assets/js/stealthRaven.js',
      './assets/js/dashboard.js'
    ])

    .addEntry('admin-dashboard', [
      './assets/js/stealthRaven.js',
      './assets/js/admin-dashboard.js'
    ])

    // Survey entry
    .addEntry('survey', [
      './assets/js/survey.js'
    ])

    // Survey results entry
    .addEntry('survey-results', [
      './assets/js/survey-results.js'
    ])

    // .addStyleEntry('css/app', './assets/css/app.scss')

    // uncomment if you use Sass/SCSS files
    .enableSassLoader(function(sassOptions) {}, {
      resolveUrlLoader: false
    })

    // uncomment for legacy applications that require $/jQuery as a global variable
    .autoProvidejQuery()

    // Post CSS to autoprefix CSS properties for example
    .enablePostCssLoader()
;


// Use polling instead of inotify
const config = Encore.getWebpackConfig();
config.watchOptions = {
    poll: true,
};

module.exports = config;
