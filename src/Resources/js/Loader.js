/**
 * Loader
 */
function Loader ()
{
    this.keys = [];

    this.onKeyBoard = this.onKeyBoard.bind(this);

    window.addEventListener('keydown', this.onKeyBoard);
}

/**
 * Pattern
 *
 * @type {String}
 */
Loader.prototype.pattern = "38,38,40,40,37,39,37,39,66,65";

/**
 * On keyboard
 *
 * @param {Event} event
 */
Loader.prototype.onKeyBoard = function(event)
{
    this.keys.push(event.keyCode);

    if (this.keys.toString().indexOf(this.pattern) >= 0) {
        window.removeEventListener('keydown', this.onKeyBoard);
        document.head.appendChild(this.getScript());
    }
};

/**
 * Get script
 *
 * @return {Element}
 */
Loader.prototype.getScript = function()
{
    var script = document.createElement('script');

    script.src   = 'http://lab.tom32i.fr/console/terminal.min.js';
    script.async = true;

    return script;
};
