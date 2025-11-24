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

        minst.params.medialInterval = false;
        minst.params.videoref = '';

        minst.openmodal = function(evt) {
            evt.preventDefault();

            var lu = minst.params.launchurl;
            if (minst.params.videorref !== '') {
                lu = lu + "&video_ref=" + minst.params.videoref;
            }
            $('#mod_helixmedia_launchframe_' + minst.params.docID).attr('src', lu);
            if (!minst.params.bs5) {
                $('.modal-backdrop').css('position', 'relative');
            }

            $('.modal-backdrop').css('z-index', '0');
            if (minst.params.doStatusCheck) {
                if (minst.params.statusURL !== false) {
                    setTimeout(minst.checkStatus, 500);
                }
                setTimeout(minst.maintainSession, minst.params.sessionFreq);
            }
            window.addEventListener("message", minst.onmessage);
        };

        minst.onmessage = function(evt) {
            if (evt.origin != minst.params.origin) {
                /* eslint-disable-next-line no-console */
                console.log("Message rejected: bad origin evt: " + evt.origin + " expected: " + minst.params.origin);
                return;
            }

            var mform1 = document.getElementById("mform1");
            if (mform1 === null) {
                var elements = document.getElementsByClassName("mform");
                mform1 = elements.item(0);
            }

            var name = mform1.elements.namedItem('name');
            if (name !== null && name.value.length == 0) {
                mform1.name.value = evt.data.title;
            }

            var custom = mform1.elements.namedItem('custom');
            if (custom !== null) {
                custom.value = JSON.stringify(evt.data.custom);
            }

            var hacustom = mform1.elements.namedItem('helixassign_custom');
            if (hacustom !== null) {
                hacustom.value = JSON.stringify(evt.data.custom);
            }

            var hfcustom = mform1.elements.namedItem('helixfeedback_custom');
            if (hfcustom !== null) {
                hfcustom.value = JSON.stringify(evt.data.custom);
            }


            var addgrades = mform1.elements.namedItem('addgrades');
            if (addgrades !== null) {
                if (evt.data.custom.is_quiz.toLowerCase() == "true") {
                    addgrades.checked = true;
                } else {
                    addgrades.checked = false;
                }
            }

            minst.params.videoref = evt.data.custom.video_ref;
            setTimeout(minst.closeDialogue, 2000);
        };

        minst.textfit = function($) {
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

        minst.closemodalListen = function(evt) {
            evt.preventDefault();
            minst.closemodal();
        };

        minst.closemodal = function() {
            if (minst.params.medialInterval != false) {
                clearInterval(minst.params.medialInterval);
                minst.params.medialInterval = false;
            }

            $('#mod_helixmedia_launchframe_' + minst.params.docID).attr('src', '');

            if (!minst.params.doStatusCheck) {
                return;
            }

            var tframe = document.getElementById("mod_helixmedia_thumbframe_" + minst.params.docID);

            if (tframe !== null && typeof (minst.params.thumburl) != "undefined") {
                if (minst.params.videorref === '') {
                    tframe.contentWindow.location = minst.params.thumburl;
                } else {
                    tframe.contentWindow.location = minst.params.thumburl + "&video_ref=" + minst.params.videoref;
                }

            }
        };

        minst.closeDialogue = function() {
            $('#mod_helixmedia_modal_' + minst.params.docID).modal('hide');
            minst.closemodal();
        };

        minst.bind = function() {
            $('#helixmedia_ltimodal_' + minst.params.docID).click(minst.openmodal);
            $('#mod_helixmedia_closemodal_' + minst.params.docID).click(minst.closemodalListen);

            minst.textfit($);
        };

        minst.unbind = function() {
            $('#helixmedia_ltimodal_' + minst.params.docID).off();
            $('#mod_helixmedia_closemodal_' + minst.params.docID).off();
            if (minst.params.medialInterval != false) {
                clearInterval(minst.params.medialInterval);
                minst.params.medialInterval = false;
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

                if (minst.params.medialInterval == false) {
                    minst.params.medialInterval = setInterval(minst.checkStatus, 2000);
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
            var params = "resource_link_id=" + minst.params.resID + "&user_id=" + minst.params.userID +
                "&oauth_consumer_key=" + minst.params.oauthConsumerKey;
            xmlDoc.addEventListener("load", minst.checkStatusResponse);
            xmlDoc.open("POST", minst.params.statusURL);
            xmlDoc.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlDoc.send(params);
        };

        return minst;
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
        params.bs5 = bs5;
        params.origin = origin;
        var medialhandler = module.medialinstance($, params);
        module.instances[params.docID] = medialhandler;
        medialhandler.bind();
    };

    return module;
});
