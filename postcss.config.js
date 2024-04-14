// Some help : https://symfony.com/doc/current/frontend/encore/postcss.html

module.exports = {
    plugins: {
        // include whatever plugins you want
        // but make sure you install these via yarn or npm!

        // add browserslist config to package.json (see below)
        autoprefixer: {
          "overrideBrowserslist": [
            // this config = defaults, see here : https://github.com/browserslist/browserslist
            "> 0.5%",
            "last 2 versions",
            "Firefox ESR",
            "not dead"
          ]
        }
    }
}
