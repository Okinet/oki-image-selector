var file_frame;

jQuery(document).ready(function() {
    setupSortation();
    setupDeleteButtons();
});

jQuery('.upload_image_button').live('click', function( event ){

    event.preventDefault();

    if ( file_frame ) {
        file_frame.open();
        return;
    }


    file_frame = wp.media.frames.file_frame = wp.media({
        title: ois_trans.select_files,
        button: {
            text: ois_trans.assign_files,
        },
        multiple: true
    });



    file_frame.on( 'select', function() {

        attachments = file_frame.state().get('selection').toJSON();
        attachments.forEach(renderItem);


        setupSortation();
        setupDeleteButtons();
        updateSortation();
    });


    file_frame.open();
});

function renderItem(element, index, array)
{
    if(element.mime.indexOf("image") > -1)
        var url = element.url;
    else
        var url = element.icon;

    jQuery('#related-files-container').append(item_template.replaceArray(
        new Array('{ITEM_ID}','{ITEM_TITLE}', '{ITEM_URL}'),
        new Array(element.id, element.title, url)
        )
    );
}

function setupDeleteButtons() {
    jQuery("#related-files-container .item .del-item").each(function () {
        jQuery(this).click(function () {
            jQuery(this).parents('.item:first').remove();
            updateSortation();
        })
    });
}

function setupSortation()
{
    jQuery( "#related-files-container" ).sortable({
        update: function( event, ui ) {
            updateSortation();
        }
    });
}

function updateSortation()
{
    jQuery('#ois_value').val(jQuery( "#related-files-container" ).sortable('toArray').toString());
}

String.prototype.replaceArray = function(find, replace) {
    var replaceString = this;
    for (var i = 0; i < find.length; i++) {
        replaceString = replaceString.replace(find[i], replace[i]);
    }
    return replaceString;
};