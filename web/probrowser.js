function get_browser_agent()
{
        var ua = navigator.userAgent.toLowerCase();
        var opera = ((ua.indexOf('opera') != -1) ? true : false);
        var espial = ((ua.indexOf('escape') != -1) ? true : false);
        var safari = ((ua.indexOf('safari') != -1) ? true : false);
        var firefox = ((ua.indexOf('firefox') != -1) ? true : false);
        var msie = ((ua.indexOf('msie') != -1) ? true : false);
        var mac = ((ua.indexOf('mac') != -1) ? true : false);
        var unix = ((ua.indexOf('x11') != -1) ? true : false);
        var win = ((mac || unix) ? false : true);
        var version = false;
        var mozilla = false;

        if (!firefox && !safari && (ua.indexOf('gecko') != -1)) {
                mozilla = true;
                var _tmp = ua.split('/');
                version = _tmp[_tmp.length - 1].split(' ')[0];
        }

        if (firefox)
        {
                var _tmp = ua.split('/');
                version = _tmp[_tmp.length - 1].split(' ')[0];
        }
        if (msie)
                version = ua.substring((ua.indexOf('msie ') + 5)).split(';')[0];

        if (safari)
        {
                /**
                * Safari doesn't report a string, have to use getBrowserEngine to get it
                */
                //        version = this.getBrowserEngine().version;
                version = ua.substring((ua.indexOf('safari/') + 7)).split(' ')[0];

        }

        if (opera)
                version = ua.substring((ua.indexOf('opera/') + 6)).split(' ')[0];

        /**
        * Return the Browser Object
        * @type Object
        */
        var browsers = {
            ua: navigator.userAgent,
            opera: opera,
            espial: espial,
            safari: safari,
            firefox: firefox,
            mozilla: mozilla,
            msie: msie,
            mac: mac,
            win: win,
            unix: unix,
            version: version
        }
        return browsers;
}
