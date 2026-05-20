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

            minst.modalVisibility(true);

            var lu = minst.params.launchurl;
            if (minst.params.videorref !== '') {
                lu = lu + "&video_ref=" + minst.params.videoref;
            }

            document.getElementById('mod_helixmedia_launchframe_' + minst.params.docID).src = lu;

            if (minst.params.doStatusCheck) {
                if (minst.params.statusURL !== false) {
                    setTimeout(minst.checkStatus, 500);
                }
                setTimeout(minst.maintainSession, minst.params.sessionFreq);
            }
            window.addEventListener("message", minst.onmessage);
        };

        minst.modalVisibility = function(show) {
            // Maintain existing Jquery behaviour if we are using bootstrap 4
            if (!minst.params.bs5) {
                if (show) {
                    $('.modal-backdrop').css('z-index', '0');
                    $('.modal-backdrop').css('position', 'relative');
                } else {
                    $('#mod_helixmedia_modal_' + minst.params.docID).modal('hide');
                }
                return;
            }

            // This ensures that the visibility of the modal and the backdrop is always set correctly.
            // Themes such as Edweiser RemUi have code that causes problems with this custom dialogue meaning it never shows.
            // JQuery modal calls also seems to fail if Edweiser plugins are present, so do this with pure JS.

            var element = document.getElementById('mod_helixmedia_modal_' + minst.params.docID);
            var backdrop = document.getElementsByClassName('modal-backdrop');
            if (backdrop.length > 0) {
                backdrop = backdrop[0];
            } else {
                backdrop = false;
            }

            if (show) {
                if (element.classList.contains('hide')) {
                    element.classList.remove('hide');
                }

                if (!element.classList.contains('show')) {
                    element.classList.add('show');
                }

                if (backdrop) {
                    if (!backdrop.classList.contains('show')) {
                        backdrop.classList.add('show');
                    }

                    if (backdrop.classList.contains('hide')) {
                        backdrop.classList.remove('hide');
                    }
                }
                return;
            }

            if (!element.classList.contains('hide')) {
                element.classList.add('hide');
            }

            if (element.classList.contains('show')) {
                element.classList.remove('show');
            }

            if (backdrop) {
                if (backdrop.classList.contains('show')) {
                    backdrop.classList.remove('show');
                }

                if (!backdrop.classList.contains('hide')) {
                    backdrop.classList.add('hide');
                }
            }
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
            minst.closeDialogue();
        };

        minst.closemodal = function() {
            if (minst.params.medialInterval != false) {
                clearInterval(minst.params.medialInterval);
                minst.params.medialInterval = false;
            }

            document.getElementById('mod_helixmedia_launchframe_' + minst.params.docID).src = '';

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
            // Workaround for themes where the bootstrap activation isn't enough on it's own.
            minst.modalVisibility(false);
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
