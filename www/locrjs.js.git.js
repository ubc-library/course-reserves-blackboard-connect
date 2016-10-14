/*
 * made by skk 8/8/14
 * modified by skk 5/20/15
 */
var isLoaded = false;

var globalNavPageContentArea = document.getElementById("globalNavPageContentArea");
var buildingBlockFormInnerHTML = '';

buildingBlockFormInnerHTML = document.getElementById("frameContent").innerHTML;
globalNavPageContentArea.getElementsByClassName("locationPane")[0].style.marginTop = '0px';
globalNavPageContentArea.getElementsByClassName("locationPane")[0].style.padding = '0';
globalNavPageContentArea.getElementsByClassName("locationPane")[0].style.width = '100%';
globalNavPageContentArea.style.overflow = 'hidden';

var pane = globalNavPageContentArea.getElementsByClassName("contentPaneWide clearfix tabbedPane portal")[0];

/*

 # use this to clear the Blackboard area of all the modules crap
 pane.style.marginLeft = '0px';
 pane.style.marginRight = '0px';
 pane.style.padding = '0';
 pane.innerHTML = "";


 # DO NOT use this if Blackboard doesn't have iFrames (new BB doesn't)
 # The iFrame is created in the building block so that it is not cross domain injected
 # This is just to remind myself of the process that needs to happen
 t = document.createElement("iframe");
 t.src = 'about:blank';
 t.id = 'locrFrame';

 console.log("adding locrFrame iframe");
 pane.appendChild(t); // attach empty iframe into bbconnect
 console.log("added locrFrame iframe");

 */

window.setTimeout(function () {

    //console.log("triggered loading locr via iframe");
    if (!isLoaded) {
        isLoaded = true;
        //console.log("attach form to iframe after it loads");
        t = document.getElementById("locrFrame");

        t = document.createElement("iframe");
        //t.src = 'about:blank';
        t.id = 'locrFrame';

        pane.innerHTML = "";
        pane.appendChild(t);

        ti = (t.contentWindow || t.contentDocument);
        if (ti.document) ti = ti.document;

        f = ti.createElement("form");
        f.setAttribute('method', "post");
        f.setAttribute('id', 'frameContent');
        f.setAttribute('action', "https://cr.library.ubc.ca/tab.php");
        f.style.width = "100%";
        f.style.border = "none";
        f.style.margin = 0;
        f.style.padding = 0;
        f.innerHTML = buildingBlockFormInnerHTML;

        pane.style.marginLeft = '0px';
        pane.style.marginRight = '0px';
        pane.style.padding = '0';

        ti.body.appendChild(f);


        t.style.width = "100%"; // globalNavPageContentArea
        t.style.height = globalNavPageContentArea.offsetHeight + "px"; // globalNavPageContentArea

        //t = document.getElementById("locrFrame");
        //pane.innerHTML = "";
        //pane.appendChild(t);

        //console.log("submitting");
        f.submit();


    } else {
        //console.log("already loaded tab");
    }
}, 125);

window.setInterval(function () {
    if (globalNavPageContentArea) {
        globalNavPageContentArea.style.overflow = 'hidden';
        globalNavPageContentArea.style.width = '100%';
    }
}, 125);

window.setInterval(function () {
    if (document.getElementById("locrFrame")) {
        document.getElementById("locrFrame").style.overflow = 'hidden';
        document.getElementById("locrFrame").style.width = '100%';
    }
}, 2500);
