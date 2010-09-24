// Toggles an item between visible and invisible.
function toggleItem(targetId)
{
    target = document.getElementById(targetId);
    if (target.style.display == "none"){
        target.style.display="";
    } else {
        target.style.display="none";
    }
}

function toggleItems()
{
    var argc = toggleItems.arguments.length;
    for (i = 0; i < argc; i++) {
        toggleItem(toggleItems.arguments[i]);
    }
}
