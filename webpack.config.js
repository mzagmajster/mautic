'use strict';

/* eslint-env node */
const path = require('path');
const glob = require('glob');
const fs = require('fs');
const { loaders } = require('@ckeditor/ckeditor5-dev-utils');
const { CKEditorTranslationsPlugin } = require('@ckeditor/ckeditor5-dev-translations');

let webroot = '';
if (!fs.existsSync('app/release_metadata.json')) {
    let files = glob.sync("**/app/release_metadata.json");
    webroot = path.dirname(path.dirname(files[0])) + '/';
}

module.exports = {
    devtool: 'source-map',
    performance: { hints: false },
    cache: {
        type: 'filesystem',
        cacheDirectory: path.resolve(__dirname, 'var/cache/js/webpack'),
    },
    entry: {
        ckeditor: path.resolve(__dirname, webroot + 'app/assets/libraries/ckeditor/src', 'ckeditor.ts'),
        tinymce: path.resolve(__dirname, webroot + 'app/assets/libraries/tinymce/src', 'tinymce.ts'),
    },
    output: {
        path: path.resolve(__dirname, webroot + 'media/libraries/'),
        filename: '[name]/[name].js',
        library: '[name]',
        libraryTarget: 'umd',
        libraryExport: 'default',
    },
    plugins: [
        new CKEditorTranslationsPlugin({
            language: 'en',
            additionalLanguages: 'all',
			addMainLanguageTranslationsToAllAssets: false,
			buildAllTranslationsToSeparateFiles: true,
        })
    ],
    module: {
        rules: [
            loaders.getIconsLoader({ matchExtensionOnly: true }),
            loaders.getStylesLoader({
                themePath: require.resolve('@ckeditor/ckeditor5-theme-lark'),
                minify: true
            }),
            loaders.getTypeScriptLoader()
        ]
    },
    resolve: {
        extensions: ['.ts', '.js', '.json']
    },
    optimization: {
        removeAvailableModules: false,
        removeEmptyChunks: false,
        splitChunks: false,
    }
};
