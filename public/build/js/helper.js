function changeElementVisibility(elementId, isById, visiblity)
{
    if(typeof elementId == "object") {
        for(a=0; a<=elementId.length; a++)
        {
            var elId = (isById ? "#" : ".") + elementId[a];
            visiblity ? $(elId).show() : $(elId).hide();
        }
    } else {
        var elId = (isById ? "#" : ".") + elementId[a];
        visiblity ? $(elId).show() : $(elId).hide();
    }
}

