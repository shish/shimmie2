/**
*
* Crossbrowser Drag Handler
* http://www.webtoolkit.info/
*
* Modified by Erik Youngren to move parent node
**/

var DragHandler = {


    // private property.
    _oElem : null,


    // public method. Attach drag handler to an element.
    attach : function(oElem) {
        oElem.onmousedown = DragHandler._dragBegin;

        // callbacks
        oElem.dragBegin = new Function();
        oElem.drag = new Function();
        oElem.dragEnd = new Function();

        return oElem;
    },


    // private method. Begin drag process.
    _dragBegin : function(e) {
        var oElem = DragHandler._oElem = this;

        if (isNaN(parseInt(oElem.parentNode.style.left))) { oElem.parentNode.style.left = '0px'; }
        if (isNaN(parseInt(oElem.parentNode.style.top))) { oElem.parentNode.style.top = '0px'; }

        var x = parseInt(oElem.parentNode.style.left);
        var y = parseInt(oElem.parentNode.style.top);

        e = e ? e : window.event;
        oElem.mouseX = e.clientX;
        oElem.mouseY = e.clientY;

        oElem.dragBegin(oElem, x, y);

        document.onmousemove = DragHandler._drag;
        document.onmouseup = DragHandler._dragEnd;
        return false;
    },


    // private method. Drag (move) element.
    _drag : function(e) {
        var oElem = DragHandler._oElem;

        var x = parseInt(oElem.parentNode.style.left);
        var y = parseInt(oElem.parentNode.style.top);

        e = e ? e : window.event;
        oElem.parentNode.style.left = x + (e.clientX - oElem.mouseX) + 'px';
        oElem.parentNode.style.top = y + (e.clientY - oElem.mouseY) + 'px';

        oElem.mouseX = e.clientX;
        oElem.mouseY = e.clientY;

        oElem.drag(oElem, x, y);

        return false;
    },


    // private method. Stop drag process.
    _dragEnd : function() {
        var oElem = DragHandler._oElem;

        var x = parseInt(oElem.parentNode.style.left);
        var y = parseInt(oElem.parentNode.style.top);

        oElem.dragEnd(oElem, x, y);

        document.onmousemove = null;
        document.onmouseup = null;
        DragHandler._oElem = null;
    }

}
