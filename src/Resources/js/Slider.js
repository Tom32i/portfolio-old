/**
 * Slider
 *
 * @param {Element} element
 */
function Slider (element)
{
    this.element    = element;
    this.controls   = Array(2);
    this.enabled    = false;
    this.width      = 0;
    this.current    = 0;
    this.max        = 0;
    this.touch      = null;
    this.start      = null;
    this.frame      = null;
    this.changed    = false;

    this.onFrame      = this.onFrame.bind(this);
    this.onResize     = this.onResize.bind(this);
    this.onControl    = this.onControl.bind(this);
    this.onTouchStart = this.onTouchStart.bind(this);
    this.onTouchMove  = this.onTouchMove.bind(this);
    this.onTouchEnd   = this.onTouchEnd.bind(this);

    window.addEventListener('resize', this.onResize);

    this.onResize();
}

/**
 * Break point
 *
 * @type {Number}
 */
Slider.prototype.breakPoint = 768;

/**
 * CSS Transition
 *
 * @type {String}
 */
Slider.prototype.transition = 'margin 300ms';

/**
 * On resize
 */
Slider.prototype.onResize = function()
{
    var width = window.innerWidth;

    if (width > this.breakPoint) {
        this.disable();
    } else {
        this.width = width;
        this.enable();
    }
};

/**
 * On control clicked
 *
 * @param {Event} event
 */
Slider.prototype.onControl = function(event)
{
    if (event.target === this.controls[0]) {
        this.slide(1);
    } else if (event.target === this.controls[1]) {
        this.slide(-1);
    }
};

/**
 * On touch start
 *
 * @param {Event} event
 */
Slider.prototype.onTouchStart = function(event)
{
    if (!this.touch && event.targetTouches.length === 1) {
        var touch = event.targetTouches[0];

        this.touch = {
            identifier: touch.identifier,
            pageX: touch.pageX,
            pageY: touch.pageY
        };

        this.start = this.current;
        this.element.style.transition = 'none';
        this.onFrame();
    }
};

/**
 * On touch move
 *
 * @param {Event} event
 */
Slider.prototype.onTouchMove = function(event)
{
    var touch = this.getTouch(event.changedTouches);

    if (touch) {
        this.setCurrent(this.start + (this.touch.pageX - touch.pageX) / this.width);
    }
};

/**
 * On touch end
 *
 * @param {Event} event
 */
Slider.prototype.onTouchEnd = function(event)
{
    var touch = this.getTouch(event.changedTouches);

    if (touch) {
        var current = this.start + (this.touch.pageX > touch.pageX ? 1 : -1);
        this.frame = window.cancelAnimationFrame(this.frame);
        this.touch = null;
        this.start = null;
        this.element.style.transition = this.transition;
        this.setCurrent(current);
    }
};

/**
 * On frame
 */
Slider.prototype.onFrame = function()
{
    this.frame = window.requestAnimationFrame(this.onFrame);

    if (this.changed) {
        this.update();
    }
};

/**
 * Enable
 */
Slider.prototype.enable = function()
{
    if (!this.enabled) {
        var length = this.element.children.length;

        this.enabled = true;
        this.current = 0;
        this.max     = length - 1;

        this.element.style.width      = (length * 100) + '%';
        this.element.style.marginLeft = 0;
        this.element.style.transition = this.transition;

        this.element.parentNode.classList.add('slider-container');

        this.element.addEventListener('touchstart', this.onTouchStart);
        this.element.addEventListener('touchend', this.onTouchEnd);
        this.element.addEventListener('touchcancel', this.onTouchEnd);
        this.element.addEventListener('touchleave', this.onTouchEnd);
        this.element.addEventListener('touchmove', this.onTouchMove);

        var controls = ['right', 'left'];

        for (var control, i = this.controls.length - 1; i >= 0; i--) {
            control = this.getControl(controls[i]);
            this.element.parentNode.insertBefore(control, this.element);
            control.addEventListener('click', this.onControl);
            this.controls[i] = control;
        }

        this.update();
    }
};

/**
 * Disable
 */
Slider.prototype.disable = function()
{
    if (this.enabled) {
        this.enabled = false;

        this.element.setAttribute('style', '');
        this.element.parentNode.classList.remove('slider-container');

        this.element.removeEventListener('touchstart', this.onTouchStart);
        this.element.removeEventListener('touchend', this.onTouchEnd);
        this.element.removeEventListener('touchcancel', this.onTouchEnd);
        this.element.removeEventListener('touchleave', this.onTouchEnd);
        this.element.removeEventListener('touchmove', this.onTouchMove);

        for (var control, i = this.controls.length - 1; i >= 0; i--) {
            control = this.controls[i];
            control.removeEventListener('click', this.onControl);
            this.element.parentNode.removeChild(control);
            this.controls[i] = null;
        }
    }
};

/**
 * Slide
 *
 * @param {Number} move
 */
Slider.prototype.slide = function(move) {
    this.setCurrent(this.current + move);
};

/**
 * Set current slider value
 *
 * @param {Number} value
 */
Slider.prototype.setCurrent = function(value) {
    this.current = Math.max(Math.min(value, this.max), 0);
    this.changed = true;

    if (!this.frame) {
        this.update();
    }
};

/**
 * Update slider status
 */
Slider.prototype.update = function() {
    this.element.style.marginLeft = (-100 * this.current) + '%';
    this.controls[0].style.opacity = this.current === this.max ? 0 : 1;
    this.controls[1].style.opacity = this.current === 0 ? 0 : 1;
    this.changed = false;
};

/**
 * Get control element
 *
 * @param {String} className
 *
 * @return {Element}
 */
Slider.prototype.getControl = function(className)
{
    var control = document.createElement('div');

    control.className = 'control icon-' + className;

    return control;
};

/**
 * Get touch
 *
 * @param {TouchList} list
 *
 * @return {Touch}
 */
Slider.prototype.getTouch = function(list) {
    if (!this.touch) {
        return null;
    }

    for (var i = list.length - 1; i >= 0; i--) {
        if (list[i].identifier === this.touch.identifier) {
            return list[i];
        }
    }

    return null;
};
