/**
 * @module TYPO3/CMS/SudoMode/opt
 */
define({
    /**
     * Implements https://requirejs.org/docs/plugins.html,
     * initial idea and code from https://stackoverflow.com/a/27422370/2854140
     *
     * @param {string} name
     * @param {require} req
     * @param {Function} onload
     */
    load: function(name, req, onload) {
        let onLoadSuccess = function(moduleInstance){
            // Module successfully loaded, call the onload callback so that
            // requirejs can work its internal magic.
            onload(moduleInstance);
        }

        let onLoadFailure = function(err) {
            // optional module failed to load.
            var failedId = err.requireModules && err.requireModules[0];
            // console.warn('Could not load optional module: ' + failedId);

            // Undefine the module to cleanup internal stuff in requireJS
            requirejs.undef(failedId);

            // If failed, this is the default value (can be anything)
            define(failedId, [], null);

            // Now require the module make sure that requireJS thinks
            // that is it loaded. Since we've just defined it, requirejs
            // will not attempt to download any more script files and
            // will just call the onLoadSuccess handler immediately
            req([failedId], onLoadSuccess);
        }

        req([name], onLoadSuccess, onLoadFailure);
    }
});
