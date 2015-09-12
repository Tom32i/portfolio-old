/**
 * On load
 */
window.onload = function () {
    var times   = document.getElementsByTagName('time'),
        sliders = document.getElementsByClassName('slider'),
        disqus  = document.getElementById('disqus_thread');

    for (var time, i = times.length - 1; i >= 0; i--) {
        times[i].innerHTML = moment(times[i].getAttribute('datetime'), 'YYYY-MM-DD hh:ii:ss +0000 UTC').fromNow();
    }

    for (var j = sliders.length - 1; j >= 0; j--) {
        new Slider(sliders[j]);
    }

    if (disqus) {
        new DisqusLoader(disqus);
    }
};
