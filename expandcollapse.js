function collapse_all()
{
    var expandtags = document.getElementsByTagName('div');
    var length = expandtags.length;
    for(var i = 0; i < length; i++)
    {
        if(expandtags[i].className == 'expanderexpanded')
        {
            expandtags[i].className = 'expandercollapsed';
            var grandgrandfather = expandtags[i].parentNode.parentNode.parentNode.className;
            if(grandgrandfather == 'entirecourse expanded ghost')
            {
                expandtags[i].parentNode.parentNode.parentNode.className = 'entirecourse collapsed ghost';
            }
            if(grandgrandfather == 'entirecourse expanded')
            {
                expandtags[i].parentNode.parentNode.parentNode.className = 'entirecourse collapsed';
            }
        }
        
    }
}

function expand_all()
{
    var collapsetags = document.getElementsByTagName('div');
    var length = collapsetags.length;
    for(var i = 0; i < length; i++)
    {
        if(collapsetags[i].className == 'expandercollapsed')
        {
            collapsetags[i].className = 'expanderexpanded';
            var grandgrandfather = collapsetags[i].parentNode.parentNode.parentNode.className;
            if(grandgrandfather == 'entirecourse collapsed ghost')
            {
                collapsetags[i].parentNode.parentNode.parentNode.className = 'entirecourse expanded ghost';
            }
            if(grandgrandfather == 'entirecourse collapsed')
            {
                collapsetags[i].parentNode.parentNode.parentNode.className = 'entirecourse expanded';
            }
        }
        
    }
}

function expand_or_collapse(element)
{
    if(element.parentNode.parentNode.parentNode.className == 'entirecourse expanded')
    {
        element.parentNode.parentNode.parentNode.className = 'entirecourse collapsed'; 
        element.className = 'expandercollapsed';
    }
    else if(element.parentNode.parentNode.parentNode.className == 'entirecourse expanded ghost')
    {
        element.parentNode.parentNode.parentNode.className = 'entirecourse collapsed ghost'; 
        element.className = 'expandercollapsed';
    }
    else if(element.parentNode.parentNode.parentNode.className == 'entirecourse collapsed')
    {
        element.parentNode.parentNode.parentNode.className = 'entirecourse expanded'; 
        element.className = 'expanderexpanded';
    }
    else if(element.parentNode.parentNode.parentNode.className == 'entirecourse collapsed ghost')
    {
        element.parentNode.parentNode.parentNode.className = 'entirecourse expanded ghost'; 
        element.className = 'expanderexpanded';
    }
}