function externalLinks() {
    if (!document.getElementsByTagName) return;
    var anchors = document.getElementsByTagName("a");
    for (var i=0; i<anchors.length; i++) {
        var anchor = anchors[i];
        if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "external")
            anchor.target = "_blank";
        if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "toppage")
            anchor.target = "_top";
        if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "rightpage")
            anchor.target = "_right";
    }
}
window.onload = externalLinks;
