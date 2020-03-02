var Encore = require('@symfony/webpack-encore');

Encore
// directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/js/app.js')
    //.addEntry('page1', './assets/js/page1.js')
    //.addEntry('page2', './assets/js/page2.js')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())


    .addEntry('js/bootstrap-datepicker', './assets/js/bootstrap-datepicker.js')
    .addEntry('js/bootstrap-datepicker.fr', './assets/js/bootstrap-datepicker.fr.js')
    .addEntry('js/Chart.bundle.min', './assets/js/Chart.bundle.min.js')
    .addEntry('js/html2pdf.bundle', './assets/js/html2pdf.bundle.js')
    .addEntry('js/app', './assets/js/app.js')
    .addEntry('js/scales', './assets/js/scales.js')

    .addStyleEntry('css/bootstrap-datepicker', './assets/css/bootstrap-datepicker.css')
    .addStyleEntry('css/Chart.min', './assets/css/Chart.min.css')
    .addStyleEntry('css/scales', './assets/css/scales.scss')

    .addStyleEntry('css/app', './assets/css/app.scss')
    .addStyleEntry('css/login', './assets/css/login.scss')
    .addStyleEntry('css/index', './assets/css/index.scss')
    .addStyleEntry('css/admin', './assets/css/admin.scss')
    .addStyleEntry('css/dashboard', './assets/css/dashboard.scss')
    .addStyleEntry('css/report', './assets/css/report.scss')
    .addStyleEntry('css/report_pdf', './assets/css/report_pdf.scss')
    .addStyleEntry('css/timeline', './assets/css/timeline.scss')

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use Sass/SCSS files
    .enableSassLoader()

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // VueJS
    .enableVueLoader()
;

module.exports = Encore.getWebpackConfig();