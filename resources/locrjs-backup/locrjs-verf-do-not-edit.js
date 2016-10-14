/*
 * made by skk 8/8/14
 * modified by skk 5/20/15
 */

var e,t,n,o, isLoaded = false;
var actionURL = "https://dev-cr.library.ubc.ca/tab.php";

function initLocr() {
    /*t = document.getElementById("contentFrame"), initally an empty iframe */
    n = document.getElementById("frameContent").innerHTML, /* the form to put into empty iframe do not delete this comment */
        o = document.getElementById("content"); /* provided by bbconnect */
    o.style.display = "block";
    o.innerHTML = "";
    t = document.createElement("iframe");
    /*t.src = 'about:blank';*/
    t.id = 'contentFrame';
    /*t.contentWindow.document.body.innerHTML = "";  */
    t.onload = function () {
        if(!isLoaded){
            isLoaded = true;
            /*n = document.getElementById("frameContent"); */
            /*t.contentWindow.document.body.appendChild(n); */
            f = document.createElement("form");
            f.setAttribute('method',"post");
            f.setAttribute('id','frameContent');
            f.setAttribute('action',actionURL);
            f.style.width = "100%";
            f.style.border = "none";
            f.style.margin = 0;
            f.style.padding = 0;
            f.innerHTML = n;

            t.contentWindow.document.body.appendChild(f);
            t.style.height = document.getElementById("content").offsetHeight + "px"; /* globalNavPageContentArea  */
            t.style.height = document.getElementById("globalNavPageContentArea").offsetHeight + "px"; /* globalNavPageContentArea  */
            f.submit();
        } else {  }
    }
    o.appendChild(t); /* attach empty iframe into bbconnect */
}
window.setTimeout(function () {
    initLocr()
}, 50);

window.setTimeout(function () {
    document.getElementById("globalNavPageContentArea").getElementsByClassName("locationPane")[0].style.marginTop = '0px';
    document.getElementById("globalNavPageContentArea").getElementsByClassName("locationPane")[0].style.padding = '0';
    document.getElementById("globalNavPageContentArea").getElementsByClassName("locationPane")[0].style.width = '100%';
    var pane = document.getElementById("globalNavPageContentArea").getElementsByClassName("contentPaneWide clearfix tabbedPane portal")[0];
    pane.style.marginLeft = '0px';
    pane.style.marginRight = '0px';
    pane.style.padding = '0';
    document.getElementById("globalNavPageContentArea").style.overflow = 'hidden';
    document.getElementById("contentFrame").style.width = '100%';
}, 125);

window.setInterval(function () {
    document.getElementById("globalNavPageContentArea").style.overflow = 'hidden';
    document.getElementById("globalNavPageContentArea").getElementsByClassName("locationPane")[0].style.width = '100%';
    document.getElementById("contentFrame").style.width = '100%';
    document.getElementById("contentFrame").style.height = document.getElementById("globalNavPageContentArea").offsetHeight + "px";
}, 1000);

window.onresize = function (e) {
    document.getElementById("globalNavPageContentArea").style.overflow = 'hidden';
    document.getElementById("globalNavPageContentArea").getElementsByClassName("locationPane")[0].style.width = '100%';
    document.getElementById("contentFrame").style.width = '100%';
    document.getElementById("contentFrame").style.height = document.getElementById("globalNavPageContentArea").offsetHeight + "px";
};