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
 * @copyright  2021 Tim Williams Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    var module = {};
    module.instances = [];
    module.first = true;

    module.medialinstance = function($, params) {

        var minst = {};
        minst.params = params;
        minst.params.gotIn = false;
        minst.params.medialinterval = false;

        minst.openmodal = function(evt) {
            evt.preventDefault();
            $('#mod_helixmedia_launchframe_' + minst.params.docID).attr('src', minst.params.launchurl);
            $('.modal-backdrop').css('position', 'relative');
            $('.modal-backdrop').css('z-index', '0');

            if (minst.params.doStatusCheck) {
                setTimeout(minst.checkStatus, 5000);
                setTimeout(minst.maintainSession, minst.params.sessionFreq);
            }
        };

        minst.closemodalListen = function(evt) {
            evt.preventDefault();
            minst.closemodal();
        };

        minst.closemodal = function() {
            if (minst.params.medialinterval != false) {
                clearInterval(minst.params.medialinterval);
            }

            $('#mod_helixmedia_launchframe_' + minst.params.docID).attr('src', '');

            if (!minst.params.doStatusCheck) {
                return;
            }

            var tframe = document.getElementById("mod_helixmedia_thumbframe_" + minst.params.docID);
            if (tframe !== null && typeof (minst.params.thumburl) != "undefined") {
                tframe.contentWindow.location = minst.params.thumburl;
            }

            var mform1 = document.getElementById("mform1");
            if (mform1 === null) {
                var elements = document.getElementsByClassName("mform");
                mform1 = elements[0];
            }

        };

        minst.closeDialogue = function() {
            $('#mod_helixmedia_modal_' + minst.params.docID).modal('hide');
            minst.closemodal();
        };

        minst.bind = function() {
            $('#helixmedia_ltimodal_' + minst.params.docID).click(minst.openmodal);
            $('#mod_helixmedia_closemodal_' + minst.params.docID).click(minst.closemodalListen);
        };

        minst.unbind = function() {
            $('#helixmedia_ltimodal_' + minst.params.docID).off();
            $('#mod_helixmedia_closemodal_' + minst.params.docID).off();
            if (minst.params.medialinterval != false) {
                clearInterval(minst.params.medialinterval);
            }
        };

        minst.maintainSession = function() {
            var xmlDoc = new XMLHttpRequest();
            xmlDoc.open("GET", minst.params.sessionURL, true);
            xmlDoc.send();
            setTimeout(minst.maintainSession, minst.params.sessionFreq);
        };

        minst.checkStatusResponse = function(evt) {
            var responseText = evt.target.responseText;
            if (responseText == "IN") {
                minst.params.gotIn = true;
            }
            if (responseText != "OUT" || minst.params.gotIn == false) {
                if (minst.params.medialinterval == false) {
                    minst.params.medialinterval = setInterval(minst.checkStatus, 2000);
                }
            } else {

                if (minst.params.resDelay == 0) {
                    minst.closeDialogue();
                } else {
                    setTimeout(minst.closeDialogue, (minst.params.resDelay * 1000));
                }
            }
        };

        minst.checkStatus = function() {
            var xmlDoc = new XMLHttpRequest();
            var params = "resource_link_id=" + minst.params.resID +
                "&user_id=" + minst.params.userID + "&oauth_consumer_key=" + minst.params.oauthConsumerKey;
            xmlDoc.addEventListener("load", minst.checkStatusResponse);
            xmlDoc.open("POST", minst.params.statusURL);
            xmlDoc.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlDoc.send(params);
        };

        return minst;
    };

    module.init = function(frameid, launchurl, thumburl, resID, userID, statusURL, oauthConsumerKey, doStatusCheck,
        sessionURL, sessionFreq, resDelay, extraID) {

        // AMD Modules aren't unique, so this will get called in the same instance for each MEDIAL we have on the page.
        // That causes trouble on the quiz grading interface in particular, so wrap each call in an inner object.

        // Sanity check, sometimes this gets called more than once with the same resID. Clean up the old one and re-init.
        if (typeof module.instances[resID + extraID] !== 'undefined') {
            module.instances[resID + extraID].unbind();
        }

        var params = {};
        params.frameid = frameid;
        params.launchurl = launchurl;
        params.thumburl = thumburl;
        params.resID = resID;
        params.userID = userID;
        params.statusURL = statusURL;
        params.oauthConsumerKey = oauthConsumerKey;
        params.doStatusCheck = doStatusCheck;
        params.sessionURL = sessionURL;
        params.sessionFreq = sessionFreq;
        params.resDelay = resDelay;
        params.docID = resID + extraID;
        var medialhandler = module.medialinstance($, params);
        module.instances[params.docID] = medialhandler;
        medialhandler.bind();
    };

    return module;
});
