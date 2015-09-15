/**
 * Disqus
 *
 * @param {Element} element
 */
function DisqusLoader(element)
{
    this.element    = element;
    this.shortname  = this.element.getAttribute('data-shortname');
    this.identifier = this.element.getAttribute('data-identifier');
    this.title      = this.element.getAttribute('data-title');
    this.url        = this.element.getAttribute('data-url');

    this.load = this.load.bind(this);

    this.element.addEventListener('click', this.load);
}

/**
 * Get script element
 *
 * @return {Element}
 */
DisqusLoader.prototype.getScript = function()
{
    var element = document.createElement('script');

    element.type  = 'text/javascript';
    element.async = true;
    element.src   = '//' + disqus_shortname + '.disqus.com/embed.js';

    return element;
};

/**
 * Load Disqus
 */
DisqusLoader.prototype.load = function()
{
    if (typeof(disqus_shortname) !== 'undefined') {
        return;
    }

    this.element.removeEventListener('click', this.load);

    window.disqus_shortname  = this.shortname;
    window.disqus_identifier = this.identifier;
    window.disqus_title      = this.title;
    window.disqus_url        = this.url;

    document.head.appendChild(this.getScript());
};
