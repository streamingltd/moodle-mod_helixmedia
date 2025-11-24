// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package
 * @copyright  2023 Tim Williams Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/templates'], function($, ModalFactory, Templates) {
    var module = {};
    module.instances = [];
    module.first = true;

    module.medialinstance = function($, params) {
        var minst = {};
        minst.params = params;
        minst.firsttime = true;

        minst.openmodal = function(evt) {
            evt.preventDefault();
            minst.modal.show();
            if (minst.firsttime) {
                $('#mod_helixmedia_launchframe_' + minst.params.preid + minst.params.extraid).attr('src', minst.params.launchurl);
                minst.firsttime = false;
            }
        };

        minst.initmodal = async function() {
            minst.modal = await ModalFactory.create({
                title: minst.params.title,
                body: Templates.render('mod_helixmedia/modalinner', minst.params),
                large: true
            });
        };

        minst.bind = function() {
            $('#helixmedia_ltimodal_' + minst.params.preid + minst.params.extraid).click(minst.openmodal);
            $('#helixmedia_ltimodalimg_' + minst.params.preid + minst.params.extraid).click(minst.openmodal);
            minst.initmodal();
        };

        minst.unbind = function() {
            $('#helixmedia_ltimodal_' + minst.params.preid + minst.params.extraid).unbind('click');
            $('#helixmedia_ltimodalimg_' + minst.params.preid + minst.params.extraid).unbind('click');
        };

        module.textfit($);

        return minst;
    };


    module.textfit = function($) {
        $('.helixmedia_fittext').each(function() {
            var w2 = $(this).width();
            if ($(this).text().length > 16 && w2 < 240) {
                var ratio = w2 / 240;
                $(this).css('font-size', ratio + 'em');
            } else {
                $(this).css('font-size', 'large');
            }
        });
    };

    module.init = function(frameid, launchurl, thumburl, resID, userID, statusURL, oauthConsumerKey, doStatusCheck,
        sessionURL, sessionFreq, resDelay, extraID, title, library, origin, bs5) {

        // AMD Modules aren't unique, so this will get called in the same instance for each MEDIAL we have on the page.
        // That causes trouble on the quiz grading interface in particular, so wrap each call in an inner object.

        // Sanity check, sometimes this gets called more than once with the same resID. Clean up the old one and re-init.
        if (typeof module.instances[resID + extraID] !== 'undefined') {
            module.instances[resID + extraID].unbind();
        }

        var params = {};
        params.launchurl = launchurl;
        params.userID = userID;
        params.preid = resID;
        params.extraid = extraID;
        params.title = title;
        params.bs5 = bs5;
        if (library || doStatusCheck) {
            params.larger = true;
        } else {
            params.viewonly = true;
        }
        var medialhandler = module.medialinstance($, params);
        module.instances[resID + extraID] = medialhandler;
        medialhandler.bind();
    };

    return module;
});
